<?php
namespace Millsoft\AceTool\Commands;

use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;

use Millsoft\AceProject\Project;
use Millsoft\AceProject\Users;

use Millsoft\AceTool\Helper;


class ProjectCommands extends Commands{

    
    /**
    * LIST ALL PROJECTS
    */
    public static function commandProjects(){
        self::$console->register('projects')
            ->setDescription('List all projects')
            ->setCode(function (InputInterface $input, OutputInterface $output) {

                $projects = Project::GetProjects();
                Helper::checkError($output);

                Helper::genTable($projects, array(
                    "PROJECT_ID"   => "Id",
                    "PROJECT_NAME" => "Project Name",
                ), $output);
            });
    }


    public static function commandGetTotals(){
        self::$console->register('projects:totals')
            ->setDescription('Get Project Totals')
            ->setDefinition(array(
                    new InputArgument('projectid', InputArgument::REQUIRED, "Project ID"),
                ))
            ->setCode(function (InputInterface $input, OutputInterface $output) {
                $projectid = (int) $input->getArgument("projectid");

                $totals = Users::GetTotals($projectid);
                Helper::checkError($output);

                $totals = $totals[0];

                $re = Helper::getFormattedArray($totals);
                $output->writeln(print_r($re, true));
            });
    }



}