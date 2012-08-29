#!/usr/bin/env php
<?php

require_once('../size.php');

$size = SizeClient::getInstance();

// Using the UDP Proxy
$size->setProxyAPI('udp', '127.0.0.1', 6125);
if (! $size->publishEvent('api.get', 1)) {
	echo "FAILED to publish event:\n";
	var_dump($size->getLastMessage());
	var_dump($size->getLastEvent());
}

// Using the TCP Proxy
$size->setProxyAPI('tcp', '127.0.0.1', 6120);
if (! $size->publishEvent('api.get', 1)) {
	echo "FAILED to publish event:\n";
	var_dump($size->getLastMessage());
	var_dump($size->getLastEvent());
}

// Using the Redis Proxy
$size->setProxyAPI('redis', '127.0.0.1', 6379);
if (! $size->publishEvent('api.get', 1)) {
	echo "FAILED to publish event:\n";
	var_dump($size->getLastMessage());
	var_dump($size->getLastEvent());
}

// Direct to the RESTful Event Publisher API
$size->setProxyAPI(null);
if (! $size->publishEvent('api.get', 1)) {
	echo "FAILED to publish event:\n";
	var_dump($size->getLastMessage());
	var_dump($size->getLastEvent());
}

