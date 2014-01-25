<?php

class CP_Help_License_Controller extends Controller
{
	public function __construct()
	{
		parent::__construct();

		// Does user have permission to access this plugin?
		if ( !session::permission('license_manage', 'system') )
		{
			view::noAccess();
		}

		view::setCustomParam('section', 'help');
		view::setCustomParam('options', array('help/license' => array('name' => __('help_license', 'system_navigation'), 'uri' => 'help/license', 'keyword' => 'help/license', 'attr' => array('help license'), 'items' => array())));

		loader::model('system/license');

		view::setTrail('cp/help/license', __('help', 'system_navigation'));
		view::setTrail('cp/help/license', __('help_license', 'system_navigation'));

		// Check if demo mode is enabled
		if ( input::demo(1, 'cp/system/config/system') ) return false;
	}

	public function index()
	{
		// Get license details
		if ( !( $license = $this->license_model->getLicense() ) )
		{
			view::setError(__('no_license', 'system_license'));
			router::redirect('cp/help/license/change');
		}

		// Assign vars
		view::assign(array('license' => $license));

		// Set title
		view::setTitle(__('help_license', 'system_navigation'));

		// Load view
		view::load('cp/help/license/view');
	}

	public function change()
	{
		$this->users_model->logout();

		router::redirect('cp/users/login/license');
	}
}
