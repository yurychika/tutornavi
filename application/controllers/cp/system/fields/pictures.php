<?php

class CP_System_Fields_Pictures_Controller extends CP_System_Fields_System_Controller
{
	public function __construct()
	{
		parent::__construct();

		// Does user have permission to access this plugin?
		if ( !session::permission('fields_manage', 'pictures') )
		{
			view::noAccess();
		}

		view::setCustomParam('section', 'plugins');
		view::setCustomParam('options', config::item('cp_top_nav', 'lists', 'plugins', 'items', 'plugins/pictures', 'items'));

		view::setTrail('cp/system/plugins', __('plugins', 'system_navigation'));
		view::setTrail('cp/plugins/pictures', __('pictures', 'system_navigation'));
	}

	public function index()
	{
		$this->browse();
	}

	public function browse()
	{
		// Get URI vars
		$typeID = (int)uri::segment(6);

		$typeID = $typeID == 0 || $typeID == 1 ? $typeID : 0;

		// Set title
		view::setTitle(__('fields', 'system_fields'));

		// Set trail
		view::setTrail('cp/system/fields/pictures', __('fields', 'system_fields'));
		if ( $typeID == 1 )
		{
			view::setTrail('cp/system/fields/pictures/browse/1', __('pictures_albums', 'system_navigation'));
		}

		// Assign actions
		view::setAction('cp/system/fields/pictures/browse/1', __('album_fields', 'pictures'), array('class' => 'icon-text icon-system-fields'));

		// Browse custom fields
		$this->browseFields('pictures', 'pictures_' . ( $typeID == 1 ? 'albums_' : '' ) . 'data', $typeID);
	}

	public function edit()
	{
		// Get URI vars
		$typeID = (int)uri::segment(6);
		$fieldID = (int)uri::segment(7);

		$typeID = $typeID == 0 || $typeID == 1 ? $typeID : 0;

		// Set title
		view::setTitle($fieldID ? __('edit_field', 'system_fields') : __('new_field', 'system_fields'));

		// Set trail
		view::setTrail('cp/system/fields/pictures', __('fields', 'system_fields'));
		if ( $typeID == 1 )
		{
			view::setTrail('cp/system/fields/pictures/browse/1', __('pictures_albums', 'system_navigation'));
		}

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

					case 'description':
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
		$this->editField('pictures', 'pictures_' . ( $typeID == 1 ? 'albums_' : '' ) . 'data', $typeID, $fieldID, $config, $hidden);
	}

	public function delete()
	{
		// Get URI vars
		$typeID = (int)uri::segment(6);
		$fieldID = (int)uri::segment(7);

		$typeID = $typeID == 0 || $typeID == 1 ? $typeID : 0;

		// Delete custom field
		$this->deleteField('pictures', 'pictures_' . ( $typeID == 1 ? 'albums_' : '' ) . 'data', $typeID, $fieldID);
	}
}
