<?php

namespace Millsoft\AceTool;

/**
 * AceProject CLI
 * (c) 2018 by Michael Milawski, www.millsoft.de
 */

require __DIR__ . '/vendor/autoload.php';

use Millsoft\AceTool\Commands\CommentCommands;
use Millsoft\AceTool\Commands\AccountCommands;
use Millsoft\AceTool\Commands\ProjectCommands;
use Millsoft\AceTool\Commands\ClockCommands;
use Millsoft\AceTool\Commands\TaskCommands;
use Millsoft\AceTool\Commands\UserCommands;


class Ace
{

    /**
     * Initialize the whole CLI system
     */
    public static function init ()
    {
        Helper::initSession();

        $header = <<<header
Version: 0.0.3
Last Update: 10 April 2018
(c) 2018 by Michael Milawski
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
