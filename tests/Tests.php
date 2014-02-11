<?php
namespace log_debug_center\Tests;
/**
 * web test case
 */
//use Silex\WebTestCase;

require_once __DIR__.'/../silex.phar';

class LogTest extends \Silex\WebTestCase
{
    /**
     * bootstrap du test
     * @return void
     */
    public function createApplication()
    {
        $app = require __DIR__.'/../src/app.php';
        $app['debug'] = true;
        unset($app['exception_handler']);
        return $app;
    }

    /**
     * test de l'url
     * @return void
     */
    public function testLog()
    {
        $client = $this->createClient();
        //$client->insulate();
      
        $client->request('POST', '/log', array("ttl" => 60, 'key' => 'test', "author" => 'o_mansour', "message" => 'raoul de message')); 
        
        // 200 
        $this->assertEquals(200, $client->getResponse()->getStatusCode());
        $this->assertContains('o_mansour', $client->getResponse()->getContent());

        // 400
        $client->request('POST', '/log', array("ttl" => 60, "message" => 'raoul de message')); 
        $this->assertEquals(400, $client->getResponse()->getStatusCode());

        // check the previous entry
        $client->request('GET', '/getlog/rss/test');
        $this->assertEquals(200, $client->getResponse()->getStatusCode());
        // verification que la réponse contient 'raoul'
        $this->assertContains('raoul', $client->getResponse()->getContent());
    }
}