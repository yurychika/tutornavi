<?php

class CP_Content_Banners_Groups_Controller extends Controller
{
	public function __construct()
	{
		parent::__construct();

		// Does user have permission to access this plugin?
		if ( !session::permission('banners_manage', 'banners') )
		{
			view::noAccess();
		}

		view::setCustomParam('section', 'content');
		view::setCustomParam('options', config::item('cp_top_nav', 'lists', 'content', 'items', 'content/banners', 'items'));

		view::setTrail('cp/system/plugins', __('content', 'system_navigation'));
		view::setTrail('cp/content/banners/groups', __('banners_groups', 'system_navigation'));

		loader::model('banners/groups', array(), 'banners_groups_model');
	}

	public function index()
	{
		$this->browse();
	}

	public function browse()
	{
		// Get banner groups
		if ( !( $groups = $this->banners_groups_model->getGroups() ) )
		{
			view::setInfo(__('no_groups', 'banners'));
		}

		// Create table grid
		$grid = array(
			'uri' => 'cp/content/banners/groups/browse',
			'keyword' => 'bannersgroups',
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
		foreach ( $groups as $group )
		{
			$grid['content'][] = array(
				'name' => array(
					'html' => html_helper::anchor('cp/content/banners/groups/edit/'.$group['group_id'], text_helper::truncate(text_helper::entities($group['name']), 64)),
				),
				'actions' => array(
					'html' => array(
						'banners' => html_helper::anchor('cp/content/banners/browse/' . $group['group_id'], __('banners', 'banners'), array('class' => 'banners')),
						'edit' => html_helper::anchor('cp/content/banners/groups/edit/' . $group['group_id'], __('edit', 'system'), array('class' => 'edit')),
						'delete' => html_helper::anchor('cp/content/banners/groups/delete/' . $group['group_id'], __('delete', 'system'), array('data-html' => __('group_delete?', 'banners'), 'data-role' => 'confirm', 'class' => 'delete')),
					)
				),
			);
		}

		// Filter hooks
		hook::filter('cp/content/banners/groups/browse/grid', $grid);

		// Assign vars
		view::assign(array('grid' => $grid));

		// Set title
		view::setTitle(__('banners_groups_manage', 'system_navigation'));

		// Set title
		view::setAction('cp/content/banners/groups/edit', __('group_new', 'banners'), array('class' => 'icon-text icon-banners-groups-new'));

		// Load view
		view::load('cp/content/banners/groups/browse');
	}

	public function edit()
	{
		// Get URI vars
		$groupID = (int)uri::segment(6);

		// Get group
		$group = array();
		if ( $groupID && !( $group = $this->banners_groups_model->getGroup($groupID) ) )
		{
			view::setError(__('no_group', 'banners'));
			router::redirect('cp/content/banners/groups');
		}

		// Assign vars
		view::assign(array('groupID' => $groupID, 'group' => $group));

		// Process form values
		if ( input::post('do_save_group') )
		{
			$this->_saveGroup($groupID);
		}

		// Set title
		view::setTitle($groupID ? __('group_edit', 'banners') : __('group_new', 'banners'));

		// Set trail
		view::setTrail('cp/content/banners/groups/edit/' . ( $groupID ? $groupID : '' ), ( $groupID ? __('group_edit', 'banners') . ' - ' . text_helper::entities($group['name']) : __('group_new', 'banners') ));

		// Assign actions
		if ( $groupID )
		{
			view::setAction('cp/content/banners/browse/' . $groupID, __('banners', 'banners'), array('class' => 'icon-text icon-banners'));
		}

		// Load view
		view::load('cp/content/banners/groups/edit');
	}

	protected function _saveGroup($groupID)
	{
		// Check if demo mode is enabled
		if ( input::demo() ) return false;

		// Create rules
		$rules = array(
			'name' => array(
				'label' => __('name', 'system'),
				'rules' => array('trim', 'required', 'max_length' => 255)
			),
			'keyword' => array(
				'label' => __('keyword', 'system'),
				'rules' => array('trim', 'required', 'max_length' => 128, 'alpha_dash', 'strtolower', 'callback__is_unique_keyword' => $groupID)
			)
		);

		// Assign rules
		validate::setRules($rules);

		// Validate fields
		if ( !validate::run() )
		{
			return false;
		}

		// Get post data
		$groupData = input::post(array('name', 'keyword'));

		// Save banner group
		if ( !( $groupID = $this->banners_groups_model->saveGroup($groupID, $groupData) ) )
		{
			view::setError(__('save_error', 'system'));
			return false;
		}

		// Success
		view::setInfo(__('group_saved', 'banners'));

		router::redirect('cp/content/banners/groups/edit/' . $groupID);
	}

	public function delete()
	{
		// Check if demo mode is enabled
		if ( input::demo(1, 'cp/content/banners') ) return false;

		// Get URI vars
		$groupID = (int)uri::segment(6);

		// Get banner group
		if ( !$groupID || !( $group = $this->banners_groups_model->getGroup($groupID) ) )
		{
			view::setError(__('no_group', 'banners'));
			router::redirect('cp/content/banners/groups');
		}

		// Delete banner group
		$this->banners_groups_model->deleteGroup($groupID, $group);

		// Success
		view::setInfo(__('group_deleted', 'banners'));

		router::redirect('cp/content/banners/groups');
	}

	public function _is_unique_keyword($keyword, $groupID)
	{
		// Get banner groups
		$groups = $this->banners_groups_model->getGroups();

		// Check if keyword already exists
		foreach ( $groups as $group )
		{
			if ( $group['keyword'] == $keyword && $group['group_id'] != $groupID )
			{
				validate::setError('_is_unique_keyword', __('group_keyword_duplicate', 'banners'));
				return false;
			}
		}

		return true;
	}
}
