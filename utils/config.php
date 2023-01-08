<?php
//=========config========//
const siteKey_turnstile = 'siteKey';
const secretKey_turnstile = 'secretKey';
const tokenParamName_turnstile = "token";
const configKey_turnstile = 'heizi_turnstile';
function tns_get_config() {
    $config = setting_get(configKey_turnstile);
    return $config ? json_decode($config,true) : array(
        siteKey_turnstile=>"",
        secretKey_turnstile=>''
    );
}
//==========req==========//
/**
 * simple cloudflare-turnstile serverside validation in php
 *
 * @see https://developers.cloudflare.com/turnstile/get-started/server-side-validation
 * @return bool|null returns null if its on error and so you have to retry again
 * TURE means success, FALSE means param is empty
 */
function tns_validate_post_req($data = null): ?bool {
    $data = empty($data) ? tns_token_form_param() :$data ;
    if (empty($data)) return false;
    $url = 'https://challenges.cloudflare.com/turnstile/v0/siteverify';
    $data = [
        'secret'=> tns_get_config()[secretKey_turnstile],
        'response' => $data,
        'remoteip' => $_SERVER['REMOTE_ADDR']
    ];
    $data = tns_req($url,$data);
    if (empty($data)) {
        tns_error_bad_vld_req("empty rsp $data", -2);
        return null;
    }
    if (!empty($data['success'])) {
        $directory = array(
            'missing-input-secret'=>'未传递秘密参数。',
            'invalid-input-secret'=>'秘密参数无效或不存在。',
            'missing-input-response'=>'未传递响应参数。',
            'invalid-input-response'=>'响应参数无效或已过期。',
            'bad-request'=>'该请求被拒绝，因为它格式不正确。',
            'timeout-or-duplicate'=>'响应参数之前已经过验证。',
            'internal-error'=>'验证响应时发生内部错误。可以重试该请求。'
        );
        $msg = $data['error-codes'];
        $msg = empty($msg) ? "未知错误: ".implode($data) : $directory->$msg;
        empty($msg) and $msg = "未知错误: ".$msg;
        message(1,$msg);
        return null;
    }
    return true;
}
/**
 * 获取用于服务器验证的token
 *
 * @return array|false|int|mixed|string
 */
function tns_token_form_param() {
    $data = param(tokenParamName_turnstile);
    if (empty($data)) {
        tns_error_message_turnstile();
        return false;
    }
    return $data;
}
//==========err==========//
function tns_error_message_turnstile(): void {
    message(1,'请通过Turnstile');
}
function tns_error_bad_vld_req($err, $code=-1): bool{
    empty($err) and $err = ": ".$err;
    message($code,'验证请求失效'.$err);
    return false;
}
//======return array=====//
function tns_req(string $url, array $data) {
    !defined("curlNotExist") AND define("curlNotExist",!function_exists('curl_exec'));
    $rsp = curlNotExist ? tns_req_not_curl($url,$data) : https_post($url,$data,460,) ;
    if (empty($rsp)) return tns_error_bad_vld_req(null);
    $rsp = json_decode($rsp,true);
    if (empty($rsp)) return tns_error_bad_vld_req("bad rsp $rsp",-2);
    return $rsp;
}
function tns_req_not_curl($url, $data) {
    $data = http_build_query($data);
    $data = stream_context_create([
        "ssl" => [
            "verify_peer"=>false,
            "verify_peer_name"=>false,
        ],
        'http' => [
            'method' => 'POST',
            'header'=> "Content-type: application/x-www-form-urlencoded\r\n" . "Content-Length: " . strlen($data) . "\r\n",
            'content' => $data,
            'timeout' => 460
        ],
    ]);
    return @file_get_contents($url, false, $data);
}