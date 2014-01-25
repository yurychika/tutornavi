<?php

class CP_System_Config_Classifieds_Controller extends CP_System_Config_System_Controller
{
	public function __construct()
	{
		parent::__construct();

		// Does user have permission to access this plugin?
		if ( !session::permission('settings_manage', 'classifieds') )
		{
			view::noAccess();
		}

		view::setCustomParam('section', 'plugins');
		view::setCustomParam('options', config::item('cp_top_nav', 'lists', 'plugins', 'items', 'plugins/classifieds', 'items'));

		view::setTrail('cp/system/plugins', __('plugins', 'system_navigation'));
		view::setTrail('cp/plugins/classifieds', __('classifieds', 'system_navigation'));
	}

	protected function _savePluginSettings($keyword, $value)
	{
		// Toggle classifieds
		if ( $keyword == 'classifieds_active' )
		{
			loader::model('system/lists');
			$this->lists_model->toggleItemStatus('classifieds', 'site_user_nav', 'user/classifieds', $value);
		}
		// Toggle public ads page
		elseif ( $keyword == 'ads_gallery' )
		{
			loader::model('system/lists');
			$this->lists_model->toggleItemStatus('classifieds', 'site_top_nav', 'site/classifieds', input::post('classifieds_active') && $value ? 1 : 0);
		}
	}
}
