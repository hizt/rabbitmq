<?php
include_once "../RabbitMQ/RabbitMQ.php";
include_once '../vendor/autoload.php';

try {
    $rabbitMq = new \RabbitMQ\RabbitMQ();

    //不延迟的消息
    $rabbitMq->producer('test', 'Hello world!');
    echo "发送不延迟的消息\n";

    //延迟1秒的消息
    $rabbitMq->producer('test', [1, 2] , 1000);
    echo "发送延迟1秒的消息\n";

    //延迟2秒的消息
    $rabbitMq->producer('test', new stdClass(), 2000);
    echo "发送延迟2秒的消息\n";
} catch (Exception $e) {
    var_dump($e);
}