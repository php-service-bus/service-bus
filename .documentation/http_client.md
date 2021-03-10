####  What is it?
Abstraction over Http client implementations.

#### Installation
```
composer req php-service-bus/http-client
```

#### Usage

Request parameters are formed using the [HttpRequest](https://github.com/php-service-bus/http-client/blob/v5.0/src/HttpRequest.php) structure.  
The http request execution adapter must implement the [HttpClient](https://github.com/php-service-bus/http-client/blob/v5.0/src/HttpClient.php) interface

Currently only [amphp/http-client](https://github.com/amphp/http-client) is [implemented](https://github.com/php-service-bus/http-client/blob/v5.0/src/Artax/ArtaxHttpClient.php).

#### Examples

##### Simple GET request:
```php
$client = new ArtaxHttpClient();

Loop::run(
    static function() use ($client)
    {
        /** @var \GuzzleHttp\Psr7\Response $response */
        $response = yield $client->execute(HttpRequest::get('https://github.com/php-service-bus/'));

        echo $response->getStatusCode();
    }
);
```

File download:

```php
$client = new ArtaxHttpClient();

Loop::run(
    static function() use ($client)
    {
        /** @var string $filePath */
        $filePath = yield $client->download(
            'https://github.com/mmasiukevich/service-bus/archive/v3.0.zip',
            \sys_get_temp_dir(),
            'service_bus.zip'
        );

        echo $filePath;
    }
);
```
or
```php
Loop::run(
    static function()
    {
        /** @var string $filePath */
        $filePath = yield downloadFile(
            'https://github.com/mmasiukevich/service-bus/archive/v3.0.zip',
            \sys_get_temp_dir(),
            'service_bus.zip'
        );

        echo $filePath;
    }
);
```