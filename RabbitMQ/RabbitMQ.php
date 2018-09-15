<?php

namespace RabbitMQ;

use PhpAmqpLib\Connection\AMQPStreamConnection;
use PhpAmqpLib\Message\AMQPMessage;
use PhpAmqpLib\Wire\AMQPTable;

class RabbitMQ
{
    private $host = 'localhost';
    private $port = 5672;
    private $user = 'guest';
    private $password = 'guest';

    /** @var AMQPStreamConnection */
    private $amqConnection;

    private $exchange = '';
    private $routingKey = '';

    private $passive = false;
    private $durable = false;
    private $exclusive = false;
    private $autoDelete = true;
    private $nowait = false;
    private $ticket = null;
    private $arguments = [];


    function __construct($host = null, $port = null, $user = null, $password = null)
    {
        if ($host)
            $this->host = $host;

        if ($port)
            $this->port = $port;

        if ($user)
            $this->user = $user;

        if ($password)
            $this->password = $password;

        $this->amqConnection = new AMQPStreamConnection($this->host, $this->port, $this->user, $this->password);
    }

    function __destruct()
    {
        $this->amqConnection->close();
    }

    /**
     * @param $queueName
     * @param $messageObj
     * @param int $delay
     * @return bool
     * @throws \Exception
     */
    function producer($queueName, $messageObj, $delay = 0)
    {
        $arguments = $this->arguments;
        $delay = intval($delay);
        if ($delay > 0) {
            //创建一个死信收容队列
            $channel = $this->amqConnection->channel();
            $channel->queue_declare($queueName,
                $this->passive,
                $this->durable,
                $this->exclusive,
                $this->autoDelete,
                $this->nowait,
                $this->parseArguments($arguments),
                $this->ticket
            );
            $channel->close();

            //写入延迟队列的参数
            $delayArguments = [
                'x-message-ttl' => $delay,  // 延迟时间 （毫秒）
                'x-dead-letter-exchange' => $this->exchange,  // 延迟结束后指向交换机（死信收容交换机）
                'x-dead-letter-routing-key' => $queueName,  // 延迟结束后指向队列（死信收容队列），可直接设置queue name也可以设置routing-key
            ];
            $arguments = array_merge($arguments, $delayArguments);
            $queueName = $this->getDelayQueueName($queueName, $delay);
        }


        $channel = $this->amqConnection->channel();
        $channel->queue_declare($queueName,
            $this->passive,
            $this->durable,
            $this->exclusive,
            $this->autoDelete,
            $this->nowait,
            $this->parseArguments($arguments),
            $this->ticket
        );

        $amqMessage = new AMQPMessage(serialize($messageObj));
        $channel->basic_publish($amqMessage, $this->exchange, $this->routingKey ? $this->routingKey : $queueName);
        $channel->close();

        return true;
    }

    /**
     * @param $queueName
     * @param callable $callback
     * @throws \Exception
     */
    function consumer($queueName, callable $callback)
    {
        $tempCallback = function ($messageObj) use ($callback) {
            $messageObj = (unserialize($messageObj->body));
            call_user_func_array($callback, [$messageObj]);
        };

        $channel = $this->amqConnection->channel();
        $channel->queue_declare($queueName, $this->passive, $this->durable, $this->exclusive, $this->autoDelete, $this->nowait, $this->arguments, $this->ticket);
        $channel->basic_consume($queueName, '', false, true, $this->exclusive, $this->nowait, $tempCallback);
        while (count($channel->callbacks)) {
            $channel->wait();
        }
    }

    /**
     * @param $queueName string
     * @param $delay int
     * @return string
     * @example QUEUE_NAME-delay-10-s
     */
    private function getDelayQueueName($queueName, $delay)
    {
        return $delay > 0 ? $queueName . '-delay-' . $delay . 'ms' : $queueName;
    }


    /**
     * @param $arguments
     * @return AMQPTable
     */
    private function parseArguments($arguments)
    {
        return new AMQPTable($arguments);
    }

    /**
     * @param bool $passive
     * @return RabbitMQ
     */
    public function setPassive($passive)
    {
        $this->passive = $passive;
        return $this;
    }

    /**
     * @param bool $durable
     * @return RabbitMQ
     */
    public function setDurable($durable)
    {
        $this->durable = $durable;
        return $this;
    }

    /**
     * @param bool $exclusive
     * @return RabbitMQ
     */
    public function setExclusive($exclusive)
    {
        $this->exclusive = $exclusive;
        return $this;
    }

    /**
     * @param bool $nowait
     * @return RabbitMQ
     */
    public function setNowait($nowait)
    {
        $this->nowait = $nowait;
        return $this;
    }

    /**
     * @param bool $autoDelete
     * @return RabbitMQ
     */
    public function setAutoDelete($autoDelete)
    {
        $this->autoDelete = $autoDelete;
        return $this;
    }

    /**
     * @param null $ticket
     * @return RabbitMQ
     */
    public function setTicket($ticket)
    {
        $this->ticket = $ticket;
        return $this;
    }

    /**
     * @param string $exchange
     * @return RabbitMQ
     */
    public function setExchange($exchange)
    {
        $this->exchange = $exchange;
        return $this;
    }

    /**
     * @param string $routingKey
     * @return RabbitMQ
     */
    public function setRoutingKey($routingKey)
    {
        $this->routingKey = $routingKey;
        return $this;
    }

    /**
     * @param array $arguments
     * @return RabbitMQ
     */
    public function setArguments($arguments)
    {
        $this->arguments = $arguments;
        return $this;
    }

}