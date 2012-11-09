Size.IO PHP API
==========

The Size.IO PHP API is a library which allows easy integration of the Size.IO platform into any PHP application.  It can leverage a local [size-io/size-proxy](https://github.com/size-io/size-proxy) proxy server or connect directly to the cloud platform.

Supplemental Platform API documentation and code samples are available at **http://size.io/developer**

## Pre-requisites

Depending on how you want to publish events to the platform, you may need to install an additional PHP modules.  It is recommended that you install [size-io/size-proxy](https://github.com/size-io/size-proxy) if possible:  this will provided the fastest and most efficient level of throughput.

Using [size-io/size-proxy](https://github.com/size-io/size-proxy):

 * UDP client interface: no additional modules
 * TCP client interface: no additional modules
 * Redis client interface: [nicolasff/phpredis](https://github.com/nicolasff/phpredis) needs to be installed

Direct access to the platform (no proxy) requires [Curl](http://www.php.net/manual/en/book.curl.php)

## Using the PHP API

### UDP Proxy Event Publishing

Using the UDP interface to a running [size-io/size-proxy](https://github.com/size-io/size-proxy) is definitely the fastest and cheapest way to publish events to the platform.  These have essentially zero performance impact.

Instantiate the object like so:
```php
require_once('size.php');
$size = SizeClient::getInstance();
$size->setProxyAPI('udp', '127.0.0.1', 6125);
```
Then at the relevant locations in your software, publish individual events like so:
```php
$size->publishEvent('my.event', 1);  // will accept any integer
```

### TCP Proxy Event Publishing

Using the TCP interface to a running [size-io/size-proxy](https://github.com/size-io/size-proxy) is still pretty fast and has the added benefit of complaining if for some reason it cannot connect to the proxy server.  On a LAN or local machine it will have essentially no measurable impact on performance.

Instantiate the object like so:
```php
require_once('size.php');
$size = SizeClient::getInstance();
$size->setProxyAPI('tcp', '127.0.0.1', 6120);
```
Then at the relevant locations in your software, publish individual events like so:
```php
$size->publishEvent('my.event', 1);  // will accept any integer
```

### Redis Proxy Event Publishing

Using the Redis interface to a running [size-io/size-proxy](https://github.com/size-io/size-proxy) is basically supplied for API completeness.  For publishing events, it has no advantages over the TCP Proxy Interface noted above.It is, however, well suited for subscribing to events.  It requires [nicolasff/phpredis](https://github.com/nicolasff/phpredis) to be installed.  On a LAN or local machine, it is fast and will complain if it cannot connect to the proxy server.

Instantiate the object like so:
```php
require_once('size.php');
$size = SizeClient::getInstance();
$size->setProxyAPI('redis', '127.0.0.1', 6379);
```
Then at the relevant locations in your software, publish individual events like so:
```php
$size->publishEvent('my.event', 1);  // will accept any integer
```

### Direct Access to the Platform

If you operate in an environment where you cannot install a local [size-io/size-proxy](https://github.com/size-io/size-proxy), you can publish events to the platform directly by consuming the [RESTful Event Publisher API](http://size.io/developer/api/publish/rest).  This requires that [Curl](http://www.php.net/manual/en/book.curl.php) be installed. Simply set the proxy settings to `null` and publish the event like normal.  Depending on ambient Internet conditions, this may have a measurable impact on performance, though no more so than any other RESTful API out there.

Instantiate the object like so:
```php
require_once('size.php');
$size = SizeClient::getInstance();
$size->setProxyAPI(null);
```
Then at the relevant locations in your software, publish individual events like so:
```php
$size->publishEvent('my.event', 1);  // will accept any integer
```
