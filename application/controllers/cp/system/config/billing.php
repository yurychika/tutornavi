<?php

class CP_System_Config_Billing_Controller extends CP_System_Config_System_Controller
{
	public function __construct()
	{
		parent::__construct();

		// Does user have permission to access this plugin?
		if ( !session::permission('settings_manage', 'billing') )
		{
			view::noAccess();
		}

		view::setCustomParam('section', 'billing');
		view::setCustomParam('options', config::item('cp_top_nav', 'lists', 'billing', 'items'));

		view::setTrail('cp/billing/transactions', __('billing', 'system_navigation'));
	}
}
