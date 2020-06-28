<?php

use Illuminate\Database\Capsule\Manager;

function sendEmail($userId)
{
    echo "send email to ". $userId .' success ...'. PHP_EOL;
}

function logs($msg = '')
{
    echo date("Y-m-d H:i:s").":". $msg. " ".PHP_EOL;
}

function getdb()
{
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

    return $manager;
}