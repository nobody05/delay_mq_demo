<?php

require "./db.php";

use Illuminate\Database\Capsule\Manager;
use MQ\Model\TopicMessage;
use MQ\MQClient;

class ProducerTest
{
    private $client;
    private $producer;

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

        $this->producer = $this->client->getProducer($instanceId, $topic);
    }

    public function run($manager)
    {
        try
        {

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

            $body = json_encode(['order_id' => $insertId, 'created_time' => date("Y-m-d H:i:s")]);
            $publishMessage = new TopicMessage(
                $body
            );
            // 设置消息KEY
            $publishMessage->setMessageKey("MessageKey");

            // 定时消息, 定时时间为3分钟后
            $publishMessage->setStartDeliverTime(time() * 1000 + 3 * 60 * 1000);

            $result = $this->producer->publishMessage($publishMessage);

            print "Send mq message success. msgId is:" . $result->getMessageId() . ", bodyMD5 is:" . $result->getMessageBodyMD5() . "\n";
        } catch (\Exception $e) {
            print_r($e->getMessage() . "\n");
        }
    }
}


$instance = new ProducerTest();
$instance->run($manager);