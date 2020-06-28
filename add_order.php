<?php

/**
 * @Author: gaozhi
 * @Date:   2020-06-24 17:04:30
 * @Last Modified by:   gaozhi
 * @Last Modified time: 2020-06-24 17:09:19
 */

require './db.php';

/**
 * 随机创建订单
 */
$order = [
	'order_number' => mt_rand(100,10000).date("YmdHis"),
	'user_id' => mt_rand(1, 100),
	'order_amount' => mt_rand(100, 1000),
];

/**@var $manager Illuminate\Database\Capsule\Manager **/
$conn = $manager;

$insertResult = $conn::table("order")
    ->insert($order);

print_r($insertResult);