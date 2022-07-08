<?php

class MainWP_wfUtils {
	
	public static function makeRandomIP(){
		return rand(11,230) . '.' . rand(0,255) . '.' . rand(0,255) . '.' . rand(0,255);
	}
	
	/**
	 * Converts a truthy value to a boolean, checking in this order:
	 * - already a boolean
	 * - numeric (0 => false, otherwise true)
	 * - 'false', 'f', 'no', 'n', or 'off' => false
	 * - 'true', 't', 'yes', 'y', or 'on' => true
	 * - empty value => false, otherwise true
	 * 
	 * @param $value
	 * @return bool
	 */
	public static function truthyToBoolean($value) {
		if ($value === true || $value === false) {
			return $value;
		}
		
		if (is_numeric($value)) {
			return !!$value;
		}
		
		if (preg_match('/^(?:f(?:alse)?|no?|off)$/i', $value)) {
			return false;
		}
		else if (preg_match('/^(?:t(?:rue)?|y(?:es)?|on)$/i', $value)) {
			return true;
		}
		
		return !empty($value);
	}
	
	/**
	 * Converts a truthy value to 1 or 0.
	 * 
	 * @see wfUtils::truthyToBoolean
	 * 
	 * @param $value
	 * @return int
	 */
	public static function truthyToInt($value) {
		return self::truthyToBoolean($value) ? 1 : 0;
	}

}
