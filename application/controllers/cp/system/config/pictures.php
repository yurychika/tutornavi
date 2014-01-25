<?php

class CP_System_Config_Pictures_Controller extends CP_System_Config_System_Controller
{
	public function __construct()
	{
		parent::__construct();

		// Does user have permission to access this plugin?
		if ( !session::permission('settings_manage', 'pictures') )
		{
			view::noAccess();
		}

		view::setCustomParam('section', 'plugins');
		view::setCustomParam('options', config::item('cp_top_nav', 'lists', 'plugins', 'items', 'plugins/pictures', 'items'));

		view::setTrail('cp/system/plugins', __('plugins', 'system_navigation'));
		view::setTrail('cp/plugins/pictures', __('pictures', 'system_navigation'));
	}

	protected function _savePluginSettings($keyword, $value)
	{
		// Toggle pictures
		if ( $keyword == 'pictures_active' )
		{
			loader::model('system/lists');
			$this->lists_model->toggleItemStatus('pictures', 'site_user_nav', 'user/pictures', $value);
		}
		// Toggle public pictures page
		elseif ( $keyword == 'albums_gallery' )
		{
			loader::model('system/lists');
			$this->lists_model->toggleItemStatus('pictures', 'site_top_nav', 'site/pictures', input::post('pictures_active') && $value ? 1 : 0);
		}
	}
}
