<?php

	namespace Millsoft\AceTool;

	/**
	 * AceProject CLI
	 * By Michael Milawski
	 */

	require __DIR__ . '/vendor/autoload.php';

	use Symfony\Component\Console\Application;
	use Symfony\Component\Console\Input\InputInterface;
	use Symfony\Component\Console\Output\OutputInterface;
	use Symfony\Component\Console\Input\InputArgument;
	use Symfony\Component\Console\Input\InputOption;
	use Symfony\Component\Console\Helper\Table;

	use Millsoft\AceProject\AceProject;
	use Millsoft\AceProject\Users;
	use Millsoft\AceProject\Project;
	use Millsoft\AceProject\Task;
	use Millsoft\AceProject\TimeSheet;


	class Ace
	{

		private static $sessionFile = "session.json";
		private static $session = array();

		/**
		 * Get a local config from session file
		 * @return array|mixed
		 */
		private static function getSession()
		{
			if ( !file_exists(self::$sessionFile)) {
				return array();
			}

			$s = file_get_contents(self::$sessionFile);
			$sess = json_decode($s, true);

			return $sess;
		}

		/**
		 * Set local session file config
		 *
		 * @param      $key
		 * @param null $val
		 */
		private static function setSession($key, $val = null)
		{
			$sess = self::getSession();

			$sess[$key] = $val;
			file_put_contents(self::$sessionFile, json_encode($sess));
		}

		/**
		 * Delete a key from session file
		 *
		 * @param $key
		 */
		private static function delSessionKey($key)
		{
			$sess = self::getSession();

			if (isset($sess[$key])) {
				unset($sess[$key]);
			}
			file_put_contents(self::$sessionFile, json_encode($sess));
		}


		/**
		 * Initialize the whole CLI system
		 */
		public static function init()
		{

			//get subdomain from session file:
			self::$session = self::getSession();

			if (isset(self::$session['subdomain'])) {
				AceProject::$subdomain = self::$session['subdomain'];
			}

			self::initCommands();


		}

		private static function genTable($data, $cols, $output, $columnWidths = array())
		{

			$rows = array();
			$headers = array();


			$colMap = array_keys($cols);


			foreach ($data as $row) {
				//$output->writeln($project->PROJECT_ID . "\t" . $project->PROJECT_NAME);

				$r = array();
				foreach ($colMap as $key) {
					$r[] = $row->$key;
				}

				$rows[] = $r;
			}

			$table = new Table($output);



			$table
				->setHeaders($cols)
				->setRows($rows);


			if(!empty($columnWidths)){
				$table->setColumnWidths($columnWidths);
			}


			$table->render();

		}

		/**
		 * Initialize all available commands in this CLI tool
		 */
		private static function initCommands()
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
					self::checkError($output);

					self::setSession("subdomain", $subdomain);
					$output->writeln('<info>Logged in.</info>');

				});

			/**
			 * LOGOUT
			 */
			$console->register('account:logout')
				->setDescription('Logout from your AceProject account')
				->setCode(function (InputInterface $input, OutputInterface $output) {
					self::delSessionKey("subdomain");
					$output->writeln('Logged off. Use account:login command to login again.');
				});

			/**
			 * LIST ALL PROJECTS
			 */
			$console->register('projects')
				->setDescription('List all projects')
				->setCode(function (InputInterface $input, OutputInterface $output) {

					$projects = Project::GetProjects();
					self::checkError($output);

					self::genTable($projects, array(
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
					self::checkError($output);

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

					$id_task = (int)$input->getArgument("taskid");
					$comment = $input->getOption("comment");

					if ($id_task == 0) {
						//try to get task id from session file:
						$id_task = self::getActiveTaskId($output);
					}

					$params = array(
						"taskid"   => $id_task,
						"comments" => !empty($comment) ? utf8_encode($comment) : null,
					);
					$re = \Millsoft\AceProject\Timesheet::OpenClock($params);
					self::checkError($output);

					//Save current task and timesheet id in session so we don't have to specify a task id later.
					//We can then simply start the clock by calling "scriptname start"
					self::setSession("TIMESHEET_INOUT_ID", $re->TIMESHEET_INOUT_ID);
					self::setSession("TASK_ID", $id_task);

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

					$timesheetinoutid = (int)$input->getArgument("timesheetid");
					$comment = $input->getOption("comment");


					//Get the timesheet_inout_id:
					if ($timesheetinoutid == 0) {
						//try to get the last id from file:
						$sess = self::getSession();
						if (isset($sess['TIMESHEET_INOUT_ID'])) {
							$timesheetinoutid = (int)$sess['TIMESHEET_INOUT_ID'];
						}
					}

					if ($timesheetinoutid == 0) {
						$output->writeln("<error>TIMESHEET_INOUT_ID was not specified</error>");
					}


					$params = array(
						"timesheetinoutid" => $timesheetinoutid,
						"comments"         => !empty($comment) ? utf8_encode($comment) : null,
					);


					$re = \Millsoft\AceProject\Timesheet::CloseClock($params);
					self::checkError($output);

					$re = Helper::getFormattedArray($re);
					$output->writeln(print_r($re, true));
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
						"forcombo"     => true,
					);


					$tasks = Task::GetTasks($params);
					self::checkError($output);


					self::genTable($tasks, array(
						"TASK_ID"     => "Id",
						"TASK_RESUME" => "Task Resume",
					), $output);


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
					if ( !empty($starred)) {
						$params['filtermarkedonly'] = true;
					}

					$tasks = Task::GetTasks($params);
					self::checkError($output);

					if (empty($tasks)) {
						$output->writeln("<info>No Tasks found</info>");
						die();
					}


					self::genTable($tasks, array(
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
					self::checkError($output);
					$task = $task[0];

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


					$id_task = (int)$input->getArgument("taskid");
					if ($id_task == 0) {
						//try to get task id from session file:
						$id_task = self::getActiveTaskId($output);
					}

					$params = array(
						"taskids" => $id_task,
						"plaintext"  => true,
					);

					$comments = Task::GetTaskComments($params);
					self::checkError($output);

					if (empty($comments)) {
						$output->writeln("<info>No Comments found</info>");
						die();
					}

					self::genTable($comments, array(
						"USERNAME"     => "User",
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
					$id_task = (int)$input->getArgument("taskid");

					if ($id_task == 0) {
						//try to get task id from session file:
						$id_task = self::getActiveTaskId($output);
					}


					$params = array(
						"taskid" => $id_task,
						"addcomments"  => $comment,
					);

					$re = Task::SaveTask($params);
					self::checkError($output);


					$output->writeln("<info>Comment added to task {$id_task}</info>");

				});


			//*****************************************************//
			//RUN THE CLI!
			$console->run();

		}


		/**
		 * Check for errors occured during the API calls, display the error and stop the script
		 *
		 * @param $output - Symfony output object
		 */
		private static function checkError(&$output)
		{
			$error = AceProject::getLastError();
			if ( !empty($error)) {
				//some error occured:
				$output->writeln("<error>" . $error . "</error>");
				die();
			}

		}


		/**
		 * Get active Task ID (which is stored in the session file)
		 *
		 * @param $output
		 *
		 * @return mixed
		 */
		private static function getActiveTaskId($output)
		{
			$ses = self::$session;
			if (isset($ses['TASK_ID']) && (int)$ses['TASK_ID'] > 0) {
				return $ses['TASK_ID'];
			} else {
				$output->writeln("<error>No task was specified!</error>");
				die();
			}
		}


	}


	Ace::init();

