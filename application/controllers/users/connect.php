<?php

class Users_Connect_Controller extends Controller
{
	public function __construct()
	{
		parent::__construct();

		// Is user logged in?
		if ( users_helper::isLoggedin() && strtolower(uri::segment(3)) != 'out' )
		{
			router::redirect(session::item('slug'));
		}

		loader::model('users/authentication', array(), 'users_authentication_model');
	}

	public function index()
	{
		$this->authorize();
	}

	public function authorize()
	{
		$class = uri::segment(4);
		$action = uri::segment(5) == 'signup' ? 'signup' : 'login';

		$service = $this->users_authentication_model->getService($class);

		if ( $service )
		{
			loader::library('authentication/' . uri::segment(4), $service['settings'], 'users_authentication_' . $class . '_model');

			$this->{'users_authentication_' . $class . '_model'}->authorize($action);
		}

		router::redirect('users/login');
	}

	public function confirm()
	{
		$class = uri::segment(4);
		$action = uri::segment(5) == 'signup' ? 'signup' : 'login';

		$service = $this->users_authentication_model->getService($class);

		if ( $service )
		{
			loader::library('authentication/' . uri::segment(4), $service['settings'], 'users_authentication_' . $class . '_model');

			$this->{'users_authentication_' . $class . '_model'}->confirm($action);
		}

		router::redirect('users/login');
	}
}
