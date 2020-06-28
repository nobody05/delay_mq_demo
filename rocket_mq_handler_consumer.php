<?php

require "./db.php";

use Illuminate\Database\Capsule\Manager;
use MQ\Model\TopicMessage;
use MQ\MQClient;

class ConsumerTest
{
    private $client;
    private $consumer;

    public function __construct()
    {
        $this->client = new MQClient(
        // 设置HTTP接入域名（此处以公共云生产环境为例）
            getenv('HTTP_ENDPOINT'),
            // AccessKey 阿里云身份验证，在阿里云服务器管理控制台创建
            getenv('ACCESS_KEY'),
            // SecretKey 阿里云身份验证，在阿里云服务器管理控制台创建
            getenv('SECRET_KEY')
        );

        // 所属的 Topic
        $topic = getenv('TOPIC');
        // Topic所属实例ID，默认实例为空NULL
        $instanceId = getenv('INSTANCE_ID');
        // 您在控制台创建的 Consumer ID(Group ID)
        $groupId = getenv('GROUP_ID');

        $this->consumer = $this->client->getConsumer($instanceId, $topic, $groupId);
    }

    public function run($manager)
    {
        // 在当前线程循环消费消息，建议是多开个几个线程并发消费消息
        while (True) {
            try {
                // 长轮询消费消息
                // 长轮询表示如果topic没有消息则请求会在服务端挂住3s，3s内如果有消息可以消费则立即返回
                $messages = $this->consumer->consumeMessage(
                    3, // 一次最多消费3条(最多可设置为16条)
                    3 // 长轮询时间3秒（最多可设置为30秒）
                );
            } catch (\Exception $e) {
                if ($e instanceof MQ\Exception\MessageNotExistException) {
                    // 没有消息可以消费，接着轮询
                    printf("No message, contine long polling!RequestId:%s\n", $e->getRequestId());
                    continue;
                }

                print_r($e->getMessage() . "\n");

                sleep(3);
                continue;
            }

            print "consume finish, messages:\n";

            // 处理业务逻辑
            $receiptHandles = array();
            foreach ($messages as $message) {
                $receiptHandles[] = $message->getReceiptHandle();


                $messageBody = $message->getMessageBody();

                $orderInfo = json_decode($messageBody, true);
                if (!empty($orderInfo['order_id'])) {
                    $orderId = $orderInfo['order_id'];

                    /**@var $manager Illuminate\Database\Capsule\Manager * */
                    $conn = $manager;
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
            }

            // $message->getNextConsumeTime()前若不确认消息消费成功，则消息会重复消费
            // 消息句柄有时间戳，同一条消息每次消费拿到的都不一样
            print_r($receiptHandles);
            try {
                $this->consumer->ackMessage($receiptHandles);
            } catch (\Exception $e) {
                if ($e instanceof MQ\Exception\AckMessageException) {
                    // 某些消息的句柄可能超时了会导致确认不成功
                    printf("Ack Error, RequestId:%s\n", $e->getRequestId());
                    foreach ($e->getAckMessageErrorItems() as $errorItem) {
                        printf("\tReceiptHandle:%s, ErrorCode:%s, ErrorMsg:%s\n", $errorItem->getReceiptHandle(), $errorItem->getErrorCode(), $errorItem->getErrorCode());
                    }
                }
            }
            print "ack finish\n";


        }

    }
}


$instance = new ConsumerTest();
$instance->run($manager);