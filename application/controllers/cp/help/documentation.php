<?php

class CP_Help_Documentation_Controller extends Controller
{
	public function __construct()
	{
		parent::__construct();
	}

	public function index()
	{
		router::redirect('http://www.socialscript.com/documentation');
	}
}
