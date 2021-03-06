<?php

namespace Millsoft\AceTool;

/**
 * AceProject CLI
 * (c) 2019 by Michael Milawski.
 */

$autoload_file        = __DIR__ . "/vendor/autoload.php";
$autoload_file_global = __DIR__ . "/../../autoload.php";

if (file_exists($autoload_file_global)) {
    //this script is installed in the composer installation
    require_once $autoload_file_global;
} else {
    if (!file_exists($autoload_file)) {
        throw new Exception("Composer autoload not found.");
    }
    require_once $autoload_file;
}

use Millsoft\AceTool\Commands\CommentCommands;
use Millsoft\AceTool\Commands\AccountCommands;
use Millsoft\AceTool\Commands\ProjectCommands;
use Millsoft\AceTool\Commands\ClockCommands;
use Millsoft\AceTool\Commands\TaskCommands;
use Millsoft\AceTool\Commands\UserCommands;

use \Rollbar\Rollbar;
use \Rollbar\Payload\Level;

class Ace
{


    /**
     * Enable error logging by Rollbar.
     */
    private static function initErrorLogging(){

        Rollbar::init(
            array(
                'access_token' => '66edf28d2ae349599b3f24d99c08467e',
                'environment' => 'production'
            )
        );

    }

    /**
     * Initialize the whole CLI system
     */
    public static function init ()
    {
        self::initErrorLogging();
        Helper::initSession();

        $versionFile = __DIR__ . "/version.txt";
        if(file_exists($versionFile)){
            $version = file_get_contents($versionFile);
        }else{
            $version = '???';
        }

        $header = <<<header
Version: $version
(c) 2019 by Michael Milawski
header;

        //Initialize a new Console AceApp:
        $console = new AceApp('AceProject CLI', $header);

        //Load all the modules that should be available in the console:
        //Modules are stored in src/commands directory.
        AccountCommands::load($console);
        ProjectCommands::load($console);
        CommentCommands::load($console);
        ClockCommands::load($console);
        TaskCommands::load($console);
        UserCommands::load($console);

        //Run the app:
        $console->run();
    }


}

Ace::init();
