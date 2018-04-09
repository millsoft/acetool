<?php
namespace Millsoft\AceTool\Commands;

use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;

class CommentCommands extends Commands{

	public static function commandList(){
        self::$console->register('comments:list2')
            ->setDescription('List all Comments for a Task - TEST')
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
}