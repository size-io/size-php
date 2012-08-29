<?php

/*
   This file is provided to you under the Apache License,
   Version 2.0 (the "License"); you may not use this file
   except in compliance with the License.  You may obtain
   a copy of the License at

   http://www.apache.org/licenses/LICENSE-2.0

   Unless required by applicable law or agreed to in writing,
   software distributed under the License is distributed on an
   "AS IS" BASIS, WITHOUT WARRANTIES OR CONDITIONS OF ANY
   KIND, either express or implied.  See the License for the
   specific language governing permissions and limitations
   under the License.
*/

/**
 * The Size API for PHP allows you to connect to the Size.IO
 * platform to publish events.
 *
 * @author Gene Stevens (gene@triplenexus.org)
 * @package SizeAPI
 */
class SizeClient {

	private static $instance;
	protected $useProxy = false;
	protected $proxyAPI = array('host'=>'127.0.0.1', 'interface'=>'tcp', 'port'=>6120);
	protected $udpProxy = null;
	protected $tcpProxy = null;
	protected $redisProxy = null;
	protected $lastEvent = array();
	protected $lastMessage = '';
	protected $publisherAccessToken = '00000000-0000-0000-0000-000000000000';
	protected $publisherRestURL = 'http://api.size.io/v1.0/event/publish';

	private function __construct() {
		$envPublisherToken = getenv("SIZE_PUBLISHER_TOKEN");
		if ($this->publisherAccessToken === '00000000-0000-0000-0000-000000000000' && $envPublisherToken )
			$this->publisherAccessToken = $envPublisherToken;
	}

	private function __clone() {

	}

	public function __destruct() {
		if ($this->tcpProxy)
			socket_close($this->tcpProxy);
		if ($this->udpProxy)
			socket_close($this->udpProxy);
		if ($this->redisProxy)
			$this->redisProxy->close();
	}

	public static function getInstance() {
		if (! self::$instance)
			self::$instance = new self();
		return self::$instance;
	}

	public function setProxyAPI($interface='tcp', $host='127.0.0.1', $port=6120) {
		if ($interface === null || $interface === false) {
			$this->useProxy = false;
			return;
		}
		$this->useProxy = true;
		switch ($interface) {
			case 'tcp':
				$this->proxyAPI['interface'] = 'tcp';
				break;
			case 'udp':
				$this->proxyAPI['interface'] = 'udp';
				break;
			case 'redis':
				if (! class_exists('Redis'))
					throw new SizeClientException("PhpRedis (https://github.com/nicolasff/phpredis) client not found");
				$this->proxyAPI['interface'] = 'redis';
				break;
			default:
				throw new SizeClientException("Unrecognized interface value: '{$array['interface']}'");
		}
		$this->proxyAPI['host'] = $host;
		$this->proxyAPI['port'] = $port;
	}

	public function publishEvent($key, $val, $time=null) {
		$this->sanitizeKey($key);
		$this->sanitizeVal($val);
		if ($this->useProxy)
			return $this->proxyPublishEvent($key, $val, $time);
		else
			return $this->directPublishEvent($key, $val, $time);
	}

	/* ===========================================================
	 *  Class functions
	 * =========================================================== */

	protected function directPublishEvent($key, $val, $time=null) {
		if (! extension_loaded('curl') && ! @dl(PHP_SHLIB_SUFFIX == 'so' ? 'curl.so' : 'php_curl.dll'))
			throw new SizeClientException('Curl module not found');
		$ch = curl_init();
		$message = json_encode(array('k'=>$key,'v'=>$val));
		$this->lastMessage = $message;
		$this->lastEvent = array( 'messageType' => 'event',
				'key' => $key,
				'val' => $val,
				'time' => $time );
		curl_setopt_array($ch, array(
			CURLOPT_URL              => $this->publisherRestURL,
			CURLOPT_FOLLOWLOCATION   => true,
			CURLOPT_MAXREDIRS        => 2,
			CURLOPT_RETURNTRANSFER   => true,
			CURLOPT_HEADER           => true,
			CURLINFO_HEADER_OUT      => true,
			CURLOPT_USERAGENT        => 'Size-PHP 0.1',
			CURLOPT_FAILONERROR      => false,
			CURLOPT_CUSTOMREQUEST    => 'POST',
			CURLOPT_POSTFIELDS       => $message,
			CURLOPT_HTTPHEADER       => array(
					"X-Size-AccessToken: {$this->publisherAccessToken}",
					'Content-Type: application/json'
				),
			));
		$response = curl_exec($ch);
		$request = curl_getinfo($ch, CURLINFO_HEADER_OUT);
		$headerSize = curl_getinfo($ch, CURLINFO_HEADER_SIZE);
		$responseHeaders = substr($response, 0, $headerSize);
		$responseBody = substr($response, $headerSize);
		$responseCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);

