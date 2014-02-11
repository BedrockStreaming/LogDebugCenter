<?php
// define env dev
define('ENV', 'dev');

$app = require __DIR__.'/../src/app.php';
$app['debug'] = true;
// trust the X-Forwarded-For* headers
use Symfony\Component\HttpFoundation\Request;
Request::trustProxyData();

// run the app
$app->run();
