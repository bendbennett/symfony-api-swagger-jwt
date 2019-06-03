<?php

use App\Kernel;
use Symfony\Component\HttpFoundation\Request;

// die(var_dump($_SERVER['APP_ENV']));

/**
 * @var Composer\Autoload\ClassLoader
 */
require dirname(__DIR__).'/vendor/autoload.php';

$kernel = new Kernel('prod', false);
Request::setTrustedProxies(['192.0.0.1', '10.0.0.0/8'], Request::HEADER_X_FORWARDED_ALL);

// When using the HttpCache, you need to call the method in your front controller instead of relying on the configuration parameter
//Request::enableHttpMethodParameterOverride();
$request = Request::createFromGlobals();
$response = $kernel->handle($request);
$response->send();
$kernel->terminate($request, $response);
