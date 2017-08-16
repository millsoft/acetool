<?php

	namespace Millsoft\AceTool;

	/**
	 * Class Helper
	 * For Ace Tool
	 * By Michael Milawski
	 */

    use Symfony\Component\Console\Helper\Table;
    use Millsoft\AceProject\AceProject;


    class Helper{

        private static $sessionFile = "session.json";
        private static $session = array();



        public static function initSession(){
            //get subdomain from session file:
            self::$session = Helper::getSession();

            if (isset(self::$session[ 'subdomain' ])) {
                AceProject::$subdomain = self::$session[ 'subdomain' ];
            }
        }

        /**
		 * Get the timestamp from Aceproject API
		 * @param $dateString
		 * @param $returnFormatted - false. returns timestamp only, true returns a formatted string
		 *
		 * @return bool|int|string
		 */
		public static function getDate($dateString, $returnFormatted = false, $dateFormat = "Y-m-d - H:i"){
			$re = '/Date\((?<timestamp>\d.+)\)/';

			preg_match_all($re, $dateString, $matches, PREG_SET_ORDER, 0);
			if(empty($matches)){
				return 0;
			}

			$m = $matches[0];

			if(isset($m['timestamp'])){

				$ts = $m['timestamp'];
				if(substr($ts,-3) == '000'){
					$ts = substr($ts,0, -3);
				}

				if($returnFormatted){
					return date($dateFormat, $ts);
				}else{
					return $ts;
				}

			}

			return 0;
		}

		/**
		 * Format an array nicely for a
		 * @param $arr
		 *
		 * @return formatted array
		 */
		public static function getFormattedArray($arr){

			$re = array();
			foreach($arr as $k=>$v){
				if(strpos($v, "/Date(") !== false){
					//convert the micro timestamp to human readable time:
					$v = self::getDate($v, true);
				}

				$re[$k] = "<info>" . $v . "</info>";
			}

			return $re;
		}

        /**
         * Check for errors occured during the API calls, display the error and stop the script
         *
         * @param $output - Symfony output object
         */
        public static function checkError (&$output)
        {
            $error = AceProject::getLastError();
            if (!empty($error)) {
                //some error occured:
                $output->writeln("<error>" . $error . "</error>");
                die();
            }

        }

        /**
         * Set local session file config
         *
         * @param      $key
         * @param null $val
         */
        public static function setSession ($key, $val = null)
        {
            $sess = self::getSession();

            $sess[ $key ] = $val;
            file_put_contents(self::$sessionFile, json_encode($sess));
        }

        /**
         * Get a local config from session file
         * @return array|mixed
         */
        public static function getSession ()
        {
            if (!file_exists(self::$sessionFile)) {
                return array();
            }

            $s = file_get_contents(self::$sessionFile);
            $sess = json_decode($s, true);

            return $sess;
        }

        /**
         * Delete a key from session file
         *
         * @param $key
         */
        public static function delSessionKey ($key)
        {
            $sess = self::getSession();

            if (isset($sess[ $key ])) {
                unset($sess[ $key ]);
            }
            file_put_contents(self::$sessionFile, json_encode($sess));
        }



        public static function genTable ($data, $cols, $output, $columnWidths = array())
        {

            $rows = array();
            $headers = array();


            $colMap = array_keys($cols);


            foreach ($data as $row) {
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


            if (!empty($columnWidths)) {
                $table->setColumnWidths($columnWidths);
            }


            $table->render();

        }

        /**
         * Get active Task ID (which is stored in the session file)
         *
         * @param $output
         *
         * @return mixed
         */
        public static function getActiveTaskId ($output)
        {
            $ses = self::$session;
            if (isset($ses[ 'TASK_ID' ]) && (int) $ses[ 'TASK_ID' ] > 0) {
                return $ses[ 'TASK_ID' ];
            } else {
                $output->writeln("<error>No task was specified!</error>");
                die();
            }
        }

    }