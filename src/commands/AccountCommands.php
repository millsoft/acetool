<?php
namespace Millsoft\AceTool\Commands;

use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;

use Millsoft\AceProject\Users;
use Millsoft\AceProject\Account;

use Millsoft\AceTool\Helper;


class AccountCommands extends Commands{


	//Login to Aceproject
	public static function commandLogin(){
        self::$console->register('account:login')
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

                //find for the session file and remove it:
                $session_file = __DIR__ . "/vendor/millsoft/aceproject/.aceproject_" . $subdomain;

                if(file_exists($session_file)){
                    unlink($session_file);
                }

                Users::login($username, $password, $subdomain, true);
                Helper::checkError($output);

                Helper::setSession("subdomain", $subdomain);
                $output->writeln('<info>Logged in!</info> :)');

            });
	}

	//Show limits for logged in user
	public static function commandLimits(){
	       self::$console->register('account:limits')
            ->setDescription('Show current account info')
            ->setCode(function (InputInterface $input, OutputInterface $output) {
                $ac = Account::GetAccountStats();
                print_r($ac);
            });
	}

	//Logout from Aceproject
	public static function commandLogout(){
        	self::$console->register('account:logout')
            ->setDescription('Logout from your AceProject account')
            ->setCode(function (InputInterface $input, OutputInterface $output) {
                Helper::delSessionKey("subdomain");
                $output->writeln('Logged off. Use account:login command to login again.');
            });
	}



}