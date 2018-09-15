<?php
include_once "../RabbitMQ/RabbitMQ.php";
include_once '../vendor/autoload.php';


/**
 * @param $message
 * @throws Exception
 */
$callbackFunction = function ($message)
{
    if ($message instanceof stdClass){
        echo "Message is stdClass\n";
        //Do something
    }
    else if(is_array($message)){
        echo "Message is array\n";
        //Do something
    }
    else if(is_string($message)){
        echo "Message is string\n";
        //Do something
    }
    else{
        //Do something
        throw new \Exception('Undefined message!');
    }
};


try {
    $rabbitMq = new \RabbitMQ\RabbitMQ();
    $rabbitMq->consumer('test' , $callbackFunction);
} catch (Exception $e) {
    var_dump($e);
}

