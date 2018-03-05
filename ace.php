<?php

namespace Millsoft\AceTool;

/**
 * AceProject CLI
 * By Michael Milawski
 */

require __DIR__ . '/vendor/autoload.php';

use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;


use Millsoft\AceProject\Users;
use Millsoft\AceProject\Project;
use Millsoft\AceProject\Task;
use Millsoft\AceProject\TimeSheet;
use Millsoft\AceProject\Account;


class Ace
{

    /**
     * Initialize the whole CLI system
     */
    public static function init ()
    {

        Helper::initSession();
        self::initCommands();


    }

    /**
     * Initialize all available commands in this CLI tool
     */
    private static function initCommands ()
    {


        //$console = new Application('AceProject CLI', '0.0.1 alpha');
        $console = new AceApp('AceProject CLI', '0.0.1 alpha');

        /**
         * LOGIN
         */
        $console->register('account:login')
            ->setDescription('Login to your AceProject account')
            ->setDefinition(array(
                                new InputArgument('username', InputArgument::REQUIRED, 'Username or E-Mail'),
                                new InputArgument('password', InputArgument::REQUIRED),
                                new InputArgument('subdomain', InputArgument::REQUIRED),
                            ))
            ->setCode(function (InputInterface $input, OutputInterface $output) {
                $username = $input->getArgument('username');
                $password = $input->getArgument('password');
                $subdomain = $input->getArgument('subdomain');

                Users::login($username, $password, $subdomain, true);
                Helper::checkError($output);

                Helper::setSession("subdomain", $subdomain);
                $output->writeln('<info>Logged in.</info>');

            });


        /**
         * Get Account Limitations
         */
        $console->register('account:limits')
            ->setDescription('Show current account info')
            ->setCode(function (InputInterface $input, OutputInterface $output) {
                $ac = Account::GetAccountStats();
                print_r($ac);
                $output->writeln('<info>Logged in.</info>');

            });

        /**
         * LOGOUT
         */
        $console->register('account:logout')
            ->setDescription('Logout from your AceProject account')
            ->setCode(function (InputInterface $input, OutputInterface $output) {
                Helper::delSessionKey("subdomain");
                $output->writeln('Logged off. Use account:login command to login again.');
            });

        /**
         * LIST ALL PROJECTS
         */
        $console->register('projects')
            ->setDescription('List all projects')
            ->setCode(function (InputInterface $input, OutputInterface $output) {

                $projects = Project::GetProjects();
                Helper::checkError($output);

                Helper::genTable($projects, array(
                    "PROJECT_ID"   => "Id",
                    "PROJECT_NAME" => "Project Name",
                ), $output);

            });

        /**
         * LIST ALL RUNNING CLOCKS
         */
        $console->register('clocks')
            ->setDescription('List all running clocks')
            ->setCode(function (InputInterface $input, OutputInterface $output) {

                $clocks = \Millsoft\AceProject\Timesheet::GetClocks();
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

        $console->register('clock:start')
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

                $re = \Millsoft\AceProject\Timesheet::OpenClock($params);
                Helper::checkError($output);

                //Save current task and timesheet id in session so we don't have to specify a task id later.
                //We can then simply start the clock by calling "scriptname start"
                Helper::setSession("TIMESHEET_INOUT_ID", $re->TIMESHEET_INOUT_ID);
                Helper::setSession("TASK_ID", $id_task);

                $re = Helper::getFormattedArray($re);
                $output->writeln(print_r($re, true));
            });


        $console->register('clock:stop')
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




                $re = \Millsoft\AceProject\Timesheet::CloseClock($params);
                Helper::checkError($output);

                $re = Helper::getFormattedArray($re);
                $output->writeln(print_r($re, true));
            });


        $console->register('tasks:recent')
            ->setDescription('Show current account info')
            ->setDefinition(array(
                                new InputOption('my', 'm', InputOption::VALUE_OPTIONAL, "Show only tasks assigned to me", 0),
                                new InputOption('project', 'p', InputOption::VALUE_OPTIONAL, "Show only tasks from a specific project", 0),
                                new InputOption('count', 'c', InputOption::VALUE_OPTIONAL, "Show a specific amount of tasks", 20),
                                new InputOption('days', 'd', InputOption::VALUE_OPTIONAL, "Show tasks for the x last days", 30),

                            ))
            ->setCode(function (InputInterface $input, OutputInterface $output) {

                $assignedToMe = is_null($input->getOption("my")) ? 1 : 0;
                $projectId = $input->getOption("project");
                $count = $input->getOption("count");
                $days = $input->getOption("count");

                $par = array(
                    "nbdaysmax"             => $days,
                    "nblatestmodifiedtasks" => $count,
                    "sortorder"             => "PROJECT_NAME",
                );

                //TODO: Project Sorting and Assigned Tasks are not working. Need to figure out why

                if ($projectId > 0) {
                    $par[ 'projectid' ] = $projectId;
                }

                $par[ "assignedprojectsonly" ] = $assignedToMe;

                $tasks = Task::GetRecentTasks($par);

                Helper::genTable($tasks, array(
                    "TASK_ID"      => "Id",
                    "PROJECT_NAME" => "Project",
                    "TASK_RESUME"  => "Task",
                ), $output);
            });


        $console->register('tasks:find')
            ->setDescription('Find a Task')
            ->setDefinition(array(
                                new InputArgument('searchstring', InputArgument::OPTIONAL, "TIMESHEET_INOUT_ID"),

                            ))
            ->setCode(function (InputInterface $input, OutputInterface $output) {


                $searchstring = $input->getArgument("searchstring");

                if (empty($searchstring)) {
                    $output->writeln("<error>No search string was specified!</error>");
                    die();
                }

                $params = array(
                    "texttosearch" => $searchstring,
                    "forcombo"     => false,
                );


                $tasks = Task::GetTasks($params);

                //print_r($tasks);
                //die();

                Helper::checkError($output);

                if (empty($tasks)) {
                    $output->writeln("<info>No tasks with that search string were found</info>");
                    die();
                }

                Helper::genTable($tasks, array(
                    "TASK_ID"     => "Id",
                    "PROJECT_NAME"     => "Project",
                    "TASK_STATUS_NAME" => "Status",
                    "ACTUAL_HOURS" => "Hours",
                    "TASK_RESUME" => "Task Resume",
                ), $output);


            });


        $console->register('tasks:add')
            ->setDescription('Add a new task')
            ->setDefinition(array(
                                new InputArgument('projectid', InputArgument::REQUIRED),
                                new InputArgument('summary', InputArgument::REQUIRED),

                            ))
            ->setCode(function (InputInterface $input, OutputInterface $output) {


                $projectid = (int) $input->getArgument("projectid");
                $summary = $input->getArgument("summary");


                $params = array(
                    "projectid" => $projectid,
                    "summary"   => $summary,
                );


                $re = Task::CreateTask($params);
                Helper::checkError($output);

                $re = Helper::getFormattedArray($re);
                $output->writeln(print_r($re, true));


            });

        $console->register('tasks:project')
            ->setDescription('List tasks by Project ID')
            ->setDefinition(array(
                                new InputArgument('projectid', InputArgument::REQUIRED),
                                new InputOption('starred', 's', InputOption::VALUE_OPTIONAL, 'Get only starred tasks'),
                            ))
            ->setCode(function (InputInterface $input, OutputInterface $output) {

                $project_id = $input->getArgument("projectid");

                $params = array(
                    "projectid" => $project_id,
                    "forcombo"  => true,
                );

                $starred = $input->getOption("starred");
                if (!empty($starred)) {
                    $params[ 'filtermarkedonly' ] = true;
                }

                $tasks = Task::GetTasks($params);
                Helper::checkError($output);

                if (empty($tasks)) {
                    $output->writeln("<info>No Tasks found</info>");
                    die();
                }


                Helper::genTable($tasks, array(
                    "TASK_ID"     => "Id",
                    "TASK_RESUME" => "Task Resume",
                ), $output);


            });


        $console->register('task')
            ->setDescription('Get Task Info by Task ID')
            ->setDefinition(array(
                                new InputArgument('taskid', InputArgument::REQUIRED),
                            ))
            ->setCode(function (InputInterface $input, OutputInterface $output) {

                $task_id = $input->getArgument("taskid");

                $params = array(
                    "taskid" => $task_id,
                );

                $task = Task::GetTasks($params);
                Helper::checkError($output);
                $task = $task[ 0 ];

                $desc = strip_tags($task->TASK_DESC_CREATOR);

                $re = <<<OUT
{$task->TASK_RESUME}
---------------------------------
Project: <info>{$task->PROJECT_NAME}</info>
Status: <info>{$task->TASK_STATUS_NAME}</info>
Assigned: <info>{$task->ASSIGNED}</info>
Hours: <info>{$task->ACTUAL_HOURS}</info>
$desc
OUT;

                $output->writeln($re);

            });


        $console->register('comments:list')
            ->setDescription('List all Comments for a Task')
            ->setDefinition(array(
                                new InputArgument('taskid', InputArgument::OPTIONAL),
                            ))
            ->setCode(function (InputInterface $input, OutputInterface $output) {


                $id_task = (int) $input->getArgument("taskid");
                if ($id_task == 0) {
                    //try to get task id from session file:
                    $id_task = self::getActiveTaskId($output);
                }

                $params = array(
                    "taskids"   => $id_task,
                    "plaintext" => true,
                );

                $comments = Task::GetTaskComments($params);
                Helper::checkError($output);

                if (empty($comments)) {
                    $output->writeln("<info>No Comments found</info>");
                    die();
                }

                Helper::genTable($comments, array(
                    "USERNAME"  => "User",
                    "NEW_VALUE" => "Comment",
                ), $output, array(10, 30));

            });


        $console->register('comments:add')
            ->setDescription('Add a comment to a task')
            ->setDefinition(array(
                                new InputArgument('comment', InputArgument::REQUIRED),
                                new InputArgument('taskid', InputArgument::OPTIONAL),
                            ))
            ->setCode(function (InputInterface $input, OutputInterface $output) {


                $comment = $input->getArgument("comment");
                $id_task = (int) $input->getArgument("taskid");

                if ($id_task == 0) {
                    //try to get task id from session file:
                    $id_task = self::getActiveTaskId($output);
                }


                $params = array(
                    "taskid"      => $id_task,
                    "addcomments" => $comment,
                );

                $re = Task::SaveTask($params);
                Helper::checkError($output);


                $output->writeln("<info>Comment added to task {$id_task}</info>");

            });


        $console->register('get:statuses')
            ->setDescription('Get a list of usable statuses')
            ->setCode(function (InputInterface $input, OutputInterface $output) {

                $statuses = Task::GetTaskStatuses();
                Helper::checkError($output);

                Helper::genTable($statuses, array(
                    "COMPLETED_STATUS" => "Id",
                    "TASK_STATUS_NAME" => "Status Name",
                ), $output);

            });

        $console->register('get:activetask')
            ->setDescription('Get current active task from session.json')
            ->setCode(function (InputInterface $input, OutputInterface $output) {

                $ses = Helper::getSession();
                if (isset($ses[ "TASK_ID" ])) {

                    $params = array(
                        "taskid" => $ses[ 'TASK_ID' ],
                    );


                    $info = Task::GetTaskInfo($ses[ 'TASK_ID' ]);
                    Helper::checkError($output);

                    if (empty($info)) {
                        $output->writeln("<error>No task info for this task ID found</error>");
                    } else {

                        $re = Helper::getFormattedArray($info);
                        $output->writeln(print_r($re, true));

                    }

                } else {
                    $output->writeln("<info>No active task found. Use set:task or start a timer to set a new task</info>");
                }
            });


        $console->register('set:activetask')
            ->setDescription('Sets the active task')
            ->setDefinition(array(
                                new InputArgument('taskid', InputArgument::REQUIRED)
                            ))
            ->setCode(function (InputInterface $input, OutputInterface $output) {

                $taskid = (int) $input->getArgument("taskid");


                if ($taskid == 0) {
                    $output->writeln("<error>No task ID was specified</error>");
                    die();
                }

                //check if the task exists:
                $info = Task::GetTaskInfo($taskid);
                Helper::checkError($output);

                //finally, set the active task:
                Helper::setSession("TASK_ID", $taskid);

                $output->writeln("<info>Task was set to active.</info>");

            });


        $console->register('get:work')
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


        $console->register('set:status')
            ->setDescription('Sets a Task as complete "todo", "inprogress", "complete"')
            ->setDefinition(array(
                                new InputArgument('taskid', InputArgument::OPTIONAL, "Task ID - if no id is specified, active task will be used", 'nope'),
                                new InputArgument('status', InputArgument::OPTIONAL, "Status. : todo, inprogress, toverify, complete", 0)
                            ))
            ->setCode(function (InputInterface $input, OutputInterface $output) {


                $taskid = (int) $input->getArgument("taskid");
                $set_status = $input->getArgument("status");


                if ($taskid == 0) {
                    //Try to get current active task because no task was specified
                    $taskid = (int) Helper::getActiveTaskId($output);
                    if (!$taskid) {
                        $output->writeln("<error>No task ID was specified or could be found in the active task.</error>");
                        die();
                    }
                }

                if ($set_status == 'nope') {
                        $output->writeln("<error>You need to specify the status:  todo, inprogress, toverify or complete</error>");
                        die();
                }

                $taskInfo = Task::GetTaskInfo($taskid);
                $project_id = $taskInfo->PROJECT_ID;

                //check if the task exists:
                $statuses = Task::GetTaskStatuses(array(
                                                      "projectid" => $project_id,
                                                  ));

                //print_r($statuses);
                Helper::checkError($output);

                $mapStatus = array(
                    "todo" => "To do",
                    "inprogress" => "In Progress",
                    "progress" => "In Progress",
                    "completed" => "Completed",
                    "done" => "Completed",
                    "toverify" => "To Verify",
                    "verify" => "To Verify",
                );

                if(isset($mapStatus[$set_status])){
                    $searchStatus = $mapStatus[$set_status];
                }else{
                    $output->writeln("<error>Status {$set_status} seems to be invalid!</error>");
                }
                //find the Completed Task:
                $status_id = 0;
                foreach($statuses as $status){
                    if($status->TASK_STATUS_NAME ==  $searchStatus ){
                        $status_id = $status->TASK_STATUS_ID;
                    }
                }

                if($status_id == 0){
                    $output->writeln("<info>No Status ID was found for project {$project_id}</info>");
                    die();
                }

                Task::SaveTask(array(
                    "taskid" => $taskid,
                    "statusid" => $status_id,
                    "notify" => false
                               ));

                //finally, set the active task:
                Helper::setSession("TASK_ID", $taskid);

                $output->writeln("<info>Task status was set to '{$searchStatus}'.</info>");

            });


        //*****************************************************//
        //RUN THE CLI!
        $console->run();

    }


}

Ace::init();

