<?php
namespace Millsoft\AceTool\Commands;

use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;

use Millsoft\AceProject\Project;
use Millsoft\AceProject\Task;
use Millsoft\AceProject\Users;

use Millsoft\AceTool\Helper;

class TaskCommands extends Commands{


    public static function commandRecent(){
        self::$console->register('tasks:recent')
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
    }

    public static function commandFind(){
        self::$console->register('tasks:find')
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
    }

    public static function commandAdd(){
        self::$console->register('tasks:add')
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
    }

    public static function commandProject(){
      self::$console->register('tasks:project')
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
    }

    public static function commandTask(){
      self::$console->register('task')
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
    }


    public static function commandStatuses(){
        self::$console->register('get:statuses')
            ->setDescription('Get a list of usable statuses')
            ->setCode(function (InputInterface $input, OutputInterface $output) {

                $statuses = Task::GetTaskStatuses();
                Helper::checkError($output);

                Helper::genTable($statuses, array(
                    "COMPLETED_STATUS" => "Id",
                    "TASK_STATUS_NAME" => "Status Name",
                ), $output);

            });

    }

    public static function commandGetActiveTask(){

        self::$console->register('get:activetask')
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
    }


    //set the active task to a specific task
    //If an active task is specified you can start / stop the task by simply
    //executing ace clock:start or ace clock:stop
    //Hint: Active task is set automatically when you specify a task with ace:clock start <id>
    public static function commandSetActiveTask(){
       self::$console->register('set:activetask')
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
    }


    //Set the status for a task (eg. todo, in progress, complete)
    public static function commandSetStatus(){

        self::$console->register('set:status')
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

    }

    //Assign a task to a specific user:
    public static function commandAssign(){

        self::$console->register('tasks:assign')
            ->setDescription('Assign a task to a user')
            ->setDefinition(array(
                    new InputArgument('taskid', InputArgument::REQUIRED, "Task ID"),
                    new InputArgument('user', InputArgument::REQUIRED, "User ID")
                ))
            ->setCode(function (InputInterface $input, OutputInterface $output){
                $taskid = (int) $input->getArgument("taskid");
                $user = $input->getArgument("user");

                if(!is_numeric($user)){
                    //user didn't enter an id, search for the id by string:
                    $usr = new UserCommands();
                    $user = $usr->getUseridFromString($user);
                }

                $params = array(
                    "taskid" => $taskid,
                    "userid" => $user,
                    "modifyrecurrency" => 0,
                    "isassignee" => true,
                );
                $re = Task::AssignUserTask($params);
                print_r($re);

            });
    }


    //Unassign a user from a task:
    public static function commandUnAssign(){

        self::$console->register('tasks:unassign')
            ->setDescription('Remove assigned user from a task')
            ->setDefinition(array(
                    new InputArgument('taskid', InputArgument::REQUIRED, "Task ID"),
                    new InputArgument('user', InputArgument::REQUIRED, "User ID")
                ))
            ->setCode(function (InputInterface $input, OutputInterface $output) {
                $taskid = (int) $input->getArgument("taskid");
                $user = $input->getArgument("user");

                if(!is_numeric($user)){
                    //user didn't enter an id, search for the id by string:
                    $usr = new UserCommands();
                    $user = $usr->getUseridFromString($user);
                }

                $params = array(
                    "taskid" => $taskid,
                    "userid" => $user,
                    "modifyrecurrency" => 0,
                    "isassignee" => false,
                );
                $re = Task::AssignUserTask($params);
                print_r($re);

            });
    }


}