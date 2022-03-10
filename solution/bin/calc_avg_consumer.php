<?php

include(__DIR__.'/config.php');

use PhpAmqpLib\Connection\AMQPStreamConnection;
use PhpAmqpLib\Exchange\AMQPExchangeType;

// $queue1 = 'que.calc_sum';
$queue2 = 'que.calc_avg';
$exchange = 'ex.routing';
$exchangeType = AMQPExchangeType::TOPIC;
// $routingKey1 = 'op.sum';
$routingKey2 = 'op.avg';

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


// 申明 queue2 此脚本只消费 op.avg routing key
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
    $queue2,
    $exchange,
    $routingKey2,
    $nowait = false,
    $arguments = array(),
    $ticket = null
);

/**
 * @param \PhpAmqpLib\Message\AMQPMessage $message
 */
function process_message($message)
{
    // $routeKey = $message->delivery_info['routing_key'];
    $routeKey = $message->getRoutingKey();
    echo "receive message using routing key - ".$routeKey.": ".PHP_EOL;
    echo "[X] received, message body: ".$message->body.PHP_EOL;
    echo '---DO JOB START---'.PHP_EOL;
    $job = json_decode($message->body, true);
    echo 'Job id: '.$job['job_id'].PHP_EOL;
    $sum = $job['params']['a'] + $job['params']['b'];
    $avg = $sum/2;
    echo 'calc avg of a: '.$job['params']['a'].' and b: '.$job['params']['b']. ' is '.$avg.' .'.PHP_EOL;
    echo "---DO JOB END---".PHP_EOL;
    // $message->delivery_info['channel']->basic_ack($message->delivery_info['delivery_tag']);
    // ack
    $message->getChannel()->basic_ack($message->getDeliveryTag());
    sleep($job['sleep_period']);
}

// 每次拿去 1 个
$channel->basic_qos(null, 1, null);

$channel->basic_consume(
    $queue2,
    '',
    $no_local = false,
    $no_ack = false,
    $exclusive = false,
    $nowait = false,
    $callback = 'process_message',
    $ticket = null,
    $arguments = array()
);

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
try {
    while (count($channel->callbacks)) {
        $channel->wait(null, false, 10);
    }
} catch (AMQPTimeoutException $e) {
    // 连接超时错误
    echo 'AMQP Timeout Exception:' . $e->getTraceAsString();
} catch (AMQPIOWaitException $e) {
    // IO 等待错误
    echo 'AMQP IO Exception:' . $e->getTraceAsString();
} catch (\Exception $e) {
    echo 'Exception:' . $e->getTraceAsString();
}

// 出错自行退出
exit(1);