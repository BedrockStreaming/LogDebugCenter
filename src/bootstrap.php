<?php
/**
 * bootstrap avec une classe un peu pourrite
 * @author o_mansour
 *
 */

namespace log_debug_center\config;

/**
 * les params de config
 */
class bootstrap
{
    /**
     * renvoi la conf du server Redis
     * @param string $env env (prod , dev)
     *
     * @return array
     */
    public static function getServerConfig($env = 'prod')
    {
        if ('prod' === $env) {
            return array(
                'php51' => array (
                    'ip' => 'localhost',
                    'port' => 6379,
                    ));
        }

        return array(
                'php51' => array (
                    'ip' => 'prod.fr',
                    'port' => 6379,
                    ));
    }

    /**
     * renvoi le namespace utilisé dans Redis
     * @return string
     */
    public static function getNamespace()
    {
        return 'log_debug_center app';
    }

    /**
     * le prefix pour les listes ordonnées
     * @return string
     */
    public static function getRedisPrefix()
    {
        return 'log_debug_center_';
    }

    /**
     * délimiteur arbitraire bidon
     * @return string
     */
    public static function getDelimiter()
    {
        return ' \| ';
    }

    /**
     * nb of records per page
     * @return int
     */
    public static function getRecordsPerPage()
    {
        return 100;
    }
}
