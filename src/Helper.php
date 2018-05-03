<?php

namespace Millsoft\AceTool;

	/**
	 * Class Helper
	 * For Ace Tool
	 * By Michael Milawski
	 */

    use Symfony\Component\Console\Helper\Table;
    use Symfony\Component\Console\Helper\TableStyle;
    use Millsoft\AceProject\AceProject;


    class Helper{

        private static $sessionFile = __DIR__ . "/../session.json";
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

            $ts = $ts / 1000;

         if($returnFormatted){

             $d = date("Y-m-d H:i:s", $ts);

             $d = new \DateTime($d);
             $local_date_time = $d->sub(new \DateInterval('PT6H'));
             $local_date_time = $local_date_time->format($dateFormat);

             return $local_date_time;
        }else{
         return $ts;
     }

 }

 return 0;
}


public static function convertTime($dec)
{

    $minutes = round($dec * 60,0);

    $d = floor ($minutes / 1440);
    $h = floor (($minutes - $d * 1440) / 60);
    $m = $minutes - ($d * 1440) - ($h * 60);

    if($d == 0 && $h ==0){
        return "{$m}m";
    }

    if($d == 0){
        return "{$h}h {$m}m";
    }

    return "{$d}d {$h}h {$m}m";
}

    // lz = leading zero
private static function lz($num)
{
    return (strlen($num) < 2) ? "0{$num}" : $num;
}


		/**
		 * Format an array nicely for a
		 * @param $arr
		 *
		 * @return formatted array
		 */
		public static function getFormattedArray($arr, $colorize = true){


			$re = array();
			foreach($arr as $k=>$v){
				if(strpos($v, "/Date(") !== false){
					//convert the micro timestamp to human readable time:
					$v = self::getDate($v, true);
				}

                if(stripos($k, 'HOURS') !== false){
                    //reformat decimal hours into hh:mm::ss
                    if($v > 0){
                        $v = $v . ' (' . self::convertTime($v) . ')';
                    }

                }

                if($colorize){
                   $re[$k] = "<info>" . $v . "</info>";
                }else{
                   $re[$k] = $v;
                }

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


        /**
         * Generate a table with data
         * @param $data - array with the data
         * @param $cols - column key / name mapping
         * @param $output - output console object
         * @param array $columnWidths - min. column widths
         */
        public static function genTable ($data, $cols, $output, $columnWidths = array())
        {

            $rows = array();
            $headers = array();

            $colMap = array_keys($cols);


            foreach ($data as $row) {
                $r = array();
                foreach ($colMap as $key) {
                    $item = $row->$key;
                    //TODO: Special Characters, eg with umlauts will be displayed wrong. utf8_* functions are not working
                    $r[] = $item;
                }

                $r = self::getFormattedArray($r, false);

                $rows[] = $r;
            }


            // by default, this is based on the default style
            $tableStyle = new TableStyle();

// customizes the style
            $tableColor = "cyan";
            $tableStyle
                ->setHorizontalBorderChar("<fg={$tableColor}>─</>")
                ->setVerticalBorderChar("<fg={$tableColor}>│</>")
                ->setCrossingChar("<fg={$tableColor}>┼</>")
            ;
            Table::setStyleDefinition('milmike', $tableStyle);

            $table = new Table($output);

            $table
            ->setHeaders($cols)
            ->setStyle("milmike")
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