<?php

class CP_Users_Types_Controller extends Controller
{
	public function __construct()
	{
		parent::__construct();

		// Does user have permission to access this plugin?
		if ( !session::permission('users_types', 'users') )
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
		// Get user types
		$types = $this->users_types_model->getTypes();

		// Create table grid
		$grid = array(
			'uri' => 'cp/users/types/browse',
			'keyword' => 'userstypes',
			'header' => array(
				'name' => array(
					'html' => __('name', 'system'),
					'class' => 'name',
				),
				'actions' => array(
					'html' => __('actions', 'system'),
					'class' => 'actions',
				),
			),
			'content' => array()
		);

		// Create grid content
		foreach ( $types as $type )
		{
			$grid['content'][] = array(
				'name' => array(
					'html' => html_helper::anchor('cp/users/types/edit/' . $type['type_id'], text_helper::truncate($type['name'], 64)),
				),
				'actions' => array(
					'html' => array(
						'questions' => html_helper::anchor('cp/system/fields/users/browse/' . $type['type_id'], __('profile_questions', 'users_types'), array('class' => 'questions')),
						'edit' => html_helper::anchor('cp/users/types/edit/' . $type['type_id'], __('edit', 'system'), array('class' => 'edit')),
						'delete' => html_helper::anchor('cp/users/types/delete/' . $type['type_id'], __('delete', 'system'), array('data-html' => __('type_delete?', 'users_types'), 'data-role' => 'confirm', 'class' => 'delete')),
					)
				),
			);
		}

		// Filter hooks
		hook::filter('cp/users/types/browse/grid', $grid);

		// Assign vars
		view::assign(array('grid' => $grid));

		// Set title
		view::setTitle(__('users_types_manage', 'system_navigation'));

		// Assign actions
		view::setAction('cp/users/types/edit', __('type_new', 'users_types'), array('class' => 'icon-text icon-users-types-new'));

		// Load view
		view::load('cp/users/types/browse');
	}

	public function edit()
	{
		// Get URI vars
		$typeID = (int)uri::segment(5);

		// Get type
		$type = array();
		if ( $typeID && !( $type = $this->users_types_model->getType($typeID, false) ) )
		{
			view::setError(__('no_type', 'users_types'));
			router::redirect('cp/users/types');
		}

		$fields = array();
		// Do we have an existing type?
		if ( $typeID )
		{
			// Get text fields
			$fields = array('' => __('none', 'system'));
			foreach ( $this->fields_model->getFields('users', $typeID) as $field )
			{
				if ( $field['type'] == 'text' )
				{
					$fields[$field['keyword']] = $field['name'];
				}
			}
		}

		// Assign vars
		view::assign(array('typeID' => $typeID, 'type' => $type, 'fields' => $fields));

		// Process form values
		if ( input::post('do_save_type') )
		{
			$this->_saveType($typeID, $type, $fields);
		}

		// Set title
		view::setTitle($typeID ? __('type_edit', 'users_types') : __('type_new', 'users_types'));

		// Set trail
		view::setTrail('cp/users/types/edit/' . ( $typeID ? $typeID : '' ), ( $typeID ? __('type_edit', 'users_types') . ' - ' . text_helper::entities($type['name']) : __('type_new', 'users_types') ));

		// Set trail
		if ( $typeID )
		{
			// Assign actions
			view::setAction('cp/system/fields/users/browse/' . $typeID, __('profile_questions', 'users_types'), array('class' => 'icon-text icon-system-fields'));
		}
		if ( count(config::item('languages', 'core', 'keywords')) > 1 )
		{
			view::setAction('translate', '');
		}

		// Load view
		view::load('cp/users/types/edit');
	}

