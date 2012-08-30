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

Direct access to the platform (no proxy) requires [PHP Curl](http://www.php.net/manual/en/book.curl.php)

## Using the PHP API

### UDP Proxy Event Publishing

Using the UDP interface to a running [size-io/size-proxy](https://github.com/size-io/size-proxy) is definitely the fastest and cheapest way to publish events to the platform.  These have essentially zero performance impact.

```php
require_once('size.php');
$size = SizeClient::getInstance();
$size->setProxyAPI('udp', '127.0.0.1', 6125);
$size->publishEvent('api.get', 1);
```

### TCP Proxy Event Publishing

Using the TCP interface to a running [size-io/size-proxy](https://github.com/size-io/size-proxy) is still pretty fast and has the added benefit of complaining if for some reason it cannot connect to the proxy server.  On a LAN or local machine it will have pretty much no measurable impact on performance.

```php
require_once('size.php');
$size = SizeClient::getInstance();
$size->setProxyAPI('tcp', '127.0.0.1', 6120);
$size->publishEvent('api.get', 1);
```

### Redis Proxy Event Publishing

Using the Redis interface to a running [size-io/size-proxy](https://github.com/size-io/size-proxy) is basically supplied for API completeness.  It has no advantages over the TCP Proxy Interface noted above.  It requires the [nicolasff/phpredis](https://github.com/nicolasff/phpredis) to be installed.  That said, it is still pretty fast and will complain if it cannot connect to the proxy server.

```php
require_once('size.php');
$size = SizeClient::getInstance();
$size->setProxyAPI('redis', '127.0.0.1', 6379);
$size->publishEvent('api.get', 1);
```

### Direct Access to the Platform

If you operate in an environment where you cannot install a local [size-io/size-proxy](https://github.com/size-io/size-proxy), you can publish events to the platform directly by consuming the [RESTful Event Publisher API](http://size.io/developer/api/publish/rest).  This requires that [PHP Curl](http://www.php.net/manual/en/book.curl.php) be installed. Simply set the proxy settings to `null` and publish the event like normal.  Depending on ambient Internet conditions, this may have a measurable impact on performance, though no different than any other RESTful API out there.

```php
require_once('size.php');
$size = SizeClient::getInstance();
$size->setProxyAPI(null);
$size->publishEvent('api.get', 1);
```
