<?php
//!defined('DEBUG') AND exit('Access Denied.');
include_once _include(APP_PATH."plugin/heizi_turnstile/utils/config.php");
if ($method == 'POST') {
    setting_set(configKey_turnstile,json_encode($_POST));
    echo json_encode(array("code"=>1));
} else include_once _include(APP_PATH."plugin/heizi_turnstile/setting.phtml");