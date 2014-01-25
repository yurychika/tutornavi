<?php

class Geo_Controller extends Controller
{
	public function __construct()
	{
		parent::__construct();
	}

	public function index()
	{
	}

	public function countries()
	{
		$output = array();

		$result = $this->db->query("SELECT `country_id`, `name` FROM `:prefix:geo_countries` ORDER BY `name` ASC")->result();
		foreach ( $result as $row )
		{
			$output[$row['country_id']] = $row['name'];
		}

		view::ajaxResponse($output);
	}

	public function states()
	{
		$countryID = uri::segment(3);

		$data = array();

		if ( input::post('title') == 'any' )
		{
			$data[''] = __('any', 'system', array(), array(), false);
		}
		else
		{
			$data[''] = __('select', 'system', array(), array(), false);
		}

		foreach ( geo_helper::getStates($countryID) as $id => $name )
		{
			$data[$id . ' '] = $name;
		}

		view::ajaxResponse($data);
	}

	public function cities()
	{
		$stateID = uri::segment(3);

		$data = array();

		if ( input::post('title') == 'any' )
		{
			$data[''] = __('any', 'system', array(), array(), false);
		}
		else
		{
			$data[''] = __('select', 'system', array(), array(), false);
		}

		foreach ( geo_helper::getCities($stateID) as $id => $name )
		{
			$data[$id . ' '] = $name;
		}

		view::ajaxResponse($data);
	}
}
