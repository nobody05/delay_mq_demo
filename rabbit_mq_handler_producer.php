<?php

require './db.php';


use Illuminate\Database\Capsule\Manager;
use PhpAmqpLib\Connection\AMQPStreamConnection;
use PhpAmqpLib\Exchange\AMQPExchangeType;
use PhpAmqpLib\Message\AMQPMessage;


$exchange = 'order15min_notify_exchange';
$queue = 'order15minx_notify_queue';

$dlxExchange = "dlx_order15min_exchange";
$dlxQueue = "dlx_order15min_queue";

$connection = new AMQPStreamConnection(getenv('RABBIT_HOST'), getenv('RABBIT_PORT'), getenv("RABBIT_USER"), getenv("RABBIT_PASS"), getenv("RABBIT_VHOST"));
$channel = $connection->channel();

$channel->exchange_declare($exchange, AMQPExchangeType::DIRECT, false, true, false);
$channel->exchange_declare($dlxExchange, AMQPExchangeType::DIRECT, false, true, false);

// 设置队列的过期时间
// 正常队列
//$queueArguments = ['x-message-ttl' => 3*60*1000, 'x-dead-letter-exchange', $dlxExchange];
$table = new \PhpAmqpLib\Wire\AMQPTable();
$table->set('x-message-ttl', 3*60*1000);
$table->set("x-dead-letter-exchange", $dlxExchange);
$channel->queue_declare($queue, false, true, false, false, false, $table);
$channel->queue_bind($queue, $exchange);


// 死信
$channel->queue_declare($dlxQueue, false, true, false, false, false);
$channel->queue_bind($dlxQueue, $dlxExchange);



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

$insertId = $conn::table("order")
    ->insertGetId($order);

$messageBody = json_encode(['order_id' => $insertId, 'created_time' => date("Y-m-d H:i:s")]);

$message = new AMQPMessage($messageBody, array('content_type' => 'text/plain', 'delivery_mode' => AMQPMessage::DELIVERY_MODE_PERSISTENT));
$channel->basic_publish($message, $exchange);

$channel->close();
$connection->close();