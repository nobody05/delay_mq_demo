<?php

/**
 * @Author: gaozhi
 * @Date:   2020-06-24 17:01:37
 * @Last Modified by:   gaozhi
 * @Last Modified time: 2020-06-24 17:01:58
 */

date_default_timezone_set("Asia/Shanghai");

require './tools.php';
require './vendor/autoload.php';


use Symfony\Component\Dotenv\Dotenv;

$dotenv = new Dotenv();
$dotenv->usePutenv()->load(__DIR__.'/.env');