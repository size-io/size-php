Size.IO PHP API
==========

The Size.IO PHP API is a library which allows easy integration of the Size.IO platform into any PHP application.  It can leverage a local [size-io/size-proxy](https://github.com/size-io/size-proxy) proxy server or connect directly to the cloud platform.

Supplemental Platform API documentation and code samples are available at **http://size.io/developer**

## Pre-requisites

Depending on how you want to publish events to the platform, you may need to install an additional PHP modules.  It is recommended that you install [size-io/size-proxy](https://github.com/size-io/size-proxy) if possible:  this will provided the fastest and most efficient level of throughput.

* Using [size-io/size-proxy](https://github.com/size-io/size-proxy)
 * UDP client: no additional modules
 * TCP client: no additional modules
 * Redis client: nicolasff/phpredis needs to be installed
* Direct access to the platform (no proxy)
  * [PHP Curl](http://www.php.net/manual/en/book.curl.php) is required

