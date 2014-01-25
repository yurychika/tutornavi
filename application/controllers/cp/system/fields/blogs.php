<?php

class CP_System_Fields_Blogs_Controller extends CP_System_Fields_System_Controller
{
	public function __construct()
	{
		parent::__construct();

		// Does user have permission to access this plugin?
		if ( !session::permission('fields_manage', 'blogs') )
		{
			view::noAccess();
		}

		view::setCustomParam('section', 'plugins');
		view::setCustomParam('options', config::item('cp_top_nav', 'lists', 'plugins', 'items', 'plugins/blogs', 'items'));

		view::setTrail('cp/system/plugins', __('plugins', 'system_navigation'));
		view::setTrail('cp/plugins/blogs', __('blogs', 'system_navigation'));
	}

	public function index()
	{
		$this->browse();
	}

	public function browse()
	{
		// Set title
		view::setTitle(__('fields', 'system_fields'));

		// Set trail
		view::setTrail('cp/system/fields/blogs/browse', __('fields', 'system_fields'));

		// Browse custom fields
		$this->browseFields('blogs', 'blogs_data');
	}

	public function edit()
	{
		// Get URI vars
		$fieldID = (int)uri::segment(7);

		// Set title
		view::setTitle($fieldID ? __('edit_field', 'system_fields') : __('new_field', 'system_fields'));

		// Set trail
		view::setTrail('cp/system/fields/blogs/browse', __('fields', 'system_fields'));

		// Hide options array
		$hidden = array('html' => 0, 'in_search_advanced' => 0);
		if ( $fieldID )
		{
			$field = $this->fields_model->getField($fieldID);
			if ( $field )
			{
				switch ( $field['keyword'] )
				{
					case 'title':
						$hidden['required'] = 1;
						$hidden['in_search'] = 0;
						$hidden['system'] = 1;
						break;

					case 'body':
						$hidden['required'] = 1;
						$hidden['in_search'] = 0;
						$hidden['system'] = 1;
						break;
				}
			}
		}

		// Additional field configuration array
		$config = array(
			array(
				'label' => __('config_in_view', 'system_fields'),
				'keyword' => 'in_view',
				'type' => 'boolean',
				'rules' => array('intval'),
			),
			array(
				'label' => __('config_in_account', 'system_fields'),
				'keyword' => 'in_account',
				'type' => 'boolean',
				'rules' => array('intval'),
			),
			array(
				'label' => __('config_in_list', 'system_fields'),
				'keyword' => 'in_list',
				'type' => 'boolean',
				'rules' => array('intval'),
			),
		);

		// Edit custom field
		$this->editField('blogs', 'blogs_data', 0, $fieldID, $config, $hidden);
	}

	public function delete()
	{
		// Get URI vars
		$fieldID = (int)uri::segment(7);

		// Delete custom field
		$this->deleteField('blogs', 'blogs_data', 0, $fieldID);
	}
}
