<?php
const siteKey = 'siteKey';
const secretKey = 'secretKey';

const configKey = 'heizi_turnstile';
function getConfig() {
    $config = setting_get(configKey);
    return $config ? json_decode($config,true) : array(
        siteKey=>"",
        secretKey=>''
    );
}