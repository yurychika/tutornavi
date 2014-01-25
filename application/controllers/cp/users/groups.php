<?php

class CP_Users_Groups_Controller extends Controller
{
	public function __construct()
	{
		parent::__construct();

		// Does user have permission to access this plugin?
		if ( !session::permission('users_groups', 'users') )
		{
			view::noAccess();
		}

		view::setCustomParam('section', 'users');
		view::setCustomParam('options', config::item('cp_top_nav', 'lists', 'users', 'items'));

		view::setTrail('cp/users', __('users', 'system_navigation'));
		view::setTrail('cp/users/groups', __('users_groups', 'system_navigation'));

		loader::model('users/groups', array(), 'users_groups_model');
	}

	public function index()
	{
		$this->browse();
	}

	public function browse()
	{
		// Actions
		$actions = array(
			0 => __('select', 'system'),
			'permissions' => __('permissions_edit', 'users_groups'),
		);

		// Check form action
		if ( input::post('do_action') )
		{
			if ( input::post('action') && isset($actions[input::post('action')]) && input::post('group_id') && is_array(input::post('group_id')) )
			{
				$groups = input::post('group_id');
				if ( isset($groups[0]) && !$groups[0] )
				{
					unset($groups[0]);
				}
				if ( count($groups) > 5 )
				{
					view::setError(__('groups_select_limit', 'users_groups', array('%groups' => 5)));
				}
				else
				{
					router::redirect('cp/users/groups/plugins/' . implode(',', $groups));
				}
			}
			router::redirect('cp/users/groups');
		}

		// Get user groups
		$groups = $this->users_groups_model->getGroups();

		// Create table grid
		$grid = array(
			'uri' => 'cp/users/groups/browse',
			'keyword' => 'usersgroups',
			'header' => array(
				'check' => array(
					'html' => 'group_id',
					'class' => 'check',
				),
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
		foreach ( $groups as $group )
		{
			$grid['content'][] = array(
				'check' => array(
					'html' => $group['group_id'],
				),
				'name' => array(
					'html' => html_helper::anchor('cp/users/groups/edit/' . $group['group_id'], text_helper::truncate($group['name'], 64)),
				),
				'actions' => array(
					'html' => array(
						'permissions' => html_helper::anchor('cp/users/groups/plugins/'.$group['group_id'], __('permissions', 'users_permissions'), array('class' => 'permissions')),
						'edit' => html_helper::anchor('cp/users/groups/edit/' . $group['group_id'], __('edit', 'system'), array('class' => 'edit')),
						'delete' => html_helper::anchor('cp/users/groups/delete/' . $group['group_id'], __('delete', 'system'), array('data-html' => __('group_delete?', 'users_groups'), 'data-role' => 'confirm', 'class' => 'delete')),
					)
				),
			);
		}

		// Filter hooks
		hook::filter('cp/users/groups/browse/grid', $grid);

		// Assign vars
		view::assign(array('grid' => $grid, 'actions' => $actions));

		// Set title
		view::setTitle(__('users_groups_manage', 'system_navigation'));

		// Assign actions
		view::setAction('cp/users/groups/edit', __('group_new', 'users_groups'), array('class' => 'icon-text icon-users-groups-new'));

		// Load view
		view::load('cp/users/groups/browse');
	}

	public function edit()
	{
		// Get URI vars
		$groupID = (int)uri::segment(5);

		// Assign vars
		view::assign(array('groupID' => $groupID));

		// Get user groups
		$groups = array(0 => __('none', 'system'));
		foreach ( $this->users_groups_model->getGroups(false) as $group )
		{
			$groups[$group['group_id']] = $group['name'];
		}

		// Assign vars
		view::assign(array('groups' => $groups));

		// Get group
		$group = array();
		if ( $groupID && !( $group = $this->users_groups_model->getGroup($groupID, false) ) )
		{
			view::setError(__('no_group', 'users_groups'));
			router::redirect('cp/users/groups');
		}

		// Assign vars
		view::assign(array('group' => $group));

		// Process form values
		if ( input::post('do_save_group') )
		{
			$this->_saveGroup($groupID, $groups);
		}

		// Set title
		view::setTitle($groupID ? __('group_edit', 'users_groups') : __('group_new', 'users_groups'));

		// Set trail
		view::setTrail('cp/users/groups/edit/'.($groupID ? $groupID : ''), ($groupID ? __('group_edit', 'users_groups') . ' - ' . text_helper::entities($group['name']) : __('group_new', 'users_groups')));

		// Assign actions
		if ( $groupID )
		{
			view::setAction('cp/users/groups/plugins/'.$groupID, __('permissions', 'users_permissions'), array('class' => 'icon-text icon-users-groups-permissions'));
		}
		if ( count(config::item('languages', 'core', 'keywords')) > 1 )
		{
			view::setAction('translate', '');
		}

		// Load view
		view::load('cp/users/groups/edit');
	}

	protected function _saveGroup($groupID, $groups)
	{
		// Check if demo mode is enabled
		if ( input::demo() ) return false;

		// Create rules
		$rules = array();

		if ( !$groupID )
		{
			$rules['copy'] = array(
				'label' => __('group_copy', 'users'),
				'rules' => array('required')
			);
		}

		// Input data array
		$input = array();

		// Name
		foreach ( config::item('languages', 'core', 'keywords') as $languageID => $language )
		{
			$rules['name_' . $language] = array(
				'label' => __('name', 'system') . ( count(config::item('languages', 'core', 'keywords')) > 1 ? ' [' . config::item('languages', 'core', 'names', $languageID) . ']' : '' ),
				'rules' => array('trim', 'required', 'max_length' => 128)
			);
			$input[] = 'name_' . $language;
		}

		// Assign rules
		validate::setRules($rules);

		// Validate fields
		if ( !validate::run() )
		{
			return false;
		}

		// Copy from group ID
		$copyID = isset($groups[input::post('copy')]) ? input::post('copy') : 0;

		// Get post data
		$data = input::post($input);

		// Save user group
		if ( !( $groupID = $this->users_groups_model->saveGroup($groupID, $data, $copyID) ) )
		{
			view::setError(__('save_error', 'system'));
			return false;
		}

		// Successs
		view::setInfo(__('group_saved', 'users_groups'));

		router::redirect('cp/users/groups/edit/' . $groupID);
	}

	public function plugins()
	{
		// Get URI vars
		$ids = trim(uri::segment(5));
		$groupIDs = explode(',' , $ids);

		// Do we have valid IDs?
		if ( !$groupIDs )
		{
			view::setError(__('no_group', 'users_groups'));
			router::redirect('cp/users/groups');
		}

		// Get groups
		foreach ( $groupIDs as $groupID )
		{
			if ( !$groupID || !is_numeric($groupID) || !( $group = $this->users_groups_model->getGroup($groupID) ) )
			{
				view::setError(__('no_group', 'users_groups'));
				router::redirect('cp/users/groups');
			}
		}

		// Get plugins
		if ( !( $plugins = $this->users_groups_model->getPlugins() ) )
		{
			view::setInfo(__('no_plugins', 'system_plugins'));
			router::redirect('cp/system/config/system');
		}

		// Create table grid
		$grid = array(
			'uri' => 'cp/users/groups/plugins/' . $ids,
			'keyword' => 'usersgroups',
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
		foreach ( $plugins as $plugin => $name )
		{
			$grid['content'][] = array(
				'name' => array(
					'html' => html_helper::anchor('cp/users/groups/permissions/' . $plugin . '/' . $ids, $name),
				),
				'actions' => array(
					'html' => html_helper::anchor('cp/users/groups/permissions/' . $plugin . '/' . $ids, __('edit', 'system'), array('class' => 'edit')),
				),
			);
		}

		// Filter hooks
		hook::filter('cp/system/languages/plugins/grid', $grid);

		// Assign vars
		view::assign(array('grid' => $grid));

		// Set title
		view::setTitle(__('plugin_select', 'system'));

		// Set trail
		if ( count($groupIDs) == 1 )
		{
			view::setTrail('cp/users/groups/edit/' . $ids, __('group_edit', 'users_groups') . ' - ' . $group['name']);
		}
		view::setTrail('cp/users/groups/plugins/' . $ids, __('permissions', 'users_permissions'));

		// Load view
		view::load('cp/users/groups/plugins');
	}

	public function permissions()
	{
		// Get URI vars
		$plugin = uri::segment(5);
		$ids = trim(uri::segment(6));
		$groupIDs = explode(',' , $ids);

		// Do we have valid IDs?
		if ( !$groupIDs )
		{
			view::setError(__('no_group', 'users_groups'));
			router::redirect('cp/users/groups');
		}

		// Get groups
		$groups = array();
		foreach ( $groupIDs as $groupID )
		{
			if ( !$groupID || !is_numeric($groupID) || !( $group = $this->users_groups_model->getGroup($groupID) ) )
			{
				view::setError(__('no_group', 'users_groups'));
				router::redirect('cp/users/groups');
			}

			$groups[$groupID] = $group;
			$groups[$groupID]['cp'] = $this->users_model->getPermissions($groupID, 'system', 'site_access_cp');
		}

		// Does the plugin exist?
		if ( !$plugin || !config::item('plugins', 'core', $plugin) )
		{
			view::setError(__('no_plugin', 'system_plugins'));
			router::redirect('cp/users/groups/plugins/'.$groupID);
		}

		// Assign vars
		view::assign(array('plugin' => $plugin, 'groups' => $groups));

		// Get permissions
		foreach ( $groupIDs as $groupID )
		{
			$permissions[$groupID] = $this->users_groups_model->getPermissions($groupID, $plugin);

			// Loop through settings
			foreach ( array('cp', 'ca') as $section )
			{
				if ( isset($permissions[$groupID][$section]) )
				{
					foreach ( $permissions[$groupID][$section] as $index => $permission )
					{
						if ( $permission['callback'] && method_exists($this, '_'.$permission['callback']) )
						{
							$permissions[$groupID][$section][$index]['items'] = $this->{'_'.$permission['callback']}();
						}

						if ( $permission['type'] == 'checkbox' )
						{
							$permissions[$groupID][$section][$index]['value'] = explode(',', $permission['value']);
							$permissions[$groupID][$section][$index]['value'] = array_combine($permissions[$groupID][$section][$index]['value'], $permissions[$groupID][$section][$index]['value']);
						}
					}
				}
			}
		}

		// Assign vars
		view::assign(array('permissions' => $permissions));

		// Process form values
		if ( input::post('do_save_permissions') )
		{
			$this->_savePermissions($ids, $plugin, $permissions);
		}

		// Set title
		view::setTitle(__('permissions_edit', 'users_permissions'));

		// Set trail
		if ( count($groupIDs) == 1 )
		{
			view::setTrail('cp/users/groups/edit/' . $groupID, __('group_edit', 'users_groups') . ' - ' . $group['name']);
		}
		view::setTrail('cp/users/groups/plugins/' . $ids, __('permissions', 'users_permissions'));
		view::setTrail('cp/users/groups/permissions/' . $plugin.'/' . $ids, text_helper::entities(config::item('plugins', 'core', $plugin, 'name')));

		// Set tabs
		foreach ( current($permissions) as $section => $data )
		{
			view::setTab('#' . $section, __('permissions_' . $section, 'users_permissions'), array('class' => 'permissions_' . $section));
		}

		// Load view
		view::load('cp/users/groups/permissions');
	}

	protected function _savePermissions($ids, $plugin, $permissions)
	{
		// Check if demo mode is enabled
		if ( input::demo() ) return false;

		// Create rules
		$rules = array();
		foreach ( $permissions as $groupID => $group )
		{
			foreach ( array('cp', 'ca') as $section )
			{
				if ( isset($permissions[$groupID][$section]) )
				{
					foreach ( $permissions[$groupID][$section] as $permission )
					{
						$rules[$permission['keyword'] . '_' . $groupID] = array(
							'label' => $permission['name'],
							'rules' => ''
						);
					}
				}
			}
		}

		// Assign rules
		validate::setRules($rules);

		// Validate fields
		if ( !validate::run() )
		{
			return false;
		}

		// Create permissions array
		$data = $orderID = array();
		foreach ( $permissions as $groupID => $group )
		{
			foreach ( array('cp', 'ca') as $section )
			{
				if ( isset($permissions[$groupID][$section]) )
				{
					foreach ( $permissions[$groupID][$section] as $permission )
					{
						$value = input::post($permission['keyword'] . '_' . $groupID);
						if ( $permission['type'] == 'checkbox' )
						{
							$value = $value ? implode(',', $value) : '';
						}
						elseif ( $permission['type'] == 'boolean' || $permission['type'] == 'number' )
						{
							$value = $value ? (int)$value : 0;
						}
						$data[$permission['keyword']] = $value;

						if ( config::item('devmode', 'system') == 2 )
						{
							$orderID[$permission['keyword']] = (int)input::post($permission['keyword'] . '___order');
						}
					}
				}
			}

			// Save user group permissions
			if ( !$this->users_groups_model->savePermissions($groupID, $plugin, $data, $orderID) )
			{
				view::setError(__('save_error', 'system'));
				return false;
			}
		}

		// Successs
		view::setInfo(__('permissions_saved', 'users_permissions'));

		router::redirect('cp/users/groups/permissions/' . $plugin . '/' . $ids);
	}

	public function delete()
	{
		// Check if demo mode is enabled
		if ( input::demo(1, 'cp/users/groups') ) return false;

		// Get URI vars
		$groupID = (int)uri::segment(5);

		// Is this one of the system groups?
		if ( in_array($groupID, array(config::item('group_default_id', 'users'), config::item('group_cancelled_id', 'users'), config::item('group_guests_id', 'users'))) )
		{
			view::setError(__('group_delete_system', 'users_groups'));
			router::redirect('cp/users/groups');
		}

		// Is this member's own group?
		if ( $groupID == session::item('group_id') )
		{
			view::setError(__('group_delete_self', 'users_groups'));
			router::redirect('cp/users/groups');
		}

		// Get user group
		if ( !$groupID || !( $group = $this->users_groups_model->getGroup($groupID) ) )
		{
			view::setError(__('no_group', 'users_groups'));
			router::redirect('cp/users/groups');
		}

		// Do we have any members in this group?
		if ( $this->users_groups_model->isUsers($groupID) )
		{
			view::setError(__('group_delete_users', 'users_groups'));
			router::redirect('cp/users/groups');
		}

		// Delete user group
		if ( !$this->users_groups_model->deleteGroup($groupID, $group) )
		{
			view::setError(__('db_no_column_drop', 'system_fields'));
			router::redirect('cp/users/groups');
		}

		// Success
		view::setInfo(__('group_deleted', 'users_groups'));

		router::redirect('cp/users/groups');
	}

	public function _get_usergroups()
	{
		return config::item('usergroups', 'core');
	}

	public function _get_usertypes()
	{
		return config::item('usertypes', 'core', 'names');
	}
}
