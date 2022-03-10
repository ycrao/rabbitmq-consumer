<?php

require_once __DIR__ . '/../vendor/autoload.php';

define('RABBITMQ_HOST', 'localhost');
define('RABBITMQ_PORT', 5672);
define('RABBITMQ_USER', 'guest');
define('RABBITMQ_PASSWORD', 'guest');
define('RABBITMQ_VHOST', '/');

// If this is enabled you can see AMQP output on the CLI
define('AMQP_DEBUG', false);