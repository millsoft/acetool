<?php
namespace Millsoft\AceTool\Commands;

use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;

use Millsoft\AceProject\Project;
use Millsoft\AceProject\Timesheet;
use Millsoft\AceProject\Task;

use Millsoft\AceTool\Helper;


class ClockCommands extends Commands{


    //List all running clocks
    public static function commandClocks(){
        self::$console->register('clocks')
            ->setDescription('List all running clocks')
            ->setCode(function (InputInterface $input, OutputInterface $output) {

                $clocks = Timesheet::GetClocks();
                Helper::checkError($output);

                if (empty($clocks)) {
                    $output->writeln("<info>No running clocks</info>");
                    die();
                }


                foreach ($clocks as &$clock) {
                    $clock = Helper::getFormattedArray($clock);
                }

                $output->write(print_r($clocks, true));

            });
    }

    //Start clock for a task
    public static function commandStart(){

        self::$console->register('clock:start')
            ->setDescription('Start a Clock for a given task')
            ->setDefinition(array(
                                new InputArgument('taskid', InputArgument::OPTIONAL, "Task ID. If not specified, the task ID in session.json will be used."),
                                new InputOption('comment', 'c', InputOption::VALUE_OPTIONAL, "Comment. Will be added to the task comment"),
                            ))
            ->setCode(function (InputInterface $input, OutputInterface $output) {

                $id_task = (int) $input->getArgument("taskid");
                $comment = $input->getOption("comment");

                if ($id_task == 0) {
                    //try to get task id from session file:
                    $id_task = Helper::getActiveTaskId($output);
                }

                $params = array(
                    "taskid"   => $id_task,
                );

                if(!empty($comment)){
                    //User specified a comment, let it pass to the API
                    $params['comments'] = !empty($comment) ? utf8_encode($comment) : null;
                }

                $re = Timesheet::OpenClock($params);
                Helper::checkError($output);

                //Save current task and timesheet id in session so we don't have to specify a task id later.
                //We can then simply start the clock by calling "scriptname start"
                Helper::setSession("TIMESHEET_INOUT_ID", $re->TIMESHEET_INOUT_ID);
                Helper::setSession("TASK_ID", $id_task);

                $re = Helper::getFormattedArray($re);
                $output->writeln(print_r($re, true));
            });
    }


    //Stop a running clock
    public static function commandStop(){
        self::$console->register('clock:stop')
            ->setDescription('Stop a running Clock')
            ->setDefinition(array(
                                new InputArgument('timesheetid', InputArgument::OPTIONAL, "TIMESHEET_INOUT_ID"),
                                new InputOption('comment', 'c', InputOption::VALUE_OPTIONAL, "Comment - Will be added to the timesheet entry"),

                            ))
            ->setCode(function (InputInterface $input, OutputInterface $output) {

                $timesheetinoutid = (int) $input->getArgument("timesheetid");
                $comment = $input->getOption("comment");


                //Get the timesheet_inout_id:
                if ($timesheetinoutid == 0) {
                    //try to get the last id from file:
                    $sess = Helper::getSession();
                    if (isset($sess[ 'TIMESHEET_INOUT_ID' ])) {
                        $timesheetinoutid = (int) $sess[ 'TIMESHEET_INOUT_ID' ];
                    }
                }

                if ($timesheetinoutid == 0) {
                    $output->writeln("<error>TIMESHEET_INOUT_ID was not specified</error>");
                }

                $params = array(
                    "timesheetinoutid" => $timesheetinoutid
                );

                if(!empty($comment)){
                    //User specified a comment, let it pass to the API
                    $params['comments'] = !empty($comment) ? utf8_encode($comment) : null;
                }


                $re = Timesheet::CloseClock($params);
                Helper::checkError($output);

                $re = Helper::getFormattedArray($re);
                $output->writeln(print_r($re, true));
            });
    }

    public static function commandGetWork(){
        self::$console->register('get:work')
            ->setDescription('Get a week')
            ->setCode(function (InputInterface $input, OutputInterface $output) {


                $par = array(
                    "rowsperpage"       => 5,
                    "pagenumber"        => 1,
                    "timesheetdatefrom" => "2017-08-31"
                );

                $items = Timesheet::GetMyWorkItems($par);

                foreach ($items as $item) {
                    $item = Helper::getFormattedArray($item);
                    $output->writeln(print_r($item, true));
                }

                die();

                //check if the task exists:
                $info = Task::GetTaskInfo($taskid);
                Helper::checkError($output);

                //finally, set the active task:
                Helper::setSession("TASK_ID", $taskid);

                $output->writeln("<info>Task was set to active.</info>");

            });
    }



}