<?php

class CP_System_Config_Blogs_Controller extends CP_System_Config_System_Controller
{
	public function __construct()
	{
		parent::__construct();

		// Does user have permission to access this plugin?
		if ( !session::permission('settings_manage', 'blogs') )
		{
			view::noAccess();
		}

		view::setCustomParam('section', 'plugins');
		view::setCustomParam('options', config::item('cp_top_nav', 'lists', 'plugins', 'items', 'plugins/blogs', 'items'));

		view::setTrail('cp/system/plugins', __('plugins', 'system_navigation'));
		view::setTrail('cp/plugins/blogs', __('blogs', 'system_navigation'));
	}

	protected function _savePluginSettings($keyword, $value)
	{
		// Toggle blogs
		if ( $keyword == 'blogs_active' )
		{
			loader::model('system/lists');
			$this->lists_model->toggleItemStatus('blogs', 'site_user_nav', 'user/blogs', $value);
		}
		// Toggle public blogs page
		elseif ( $keyword == 'blogs_gallery' )
		{
			loader::model('system/lists');
			$this->lists_model->toggleItemStatus('blogs', 'site_top_nav', 'site/blogs', input::post('blogs_active') && $value ? 1 : 0);
		}
	}
}
