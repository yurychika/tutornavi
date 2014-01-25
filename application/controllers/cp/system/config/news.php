<?php

class CP_System_Config_News_Controller extends CP_System_Config_System_Controller
{
	public function __construct()
	{
		parent::__construct();

		// Does user have permission to access this plugin?
		if ( !session::permission('settings_manage', 'news') )
		{
			view::noAccess();
		}

		view::setCustomParam('section', 'content');
		view::setCustomParam('options', config::item('cp_top_nav', 'lists', 'content', 'items', 'content/news', 'items'));

		view::setTrail('cp/system/plugins', __('content', 'system_navigation'));
		view::setTrail('cp/content/news', __('news', 'system_navigation'));
	}

	protected function _savePluginSettings($keyword, $value)
	{
		// Toggle news
		if ( $keyword == 'news_active' )
		{
			loader::model('system/lists');
			$this->lists_model->toggleItemStatus('news', 'site_bottom_nav', 'site/news', $value);
		}
	}
}
