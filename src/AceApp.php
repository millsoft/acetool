<?php
	namespace Millsoft\AceTool;

	use Symfony\Component\Console\Application;


	class AceApp extends Application
	{
		private static $name = "AceProject";
		/**
		 * @var string
		 */
		private static $logo = <<<LOGO
                  _____           _           _   
    /\           |  __ \         (_)         | |  
   /  \   ___ ___| |__) | __ ___  _  ___  ___| |_ 
  / /\ \ / __/ _ \  ___/ '__/ _ \| |/ _ \/ __| __|
 / ____ \ (_|  __/ |   | | | (_) | |  __/ (__| |_ 
/_/    \_\___\___|_|   |_|  \___/| |\___|\___|\__|
                                _/ |              
                               |__/               

LOGO;



		/**
		 * MyApp constructor.
		 *
		 * @param KernelInterface $kernel
		 * @param string          $version
		 */
		public function __construct( $name = 'UNKNOWN', $version = 'UNKNOWN')
		{

			//parent::__construct($kernel);

			$this->setName(static::$name);
			$this->setVersion($version);

			parent::__construct($name, $version);

		}

		/**
		 * @return string
		 */
		public function getHelp()
		{
			return static::$logo . parent::getHelp();
		}
	}