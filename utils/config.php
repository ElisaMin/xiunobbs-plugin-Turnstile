<?php
const siteKey = 'siteKey';
const secretKey = 'secretKey';

const configKey = 'heizi_turnstile';
function includeFile($file) {
    include_once _include(APP_PATH."plugin/heizi_turnstile/$file");
}
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
function getTokenFromReq(): mixed {
    $data = param('cf-turnstile-response');
    if (empty($data)) {
        error_message();
        return false;
    }
    return $data;
}
function validate_post_req():void {

    $url = 'https://challenges.cloudflare.com/turnstile/v0/siteverify';

    $data = getTokenFromReq();
    if (empty($data)) return;

    $data = array(
        'secret'=> getConfig()[secretKey],
        'response' => $data,
        'remoteip' => $_SERVER['REMOTE_ADDR']
    );
    $data = array('http'=>array(
        'method'=>'POST',
        'content' => $data,
        'header'=> "Content-type: application/x-www-form-urlencoded\r\n"
            .      "Content-Length: " . strlen(json_encode($data)) . "\r\n",
        'timeout' => 5*1000

    ),'content' => $data);
    $response = @file_get_contents($url, false, stream_context_create($data));
    $response = json_decode($response);
    if (empty($response)) {
        error_message();
        return;
    }
    if (!$response->success) {
        $directory = array(
            'missing-input-secret'=>'未传递秘密参数。',
            'invalid-input-secret'=>'秘密参数无效或不存在。',
            'missing-input-response'=>'未传递响应参数。',
            'invalid-input-response'=>'响应参数无效或已过期。',
            'bad-request'=>'该请求被拒绝，因为它格式不正确。',
            'timeout-or-duplicate'=>'响应参数之前已经过验证。',
            'internal-error'=>'验证响应时发生内部错误。可以重试该请求。'
        );
        message(1,$directory[$response['error-codes']]);
    }

}