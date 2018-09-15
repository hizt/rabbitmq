## rabbitMQ Delay Queue
This library is a easy-to-use delayed message queue for rabbitMq.

### Requirements
    -   PHP 5.3** due to the use of `namespace`
    -   php-amqplib/php-amqplib >=2.6.1

### Composer
`composer install zt/rabbit-mq`

### Example

-   producer
    ```PHP
        $rabbitMq = new \RabbitMQ\RabbitMQ();
        //Send a normal message.
        $rabbitMq->producer('test', 'Hello world!');
        //Send a message delayed by 1 second.
        $rabbitMq->producer('test', [1, 2] , 1000);
        //Send a message delayed by 2 second
        $rabbitMq->producer('test', new stdClass(), 2000);`
    ```
-   consumer
    ```PHP
        $rabbitMq = new \RabbitMQ\RabbitMQ();
        $rabbitMq->consumer('test' , function($msg){
            var_dump($msg);
        });
    ```