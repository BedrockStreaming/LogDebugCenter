<?php
// tests/bootstrap.php
 
require_once __DIR__.'/../silex.phar';
 
use Symfony\Component\ClassLoader\UniversalClassLoader;
 
$loader = new UniversalClassLoader();
 
$loader->registerNamespace('log_debug_center', __DIR__.'/../src');
$loader->register();
 
return $loader;