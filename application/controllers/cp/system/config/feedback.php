<?php

class CP_System_Config_Feedback_Controller extends CP_System_Config_System_Controller
{
	public function __construct()
	{
		parent::__construct();

		// Does user have permission to access this plugin?
		if ( !session::permission('settings_manage', 'feedback') )
		{
			view::noAccess();
		}

		view::setCustomParam('section', 'content');
		view::setCustomParam('options', config::item('cp_top_nav', 'lists', 'content', 'items', 'content/feedback', 'items'));

		view::setTrail('cp/system/plugins', __('content', 'system_navigation'));
		view::setTrail('cp/system/config/feedback', __('feedback', 'system_navigation'));
	}

	protected function _savePluginSettings($keyword, $value)
	{
		// Toggle feedback
		if ( $keyword == 'feedback_active' && config::item($keyword, 'feedback') != $value )
		{
			loader::model('system/lists');

			if ( $value )
			{
				$data = array(
					'uri' => 'contact',
					'name' => 'feedback_contact_us|system_navigation',
				);
				$this->lists_model->addItem('feedback', 'site_bottom_nav', 'feedback', $data);
			}
			else
			{
				$this->lists_model->deleteItem('feedback', 'site_bottom_nav', 'feedback');
			}
		}
	}
}
