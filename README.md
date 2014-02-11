README.md
=========

## Usage ##

**Dont use ! use [kibana](http://www.elasticsearch.org/overview/kibana/) or other real tool.**

**Attention, le LDC est dédié au debug ! À plus de 100 r/s ça va saturer grave et ça n'a aucun sens.**

### Poster un message ###

Les champs author et message sont libres.

Le champ key représente la clé de la liste rédis ds laquelle les messages vont être sauvés. Idéalement il faut mettre une chaine qui passe bien dans une url.

**à l'arrache**

    $> curl -d "author=o_mansour&key=test&message=raoul" -L "http://domain/index.php/log"

ou mieux en précisant le max time

    $> curl --max-time 1 -d "author=o_mansour&key=ping.pong&message=un_message" -L "http://domain/index.php/log"


**en php**

    $url = 'http://domain/index.php/log';
    $fields = array(
        'author' => 'raoul',
        'message' => (string) $message,
        'key' => 'ingest_debug'
    );


    $ch = curl_init();
    curl_setopt($ch,CURLOPT_POSTFIELDS, $fields);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_TIMEOUT, 0.5); // 0.5 s max
    curl_exec($ch);
    curl_close($ch);

**avec guzzle**

   TODO

### Poster un message (via GET) ###

**déconseillé, faite gaffe à vos inputs**

    $> curl --max-time 1 -L "http://domain/index.php/log?author=o_mansour&key=ping.pong&message=un_message"

**en dev**

    $> curl --max-time 1 -L "http://o_mansour.dev.domain/index_dev.php/log?author=o_mansour&key=ping.pong&message=un_message" -u o_mansour:p@ssword

### Consulter les logs ###

Admettons que votre clé de message est raoul

* au format html :

    http://domain/getlog/html/raoul

* au format json :

    http://domain/getlog/json/raoul

* au format csv :

    http://domain/getlog/csv/raoul


* ou sinon :

    http://domain/

## deploy

`deploy prod service-log-debug-center`

## TODO

* ajouter la notif xmpp jabber - abhinavsingh/jaxl
* degug la nav avec index_dev
* more output formats
* refactor config\params::getRedisPrefix().$key$
* formatter les dates
