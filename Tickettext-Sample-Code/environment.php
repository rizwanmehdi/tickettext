<?php
    /*
     * Bootstrap the loading of the environment based on the domain information
     *
     * */
	 /*
    if(PHP_SAPI == 'cli' || PHP_SAPI == 'cli')
    {
        defined('IS_CLI') || define('IS_CLI', true);
    }


    if (!defined('IS_CLI') && ($_SERVER) && ($_SERVER['HTTP_HOST']))
    {
         defined('IS_CLI') || define('IS_CLI', false);

        // parse the server name
        $urlParts = explode('.', $_SERVER['HTTP_HOST']);

        if (count($urlParts) > 0)
        {
            if($_SERVER['REMOTE_ADDR']== '192.168.1.41')
            {
                switch ($urlParts[0])
                {
                    case 'bsc';
                    case 'demo':
                    case 'www':
                    case 'localdev':
                        $demoEnvironment = 'localdev';
                        break;
                    case 'oldstaging':
                    case 'staging':
                        $demoEnvironment = 'staging';
                        break;
                    default:
                        $demoEnvironment = 'live';
                }
            }
            else
            {
                switch ($urlParts[0])
                {
                    case 'localdev':
                        $demoEnvironment = 'localdev';
                        break;
                    case 'dev':
                        $demoEnvironment = 'dev';
                        break;
                    case 'oldstaging':
                    case 'staging':
                        $demoEnvironment = 'staging';
                        break;
                    default:
                        $demoEnvironment = 'live';
                }
            }

        }
        else
        {
            $demoEnvironment = 'live';
        }
    }
    else{
        $demoEnvironment = 'live';
    }*/

//die($demoEnvironment);
defined('IS_CLI') || define('IS_CLI', false);

    require 'environment.dev.php';
