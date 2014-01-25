<?php defined('SYSPATH') || die('No direct script access allowed.');

class Geo_Helper
{
	static public function getCountries()
	{
		return config::item('countries', 'core');
	}

	static public function getCountry($countryID)
	{
		return config::item('countries', 'core', $countryID);
	}

	static public function getStates($countryID)
	{
		$str = codebreeder::instance()->geo_model->getStates($countryID, true);

		return $str;
	}

	static public function getState($stateID)
	{
		$str = codebreeder::instance()->geo_model->getState($stateID);

		return $str;
	}

	static public function getCities($stateID, $padding = false)
	{
		$str = codebreeder::instance()->geo_model->getCities($stateID, true);

		return $str;
	}

	static public function getCity($cityID)
	{
		$str = codebreeder::instance()->geo_model->getCity($cityID);

		return $str;
	}

}