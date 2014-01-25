<?php

class CP_Home_Controller extends Controller
{
	public function __construct()
	{
		parent::__construct();

		if ( !users_helper::isLoggedin() || !session::permission('site_access_cp', 'system') )
		{
			router::redirect('cp/users/login');
		}
	}

	public function index()
	{
		if ( session::permission('users_manage', 'users') )
		{
			loader::controller('cp/users');
			$this->users->browse();
		}
		else
		{
			view::noAccess(false);

			// Load view
			view::load('cp/home');
		}
	}
}
