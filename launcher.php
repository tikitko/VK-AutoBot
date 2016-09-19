#!/bin/bash
<?php
require_once 'Bot.class.php';
$bot = new Bot(
    'VK API KEY', // VK API KEY
    'ANTI-CAPTCHA API KEY' // ANTI-CAPTCHA API KEY (https://rucaptcha.com/)
);
$bot->run();