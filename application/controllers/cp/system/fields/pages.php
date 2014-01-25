<?php

class CP_System_Fields_Pages_Controller extends CP_System_Fields_System_Controller
{
	public function __construct()
	{
		parent::__construct();

		// Does user have permission to access this plugin?
		if ( !session::permission('fields_manage', 'pages') )
		{
			view::noAccess();
		}

		view::setCustomParam('section', 'content');
		view::setCustomParam('options', config::item('cp_top_nav', 'lists', 'content', 'items', 'content/pages', 'items'));

		view::setTrail('cp/system/plugins', __('content', 'system_navigation'));
		view::setTrail('cp/content/pages', __('pages', 'system_navigation'));
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
		view::setTrail('cp/system/fields/pages/browse', __('fields', 'system_fields'));

		// Browse custom fields
		$this->browseFields('pages', 'pages_data');
	}

	public function edit()
	{
		// Get URI vars
		$fieldID = (int)uri::segment(7);

		// Set title
		view::setTitle($fieldID ? __('edit_field', 'system_fields') : __('new_field', 'system_fields'));

		// Set trail
		view::setTrail('cp/system/fields/pages/browse', __('fields', 'system_fields'));

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
						$hidden['multilang'] = 1;
						$hidden['system'] = 1;
						$hidden['in_search'] = 0;
						$hidden['config_custom_in_view'] = 1;
						$hidden['config_custom_in_list'] = 1;
						break;

					case 'body':
						$hidden['required'] = 1;
						$hidden['multilang'] = 1;
						$hidden['system'] = 1;
						$hidden['in_search'] = 0;
						$hidden['config_custom_in_view'] = 1;
						$hidden['config_custom_in_list'] = 1;
						$hidden['html'] = 1;
						break;

					case 'meta_keywords':
					case 'meta_description':
						$hidden['multilang'] = 1;
						$hidden['system'] = 1;
						$hidden['in_search'] = 0;
						$hidden['config_custom_in_view'] = 1;
						$hidden['config_custom_in_list'] = 1;
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
				'label' => __('config_in_list', 'system_fields'),
				'keyword' => 'in_list',
				'type' => 'boolean',
				'rules' => array('intval'),
			),
		);

		// Edit custom field
		$this->editField('pages', 'pages_data', 0, $fieldID, $config, $hidden);
	}

	public function delete()
	{
		// Get URI vars
		$fieldID = (int)uri::segment(7);

		// Delete custom field
		$this->deleteField('pages', 'pages_data', 0, $fieldID);
	}
}
