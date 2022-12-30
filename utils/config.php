<?php
const siteKey = 'siteKey';
const secretKey = 'secretKey';

const configKey = 'heizi_turnstile';
//function includeFile($file) {
//    include_once _include(APP_PATH."plugin/heizi_turnstile/$file");
//}
function getConfig() {
    $config = setting_get(configKey);
    return $config ? json_decode($config,true) : array(
        siteKey=>"",
        secretKey=>''
    );
}

function error_message(): void {
    message(1,'请通过Turnstile');
}

/**
 * 获取用于服务器验证的token
 *
 * @return array|false|int|mixed|string
 */
function getTokenFromReq() {
    $data = param('cf-turnstile-response');
    if (empty($data)) {
        error_message();
        return false;
    }
    return $data;
}
function bad_vld_req(): bool{
    message(1,'验证请求失效');
    return false;
}
function req(string $url,array $data) {

    !defined("curlNotExist") AND define("curlNotExist",!function_exists('curl_exec'));
    if (curlNotExist) {
        message(-1, "curl not support");
        return false;
    }
    $ch = curl_init();
    curl_setopt_array($ch,[
        CURLOPT_URL=>$url,
        CURLOPT_HEADER=>false,
        CURLOPT_POST=>1,
        CURLOPT_HTTPHEADER=>['ContentType:application/x-www-form-urlencoded'],
        CURLOPT_RETURNTRANSFER=>true,
        CURLOPT_CONNECTTIMEOUT=>5*1000,
        CURLOPT_POSTFIELDS=>$data,
    ]);
    $err = curl_error($ch);
    $rsp = curl_exec($ch);

    curl_close($ch);
    if (empty($rsp) || $err!=0) return bad_vld_req();

    $rsp = json_decode($rsp);
    if (empty($rsp)) return  bad_vld_req();

    return $rsp;
}
function validate_post_req():void {

    $url = 'https://challenges.cloudflare.com/turnstile/v0/siteverify';

    $data = getTokenFromReq();
    if (empty($data)) return;

    $data = [
        'secret'=> getConfig()[secretKey],
        'response' => $data,
        'remoteip' => $_SERVER['REMOTE_ADDR']
    ];
    $data = req($url,$data);

    if (empty($data)) bad_vld_req();

    if (!$data['success']) {
        $directory = array(
            'missing-input-secret'=>'未传递秘密参数。',
            'invalid-input-secret'=>'秘密参数无效或不存在。',
            'missing-input-response'=>'未传递响应参数。',
            'invalid-input-response'=>'响应参数无效或已过期。',
            'bad-request'=>'该请求被拒绝，因为它格式不正确。',
            'timeout-or-duplicate'=>'响应参数之前已经过验证。',
            'internal-error'=>'验证响应时发生内部错误。可以重试该请求。'
        );
        message(1,$directory[$data['error-codes']]);
    }

}