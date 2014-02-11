<?php
$app = require __DIR__.'/../src/app.php';
$app['debug'] = false;
// trust the X-Forwarded-For* headers
use Symfony\Component\HttpFoundation\Request;
Request::trustProxyData();

// run the app
$app->run();