	protected function _saveType($typeID, $type, $fields)
	{
		// Check if demo mode is enabled
		if ( input::demo() ) return false;

		// Rules array
		$rules = array();

		// Input data array
		$input = array('keyword', 'field_name_1', 'field_name_2');

		// Name field
		foreach ( config::item('languages', 'core', 'keywords') as $languageID => $language )
		{
			$rules['name_' . $language] = array(
				'label' => __('name', 'system') . ( count(config::item('languages', 'core', 'keywords')) > 1 ? ' [' . config::item('languages', 'core', 'names', $languageID) . ']' : '' ),
				'rules' => array('trim', 'required', 'max_length' => 128)
			);
			$input[] = 'name_' . $language;
		}

		// Keyword field
		$rules['keyword'] = array(
			'label' => __('keyword', 'system'),
			'rules' => array('required', 'max_length' => 32, 'alpha_dash', 'strtolower', 'callback__is_unique_keyword' => $typeID)
		);

		// Is this an existing type?
		if ( $typeID )
		{
			$rules['field_name_1'] = array(
				'label' => __('type_fields_name', 'users_types'),
				'rules' => array('max_length' => 128, 'callback__is_valid_field_name' => array('field_name_1', $fields))
			);
			$rules['field_name_2'] = array(
				'label' => __('type_fields_name', 'users_types'),
				'rules' => array('max_length' => 128, 'callback__is_valid_field_name' => array('field_name_2', $fields))
			);
		}

		// Assign rules
		validate::setRules($rules);

		// Validate fields
		if ( !validate::run() )
		{
			return false;
		}

		// Get post data
		$data = input::post($input);

		// Save user type
		if ( !( $newTypeID = $this->users_types_model->saveType($typeID, $data) ) )
		{
			if ( $typeID )
			{
				view::setError(__('db_no_rename', 'system_fields'));
			}
			else
			{
				view::setError(__('db_no_create', 'system_fields'));
			}
			return false;
		}

		// Is this an existing type?
		if ( $typeID )
		{
			$fields = config::item('usertypes', 'core', 'fields', $typeID);
			if ( ( $data['field_name_1'] || $data['field_name_2'] ) && ( $fields[1] != $data['field_name_1'] || $fields[2] != $data['field_name_2'] ) )
			{
				$this->users_types_model->updateNames($typeID, $data['field_name_1'], $data['field_name_2']);
			}
		}

		// Success
		view::setInfo(__('type_saved', 'users_types'));

		router::redirect('cp/users/types/edit/' . $newTypeID);
	}

	public function delete()
	{
		// Check if demo mode is enabled
		if ( input::demo(1, 'cp/users/types') ) return false;

		// Get URI vars
		$typeID = (int)uri::segment(5);

		// Is this one of the system types?
		if ( $typeID == config::item('type_default_id', 'users') )
		{
			view::setError(__('type_delete_system', 'users_types'));
			router::redirect('cp/users/types');
		}

		// Is this member's own type?
		if ( $typeID == session::item('type_id') )
		{
			view::setError(__('type_delete_self', 'users_types'));
			router::redirect('cp/users/types');
		}

		// Get user type
		if ( !$typeID || !( $type = $this->users_types_model->getType($typeID) ) )
		{
			view::setError(__('no_type', 'users_types'));
			router::redirect('cp/users/types');
		}

		// Do we have any members of this type?
		if ( $this->users_types_model->isUsers($typeID) )
		{
			view::setError(__('type_delete_users', 'users_types'));
			router::redirect('cp/users/types');
		}

		// Delete user type
		if ( !$this->users_types_model->deleteType($typeID, $type) )
		{
			view::setError(__('db_no_drop', 'system_fields'));
			router::redirect('cp/users/types');
		}

		// Success
		view::setInfo(__('type_deleted', 'users_types'));

		router::redirect('cp/users/types');
	}

	public function _is_unique_keyword($keyword, $typeID)
	{
		// Get user types
		$types = $this->users_types_model->getTypes();

		// Check if keyword already exists
		foreach ( $types as $type )
		{
			if ( $type['keyword'] == $keyword && $type['type_id'] != $typeID )
			{
				validate::setError('_is_unique_keyword', __('type_duplicate_keyword', 'users_types'));
				return false;
			}
		}

		return true;
	}

	public function _is_valid_field_name($field, $keyword, $fields)
	{
		if ( count($fields) > 1 && $field )
		{
			if ( !isset($fields[$field]) )
			{
				validate::setError('_is_valid_field_name', __('type_name_field_invalid', 'users_types'));
				return false;
			}
			//elseif ( $keyword == 'field_name_2' && !input::post('field_name_1') )
			//{
			//	validate::setError('_is_valid_field_name', __('type_name_field_missing', 'users_types'));
			//	return false;
			//}
			elseif ( $keyword == 'field_name_2' && input::post('field_name_1') && $field == input::post('field_name_1') )
			{
				validate::setError('_is_valid_field_name', __('type_duplicate_field_name', 'users_types'));
				return false;
			}
			elseif ( $keyword == 'field_name_2' && !input::post('field_name_1') )
			{
				return '';
			}
		}

		return true;
	}
}
