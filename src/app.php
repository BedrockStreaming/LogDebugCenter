<?php
/**
 * main app file
 * @author o_mansour
 */

// autoloading vendor
require_once __DIR__.'/../vendor/autoload.php';

// require silex
require_once __DIR__.'/bootstrap.php';

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

use \log_debug_center\config;
use \M6\Component\Redis;

if (!defined('ENV')) {
    define('ENV', 'prod');
}

$app = new Silex\Application();

/**
 * Redis service (shared)
 */
$app['Redis'] = $app->share(function () {
    return new Redis\DB (array(
        'timeout' => 2,
        'server_config' => config\bootstrap::getServerConfig(ENV),
    ));
});

/**
 * twig
 * $app['twig']
 */
$app->register(new Silex\Provider\TwigServiceProvider(), array(
    'twig.path'       => __DIR__.'/../views',
));

$doLog = function (Request $request) use ($app) {
    // needed : message, author, key
    $params_needed = array('message', 'author', 'key');
    $log_param = array();
    foreach ($params_needed as $param) {
        $log_param[$param] = null;
        if (!$request->get($param)) { // assign data to log_param

            return new Response('Missing parameter '.$param, 400);
        } else {
            $log_param[$param] = $app->escape($request->get($param));
        }
    }

    // urlencode the key
    $log_param['key'] = urlencode($log_param['key']);
    // sanitize key
    $log_param['key'] = str_replace(array('/', '%5C'), array('', ''), $log_param['key']);

    // format the message
    $message = date('Ymd His').config\bootstrap::getDelimiter().str_pad($log_param['author'], 15).
        config\bootstrap::getDelimiter().$log_param['message'];

    // Persist data to Redis
    if ($app['Redis']->lPush(config\bootstrap::getRedisPrefix().$log_param['key'], $message) === false) {
        throw new Exception("erreur saving in redis : lPush");
    }

    // trim the list to 1000 items
    if ($app['Redis']->lTrim(config\bootstrap::getRedisPrefix().$log_param['key'], 0, 999) === false) {
        throw new Exception("erreur saving in redis : lTrim");
    }

    // expire ds 10 Jours
    $app['Redis']->expire(config\bootstrap::getRedisPrefix().$log_param['key'], 30*24*3600);

    // return message
    if ($app['debug'] === true) {
        $msg_string = 'Data saved'."\n\n"."from : ".$log_param['author']."\n".
        "key : ".$log_param['key']."\n".
        "date : ".date('Ymd His')."\n".
        "message : ".$log_param['message'];
    } else {
        $msg_string = '';
    }

    return $msg_string;
};

/**
 * log method - log to Redis
 */
$app->post('/log', function (Request $request) use ($app, $doLog) {
    // Get POST data or 400 HTTP response
    $msg_string = $doLog($request);

    return new Response($msg_string, 200);
});

/**
 * log method - log to Redis
 */
$app->get('/log', function (Request $request) use ($app, $doLog) {
    // Get GET data or 400 HTTP response
    $msg_string = $doLog($request);

    return new Response($msg_string, 200);
});

/**
 * restition des logs
 * format in ('rss', 'csv', 'html') ??
 */
$app->get('/getlog/{format}/{key}/{current_page}', function ($format, $key, $current_page) use ($app) {
    /** pagination **/
    $range = config\bootstrap::getRecordsPerPage();
    $start = $current_page * $range;
    $end = $start + $range - 1;

    $nb_records = $app['Redis']->lLen(config\bootstrap::getRedisPrefix().$key);

    if ($app['Redis']->type(config\bootstrap::getRedisPrefix().$key) === 'list') {
        $records = $app['Redis']->lRange(config\bootstrap::getRedisPrefix().$key, $start, $end); // 1000 entrées max ?
    } else {
        $records = array();
    }

    $formated_records = array();
    // format records
    foreach ($records as $record) {
        $t = explode(config\bootstrap::getDelimiter(), $record);
        if (count($t) == 3) {
            list ($date, $author, $message) = $t;
        } else {
            continue; // wrong format ?
        }
        $formated_records[] = array(
            'date' => $date,
            'author' => $author,
            'message' => $message
            );
    }
    // TODO : check if the template really exist

    // use twig according the format
    $to_return =  $app['twig']->render('getlog_'.$app->escape($format).'.twig', array(
        'records' => $formated_records,
        'key' => $key,
        'nb_displayed_records' => count($formated_records),
        'nb_records' => $nb_records,
        'start' => $start,
        'end' => $end,
        'current_page' => $current_page,
        'max_page' => (int) ($nb_records / $range)
    ));

    return new Response($to_return, 200);
})->value('current_page', 0)->assert('current_page', '\d+');

/**
 * list all key in the db and count the records
 */
$app->get('/', function () use ($app) {

    $info = $app['Redis']->info();

    $key_records = array();
    $keys = $app['Redis']->keys(config\bootstrap::getRedisPrefix().'*');
    $keys = array_reverse($keys);
    foreach ($keys as $key) {
        if ($app['Redis']->type($key) === 'list') {
            $last_log_date = $last_log_author = $last_log_message = '';
            $last_log = $app['Redis']->lRange($key, 0, 1);
            if (isset($last_log[0])) {
                $t = explode(config\bootstrap::getDelimiter(), $last_log[0]);
                if (count($t) == 3) {
                    list ($last_log_date, $last_log_author, $last_log_message) = $t;

                }
            }
            $key_records[] = array (
                'key' => str_replace(config\bootstrap::getRedisPrefix(), '', $key), // todo : supression par regexp sur le début de la chaine
                'realkey' => $key,
                'count' => $app['Redis']->lLen($key),
                'last_log_date' => $last_log_date,
                'last_log_author' => $last_log_author,
                'last_log_message' => $last_log_message
                );
        }
    }

    $to_return = $app['twig']->render('index.twig', array(
        'key_records' => $key_records,
        'nb_keys' => count($key_records),
        'used_memory_human' => $info['used_memory_human'],
        'used_memory_peak_human' =>$info['used_memory_peak_human']
    ));

    return new Response($to_return, 200);
});

/**
* clear all the records under a key
*/
$app->get('/purge/{key}', function ($key) use ($app) {
    if ($app['Redis']->type(config\bootstrap::getRedisPrefix().$key) !== 'list') {
        throw new Exception("no lKey under : ".config\bootstrap::getRedisPrefix().$key
            ." type : ".$app['Redis']->type(config\bootstrap::getRedisPrefix().$key)
            );
    }
    $nb_keys = $app['Redis']->lLen(config\bootstrap::getRedisPrefix().$key);
    if ($app['Redis']->del(config\bootstrap::getRedisPrefix().$key)) { // suppression
        // confirmation message
        $to_return = $app['twig']->render('purge_confirmation.twig', array(
        'key' => $key,
        'nb_keys' => $nb_keys
        ));

        return new Response($to_return, 200);
    } else {
        throw new Exception("unable to delete : ".config\bootstrap::getRedisPrefix().$key);
    }

});

// todo : error handling

return $app;
