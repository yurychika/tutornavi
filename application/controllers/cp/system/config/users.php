<?php

class CP_System_Config_Users_Controller extends CP_System_Config_System_Controller
{
	public function __construct()
	{
		parent::__construct();

		// Does user have permission to access this plugin?
		if ( !session::permission('settings_manage', 'users') )
		{
			view::noAccess();
		}

		view::setCustomParam('section', 'users');
		view::setCustomParam('options', config::item('cp_top_nav', 'lists', 'users', 'items'));

		view::setTrail('cp/users', __('users', 'system_navigation'));
	}

	protected function _get_usergroups($setting)
	{
		$setting['items'] = config::item('usergroups', 'core');

		return $setting;
	}

	protected function _get_usertypes($setting)
	{
		$setting['items'] = config::item('usertypes', 'core', 'names');

		return $setting;
	}
}
