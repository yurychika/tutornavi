<?php defined('SYSPATH') || die('No direct script access allowed.');
/**
* Date Helper
*
* Wrapper class
*
* @package		CodeBreeder
* @category		Core
* @author		VLD Interactive Inc.
* @copyright	(c) 2013 VLD Interactive Inc.
* @license		http://www.codebreeder.com/license
* @link			http://www.codebreeder.com/guide/helpers/date
*/
class Date_Helper extends CodeBreeder_Date_Helper
{
	public static function formatDate($time = '', $format = 'stamp', $usa = true, $offset = 0, $dst = null)
	{
		$usa = config::item('time_euro', 'system') ? false : true;

		$offset = session::item('time_zone') * 60*60;

		$dst = date('i') ? 3600 : 0;

		return parent::formatDate($time, $format, $usa, $offset, $dst);
	}

	public static function stampToDate($stamp = '')
	{
		if ( strlen($stamp) == 8 )
		{
			$stamp = date_helper::month(ltrim(substr($stamp, 4, 2), '0')).' '.(int)substr($stamp, -2).', '.substr($stamp, 0, 4);
		}
		elseif ( strlen($stamp) == 4 )
		{
			$stamp = date_helper::month(ltrim(substr($stamp, 0, 2), '0')).' '.(int)substr($stamp, -2);
		}

		return $stamp;
	}

	public static function getYearsDiff($date = 19900101)
	{
		$year = substr($date, 0, 4);
		$age = date('Y') - $year;
		$md = date('m') . date('d');

		if ( $md < substr($date, -4) )
		{
			$age--;
		}

		return $age;
	}
}
