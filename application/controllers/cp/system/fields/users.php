<?php

class CP_System_Fields_Users_Controller extends CP_System_Fields_System_Controller
{
	public function __construct()
	{
		parent::__construct();

		// Does user have permission to access this plugin?
		if ( !session::permission('fields_manage', 'users') )
		{
			view::noAccess();
		}

		view::setCustomParam('section', 'users');
		view::setCustomParam('options', config::item('cp_top_nav', 'lists', 'users', 'items'));

		view::setTrail('cp/users', __('users', 'system_navigation'));
		view::setTrail('cp/users/types', __('users_types', 'system_navigation'));

		loader::model('users/types', array(), 'users_types_model');
	}

	public function index()
	{
		$this->browse();
	}

	public function browse()
	{
		// Get URI vars
		$typeID = (int)uri::segment(6);

		// Is user type ID set?
		if ( !$typeID )
		{
			// Get default user type
			if ( !( $types = $this->users_types_model->getTypes() ) )
			{
				view::setError(__('no_type', 'users_types'));
				router::redirect('cp/userstypes');
			}
			$type = current($types);
		}
		// Get user type
		elseif ( !( $type = $this->users_types_model->getType($typeID) ) )
		{
			view::setError(__('no_type', 'users_types'));
			router::redirect('cp/userstypes');
		}
		$typeID = $type['type_id'];

		// Set title
		view::setTitle(__('profile_questions', 'users_types'));

		// Set trail
		view::setTrail('cp/users/types/edit/' . $typeID, __('type_edit', 'users_types') . ' - ' . $type['name']);
		view::setTrail('cp/system/fields/users/browse/' . ($typeID ? $typeID : ''), __('profile_questions', 'users_types'));

		// Browse profile questions
		$this->browseFields('users', 'users_data_' . $type['keyword'], $typeID);
	}

	public function edit()
	{
		// Get URI vars
		$typeID = (int)uri::segment(6);
		$fieldID = (int)uri::segment(7);

		// Get user type
		if ( !$typeID || !( $type = $this->users_types_model->getType($typeID) ) )
		{
			view::setError(__('no_type', 'users_types'));
			router::redirect('cp/userstypes');
		}

		// Set title
		view::setTitle($fieldID ? __('edit_field', 'system_fields') : __('new_field', 'system_fields'));

		// Set trail
		view::setTrail('cp/users/types/edit/' . $typeID, __('type_edit', 'users_types') . ' - ' . $type['name']);
		view::setTrail('cp/system/fields/users/browse/' . ($typeID ? $typeID : ''), __('profile_questions', 'users_types'));

		// Hide options array
		$hidden = array('html' => 0, 'system' => 0);

		// Additional field configuration array
		$config = array(
			array(
				'label' => __('config_in_signup', 'system_fields'),
				'keyword' => 'in_signup',
				'type' => 'boolean',
				'rules' => array('intval'),
				'default' => 0,
			),
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

		// Edit profile question
		$this->editField('users', 'users_data_' . $type['keyword'], $typeID, $fieldID, $config, $hidden);
	}

	public function delete()
	{
		// Get URI vars
		$typeID = (int)uri::segment(6);
		$fieldID = (int)uri::segment(7);

		// Get user type
		if ( !$typeID || !( $type = $this->users_types_model->getType($typeID) ) )
		{
			view::setError(__('no_type', 'users_types'));
			router::redirect('cp/userstypes');
		}

		// Delete profile question
		$this->deleteField('users', 'users_data_' . $type['keyword'], $typeID, $fieldID);
	}
}
