<?php

/**
 * @Author: gaozhi
 * @Date:   2020-06-24 17:02:06
 * @Last Modified by:   gaozhi
 * @Last Modified time: 2020-06-24 17:03:25
 */
require './common.php';

use Illuminate\Database\Capsule\Manager;
use Illuminate\Contracts\Events\Dispatcher as Dispatcher;
use Illuminate\Container\Container;

$manager = new Manager();

$manager->addConnection([
    'driver'    => 'mysql',
    'host'      => '127.0.0.1',
    'database'  => 'test',
    'username'  => 'root',
    'password'  => 'root',
    'charset'   => 'utf8',
    'collation' => 'utf8_unicode_ci',
    'prefix'    => '',
]);

$manager->setAsGlobal();
$manager->bootEloquent();
