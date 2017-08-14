<?php

	namespace Millsoft\AceTool;

	/**
	 * Class Helper
	 * For Ace Tool
	 * By Michael Milawski
	 */

	class Helper{

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
	}