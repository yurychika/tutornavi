<?php

class Banners_Controller extends Controller
{
	public function __construct()
	{
		parent::__construct();
	}

	public function index()
	{
	}

	public function click()
	{
		$bannerID = (int)uri::segment(3);

		if ( $bannerID && $bannerID > 0 )
		{
			loader::model('banners/banners');

			$this->banners_model->updateClicks($bannerID);
		}

		exit;
	}
}
