<?php
include_once _include(APP_PATH."plugin/heizi_turnstile/utils/config.php");
empty(tns_validate_post_req()) && tns_error_message_turnstile();
?>