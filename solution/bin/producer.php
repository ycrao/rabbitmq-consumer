<?php

include(__DIR__.'/config.php');

use PhpAmqpLib\Connection\AMQPStreamConnection;
use PhpAmqpLib\Message\AMQPMessage;
use PhpAmqpLib\Exchange\AMQPExchangeType;
use PhpAmqpLib\Wire\AMQPTable;

$queue1 = 'que.calc_sum';
$queue2 = 'que.calc_avg';
$queue3 = 'que.calc';
$exchange = 'ex.routing';
$exchangeType = AMQPExchangeType::TOPIC;
$routingKey1 = 'op.sum';
$routingKey2 = 'op.avg';
// 符号 "#" 表示匹配一个或多个词，符号 "*" 表示匹配一个词。
$routingKey3 = 'op.*';

// 连接
$connection = new AMQPStreamConnection(
    RABBITMQ_HOST,
    RABBITMQ_PORT,
    RABBITMQ_USER,
    RABBITMQ_PASSWORD,
    RABBITMQ_VHOST
);

// 通道
$channel = $connection->channel();


// 申明 queue
$channel->queue_declare(
    $queue1,
    $passive = false,
    $durable = true,
    $exclusive = false,
    $auto_delete = false,
    $nowait = false,
    $arguments = array(),
    $ticket = null
);
$channel->queue_declare(
    $queue2,
    $passive = false,
    $durable = true,
    $exclusive = false,
    $auto_delete = false,
    $nowait = false,
    $arguments = array(),
    $ticket = null
);
$channel->queue_declare(
    $queue3,
    $passive = false,
    $durable = true,
    $exclusive = false,
    $auto_delete = false,
    $nowait = false,
    $arguments = array(),
    $ticket = null
);

// 申明 exchange
$channel->exchange_declare(
    $exchange,
    $exchangeType,
    $passive = false,
    $durable = true,
    $auto_delete = true,
    $internal = false,
    $nowait = false,
    $arguments = array(),
    $ticket = null
);

// 绑定 queue
$channel->queue_bind(
    $queue1,
    $exchange,
    $routingKey1,
    $nowait = false,
    $arguments = array(),
    $ticket = null
);
$channel->queue_bind(
    $queue2,
    $exchange,
    $routingKey2,
    $nowait = false,
    $arguments = array(),
    $ticket = null
);
$channel->queue_bind(
    $queue3,
    $exchange,
    $routingKey3,
    $nowait = false,
    $arguments = array(),
    $ticket = null
);

for ($i = 0; $i < 100; $i ++)
{
    $jobArray = [
        'job_id' => $i + 1,
        'task' => 'calc',  // calc sum or avg value of a and b
        'params' => [
            'a' => rand(0, 100),
            'b' => rand(0, 100)
        ],
        'sleep_period' => rand(0, 3),
    ];

    $msg = new AMQPMessage(
        json_encode($jobArray, JSON_UNESCAPED_SLASHES|JSON_UNESCAPED_UNICODE),
        [
            'content_type' => 'application/json',
            'delivery_mode' => AMQPMessage::DELIVERY_MODE_PERSISTENT, # make message persistent
            // 'app_id' => 'demo',
            // 'message_id' => uniqid(date('YmdHis-', time())),
        ]
    );

    $headers = new AMQPTable([
        "retry_nums"=> 3  // 重试次数
    ]);

    // 设置头
    $msg->set('application_headers', $headers);

    if ($i % 2) {
        $channel->basic_publish(
            $msg,
            $exchange,
            $routingKey1,
            $mandatory = false,
            $immediate = false,
            $ticket = null
        );
        echo "send job message using routing key - ".$routingKey1." i=".$i.PHP_EOL;
    } else {
        $channel->basic_publish(
            $msg,
            $exchange,
            $routingKey2,
            $mandatory = false,
            $immediate = false,
            $ticket = null
        );
        echo "send job message using routing key - ".$routingKey2." i=".$i.PHP_EOL;
    }
    sleep(1);
}

try {
    // 关闭通道与连接
    $channel->close();
    $connection->close();
} catch (\Exception $e) {
    echo 'close connection error:'.$e->getMessage();
}

