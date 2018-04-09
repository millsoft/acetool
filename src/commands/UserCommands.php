<?php
namespace Millsoft\AceTool\Commands;

use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;

use Millsoft\AceProject\Users;

use Millsoft\AceTool\Helper;


class UserCommands extends Commands{

    //Get all Users
    public static function commandGetUsers(){
        self::$console->register('users')
            ->setDescription('List all users')
            ->setCode(function (InputInterface $input, OutputInterface $output) {

                $users = Users::GetUsers();
                Helper::checkError($output);

                Helper::genTable($users, array(
                    "USER_ID"   => "Id",
                    "USERNAME" => "Username",
                    "FIRST_NAME" => "Username",
                    "LAST_NAME" => "Lastname",
                    "USER_GROUP_NAME" => "Group",
                ), $output);
            });
    }


}