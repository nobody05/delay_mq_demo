<?php

/**
 * @Author: gaozhi
 * @Date:   2020-06-24 17:04:20
 * @Last Modified by:   gaozhi
 * @Last Modified time: 2020-06-24 17:09:23
 */

use Illuminate\Database\Capsule\Manager;

require './db.php';

/**@var $manager Illuminate\Database\Capsule\Manager **/
$conn = $manager;

while(true) {
    // 未支付订单列表
    $orderList = $conn::table("order")
        ->where("created_time",  '<=', date("Y-m-d H:i:s", strtotime("-15 minutes")))
        ->where('sended_need_pay_notify', '=', 2)
        ->where('status', '=', 1)
        ->select(['user_id', 'id'])
        ->orderBy("id", 'asc')
        ->get();
    $orderList = json_decode(json_encode($orderList), true);
    foreach ($orderList as $orderInfo) {
        sendEmail($orderInfo['user_id']);
        $conn::table('order')
            ->where('id', '=', $orderInfo['id'])
            ->update(['sended_need_pay_notify' => 1]);
        logs("update-success-orderId-". $orderInfo['id']."-userId-".$orderInfo['user_id']);
    }

    sleep(10);
}