		$error = curl_error($ch);
		$errno = curl_errno($ch);
		if ($error > '')
			throw new SizeClientException("Error publishing to Size.IO platform: Curl Error #{$errno}: $error");

		switch($responseCode) {
			case 200:
				return true;
			case 204:
				return true;
			default:
				throw new SizeClientException("RESTful Publisher API returned $responseCode: $responseBody");
		}
	}

	protected function proxyPublishEvent($key, $val, $time=null) {
		switch ($this->proxyAPI['interface']) {
			case 'tcp':
				return $this->proxyPublishTCP('event', $key, $val, $time);
				break;
			case 'udp':
				return $this->proxyPublishUDP('event', $key, $val, $time);
				break;
			case 'redis':
				return $this->proxyPublishRedis('event', $key, $val, $time);
				break;
		}
	}

	protected function proxyPublishTCP($messageType, $key, $val, $time=null) {
		$message = $this->formatPlainMessage($messageType, $key, $val, $time);
		if (! $this->tcpProxy) {
			$this->tcpProxy = socket_create(AF_INET, SOCK_STREAM, SOL_TCP);
			if (! socket_connect($this->tcpProxy, $this->proxyAPI['host'], $this->proxyAPI['port']))
				throw new SizeClientException("Unable to connect to TCP Proxy");
		}
		if (! socket_send($this->tcpProxy, $message, strlen($message), MSG_EOF))
			return false;
		return true;
	}

	protected function proxyPublishUDP($messageType, $key, $val, $time=null) {
		$message = $this->formatPlainMessage($messageType, $key, $val, $time);
		if (! $this->udpProxy)
			$this->udpProxy = socket_create(AF_INET, SOCK_DGRAM, SOL_UDP);
		socket_sendto($this->udpProxy, $message, strlen($message), 0,
			$this->proxyAPI['host'], $this->proxyAPI['port']);
		return true;
	}

	protected function proxyPublishRedis($messageType, $key, $val, $time=null) {
		if (! $this->redisProxy) {
			$this->redisProxy = new Redis();
			$this->redisProxy->connect($this->proxyAPI['host'], $this->proxyAPI['port']);
		}
		$this->lastEvent = array( 'messageType' => $messageType,
				'key' => $key,
				'val' => $val,
				'time' => $time );
		$this->lastMessage = "INCRBY $key $val";
		return $this->redisProxy->incrby($key, $val);
	}

	protected function formatPlainMessage($messageType, $key, $val, $time=null) {
		$message = '';
		switch ($messageType) {
			case 'event':
				$message = ($time ? "$time|" : "") . "$key|$val";
				break;
			default:
				throw new SizeClientException("Unrecognized proxy message type: '$messageType'");
		}
		$this->lastEvent = array( 'messageType' => $messageType,
				'key' => $key,
				'val' => $val,
				'time' => $time );
		$this->lastMessage = $message;
		return $message;
	}

	protected function sanitizeKey($key) {
		if (preg_match('/[^a-zA-Z0-9_.-]/', $key))
			throw new SizeClientException("Invalid key: '$key'. Valid values are [a-zA-Z0-9_.-]");
		return $key;
	}

	protected function sanitizeVal($val) {
		if (preg_match('/[^0-9.]/', $val))
			throw new SizeClientException("Invalid key: '$val'. Valid values are [0-9.]");
		return (int) $val;
	}
	public function getLastEvent() {
		return $this->lastEvent;
	}

	public function getLastMessage() {
		return $this->lastMessage;
	}

	public function getProxyAPI() {
		return $this->proxyAPI;
	}

}

class SizeClientException extends Exception {

}
