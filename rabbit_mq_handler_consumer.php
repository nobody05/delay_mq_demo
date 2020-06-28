<?php

require './db.php';


use Illuminate\Database\Capsule\Manager;
use PhpAmqpLib\Connection\AMQPStreamConnection;
use PhpAmqpLib\Exchange\AMQPExchangeType;

$exchange = 'router';
$queue = 'msgs';
$consumerTag = 'consumer';

//$exchange = 'order15min_notify_exchange';
//$queue = 'order15minx_notify_queue';

$dlxExchange = "dlx_order15min_exchange";
$dlxQueue = "dlx_order15min_queue";

$connection = new AMQPStreamConnection(getenv('RABBIT_HOST'), getenv('RABBIT_PORT'), getenv("RABBIT_USER"), getenv("RABBIT_PASS"), getenv("RABBIT_VHOST"));
$channel = $connection->channel();
$channel->queue_declare($dlxQueue, false, true, false, false);
$channel->exchange_declare($dlxExchange, AMQPExchangeType::DIRECT, false, true, false);
$channel->queue_bind($dlxQueue, $dlxExchange);

/**
 * @param \PhpAmqpLib\Message\AMQPMessage $message
 */
function process_message($message)
{
    echo "\n--------\n";
    echo $message->body;
    echo "\n--------\n";


    $orderInfo = json_decode($message->body, true);
    if (!empty($orderInfo['order_id'])) {
        $orderId = $orderInfo['order_id'];

        /**@var $conn Illuminate\Database\Capsule\Manager * */
        $conn = getdb();
        $orderInfo = $conn::table("order")
            ->select(['id', 'user_id'])
            ->where('id', '=', $orderId)
            ->where('status', '=', 1)
            ->first();
        if (!empty($orderInfo)) {
            $orderInfo = json_decode(json_encode($orderInfo), true);
            sendEmail($orderInfo['user_id']);
            $conn::table('order')
                ->where('id', '=', $orderInfo['id'])
                ->update(['sended_need_pay_notify' => 1]);
            logs("update-success-orderId-" . $orderInfo['id'] . "-userId-" . $orderInfo['user_id']);
        }


    }
    $message->delivery_info['channel']->basic_ack(
        $message->delivery_info['delivery_tag']);
}

$channel->basic_consume($dlxQueue, $consumerTag, false, false, false, false, 'process_message');

/**
 * @param \PhpAmqpLib\Channel\AMQPChannel $channel
 * @param \PhpAmqpLib\Connection\AbstractConnection $connection
 */
function shutdown($channel, $connection)
{
    $channel->close();
    $connection->close();
}

register_shutdown_function('shutdown', $channel, $connection);

// Loop as long as the channel has callbacks registered
while ($channel ->is_consuming()) {
    $channel->wait();
}