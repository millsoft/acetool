<?php
namespace Millsoft\AceTool\Commands;

use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;

use Millsoft\AceProject\Task;

class CommentCommands extends Commands{

    //Get all comments for a task
	public static function commandList(){
        self::$console->register('comments:list')
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

	}

    //Add a new comment to a task
    public static function commandAdd(){
        self::$console->register('comments:add')
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
    }
}