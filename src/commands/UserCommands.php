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

    //search for a user by a string (email, firstname, lastname) and return the id
    public function getUseridFromString($str, $onlyone = true){
        
        $users = Users::GetUsers(array(
            'texttosearch' => $str
        ));

        if(empty($users)){
            throw new \Exception("No user found using search '{$str}'", 1);
            return null;
        }

        if($onlyone && count($users) > 1){
            throw new \Exception("User search returned more than one user. Only one is required.\nUse a more exact search or use a numeric id", 1);
        }

        if($onlyone){
            return $users[0]->USER_ID;
        }

        $re = array();
        foreach($users as $user){
            $re[] = $user->USER_ID;
        }

        return $re;
    }


}