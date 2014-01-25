<?php

class CP_Content_Banners_Controller extends Controller
{
	public function __construct()
	{
		parent::__construct();

		// Does user have permission to access this plugin?
		if ( !session::permission('banners_manage', 'banners') )
		{
			view::noAccess();
		}

		if ( uri::segment(5) )
		{
			view::setCustomParam('section', 'content');
			view::setCustomParam('options', config::item('cp_top_nav', 'lists', 'content', 'items', 'content/banners', 'items'));

			view::setTrail('cp/system/plugins', __('content', 'system_navigation'));
			view::setTrail('cp/content/banners/groups', __('banners_groups', 'system_navigation'));
		}

		loader::model('banners/banners');
		loader::model('banners/groups', array(), 'banners_groups_model');
	}

	public function index()
	{
		// Load banner groups by default
		if ( !uri::segment(5) )
		{
			loader::controller('cp/content/banners/groups', array(), 'banners_groups');
			$this->banners_groups->index();
			return;
		}

		$this->browse();
	}

	public function browse()
	{
		// Get URI vars
		$groupID = (int)uri::segment(5);

		// Get group
		if ( !$groupID || !( $group = $this->banners_groups_model->getGroup($groupID) ) )
		{
			view::setError(__('no_group', 'banners'));
			router::redirect('cp/content/banners/groups');
		}

		// Process query string
		$params = $this->parseQuerystring();

		// Get banners
		if ( !( $banners = $this->banners_model->getBanners($groupID, $params) ) )
		{
			view::setInfo(__('no_banners', 'banners'));
		}

		// Create table grid
		$grid = array(
			'uri' => 'cp/content/banners/browse/' . $groupID,
			'keyword' => 'banners',
			'header' => array(
				'name' => array(
					'html' => __('name', 'system'),
					'class' => 'name',
					'sortable' => true,
				),
				'total_views' => array(
					'html' => __('banner_views', 'banners'),
					'class' => 'views',
					'sortable' => true,
				),
				'total_clicks' => array(
					'html' => __('banner_clicks', 'banners'),
					'class' => 'clicks',
					'sortable' => true,
				),
				'status' => array(
					'html' => __('active', 'system'),
					'class' => 'status',
				),
				'actions' => array(
					'html' => __('actions', 'system'),
					'class' => 'actions',
				),
			),
			'content' => array()
		);

		// Create grid content
		foreach ( $banners as $banner )
		{
			$grid['content'][] = array(
				'name' => array(
					'html' => html_helper::anchor('cp/content/banners/edit/'.$groupID.'/'.$banner['banner_id'], text_helper::truncate(text_helper::entities($banner['name']), 64)),
				),
				'total_views' => array(
					'html' => $banner['total_views'],
				),
				'total_clicks' => array(
					'html' => $banner['total_clicks'],
				),
				'status' => array(
					'html' => $banner['active'] ? '<span class="label success small">' . __('yes', 'system') . '</span>' : '<span class="label important small">' . __('no', 'system') . '</span>',
				),
				'actions' => array(
					'html' => array(
						'edit' => html_helper::anchor('cp/content/banners/edit/'.$groupID.'/'.$banner['banner_id'], __('edit', 'system'), array('class' => 'edit')),
						'delete' => html_helper::anchor('cp/content/banners/delete/'.$groupID.'/'.$banner['banner_id'], __('delete', 'system'), array('data-html' => __('banner_delete?', 'banners'), 'data-role' => 'confirm', 'class' => 'delete')),
					)
				),
			);
		}

		// Filter hooks
		hook::filter('cp/content/banners/browse/grid', $grid);

		// Assign vars
		view::assign(array('grid' => $grid));

		// Set title
		view::setTitle(__('banners_manage', 'system_navigation'));

		// Set trail
		view::setTrail('cp/content/banners/groups/edit/' . $groupID, __('group_edit', 'banners') . ' - ' . text_helper::entities($group['name']));
		view::setTrail('cp/content/banners/browse/' . $groupID, __('banners', 'banners'));

		// Assign actions
		view::setAction('cp/content/banners/edit/' . $groupID, __('banner_new', 'banners'), array('class' => 'icon-text icon-banners-new'));

		// Load view
		view::load('cp/content/banners/browse');
	}

	public function edit()
	{
		// Get URI vars
		$groupID = (int)uri::segment(5);
		$bannerID = (int)uri::segment(6);

		// Assign vars
		view::assign(array('groupID' => $groupID, 'bannerID' => $bannerID));

		// Get group
		if ( !$groupID || !( $group = $this->banners_groups_model->getGroup($groupID) ) )
		{
			view::setError(__('no_group', 'banners'));
			router::redirect('cp/content/banners/groups');
		}

		// Assign vars
		view::assign(array('group' => $group));

		// Get banner
		$banner = array();
		if ( $bannerID && !( $banner = $this->banners_model->getBanner($bannerID) ) )
		{
			view::setError(__('no_banner', 'banners'));
			router::redirect('cp/content/banners/' . $groupID);
		}

		// Assign vars
		view::assign(array('banner' => $banner));

		// Process form values
		if ( input::post('do_save_banner') )
		{
			$this->_saveBanner($groupID, $bannerID, $banner);
		}

		// Set title
		view::setTitle($bannerID ? __('banner_edit', 'banners') : __('banner_new', 'banners'));

		// Set trail
		view::setTrail('cp/content/banners/groups/edit/' . $groupID, __('group_edit', 'banners') . ' - ' . text_helper::entities($group['name']));
		view::setTrail('cp/content/banners/browse/' . $groupID, __('banners', 'banners'));
		view::setTrail('cp/content/banners/edit/' . $groupID . '/' . ( $bannerID ? $bannerID : '' ), ( $bannerID ? __('banner_edit', 'banners') . ' - ' . text_helper::entities($banner['name'] ) : __('banner_new', 'banners') ));

		// Load view
		view::load('cp/content/banners/edit');
	}

	protected function _saveBanner($groupID, $bannerID, $bannerOld)
	{
		// Check if demo mode is enabled
		if ( input::demo() ) return false;

		// Create rules
		$rules = array(
			'name' => array(
				'label' => __('name', 'system'),
				'rules' => array('trim', 'required', 'max_length' => 255),
			),
			'keyword' => array(
				'label' => __('keyword', 'system'),
				'rules' => array('trim', 'required', 'max_length' => 128, 'alpha_dash', 'strtolower', 'callback__is_unique_keyword' => array($groupID, $bannerID)),
			),
			'code' => array(
				'label' => __('banner_code', 'banners'),
				'rules' => array('trim', 'required'),
			),
			'count_views' => array(
				'label' => __('banner_count_views', 'banners'),
				'rules' => array('trim', 'intval'),
			),
			'total_views' => array(
				'label' => __('banner_views', 'banners'),
				'rules' => array('trim', 'intval'),
			),
			'count_clicks' => array(
				'label' => __('banner_count_clicks', 'banners'),
				'rules' => array('trim', 'intval'),
			),
			'total_clicks' => array(
				'label' => __('banner_clicks', 'banners'),
				'rules' => array('trim', 'intval'),
			),
			'secure_mode' => array(
				'label' => __('banner_secure_mode', 'banners'),
				'rules' => array('trim', 'intval'),
			),
			'active' => array(
				'label' => __('active', 'system'),
				'rules' => array('trim', 'intval'),
			)
		);

		// Assign rules
		validate::setRules($rules);

		// Validate fields
		if ( !validate::run() )
		{
			return false;
		}

		// Banner data
		$bannerData = input::post(array('name', 'keyword', 'code', 'count_views', 'total_views', 'count_clicks', 'total_clicks', 'secure_mode', 'active'));
		$bannerData['group_id'] = $groupID;

		// Save banner
		if ( !($bannerID = $this->banners_model->saveBanner($groupID, $bannerID, $bannerData)) )
		{
			if ( !validate::getTotalErrors() )
			{
				view::setError(__('save_error', 'system'));
			}
			return false;
		}

		// Success
		view::setInfo(__('banner_saved', 'banners'));

		router::redirect('cp/content/banners/edit/' . $groupID . '/' . $bannerID);
	}

	public function delete()
	{
		// Get URI vars
		$groupID = (int)uri::segment(5);
		$bannerID = (int)uri::segment(6);

		// Check if demo mode is enabled
		if ( input::demo(1, 'cp/content/banners/browse/' . $groupID) ) return false;

		// Get banner
		if ( !$bannerID || !( $banner = $this->banners_model->getBanner($bannerID) ) )
		{
			view::setError(__('no_banner', 'banners'));
			router::redirect('cp/content/banners/browse/' . $groupID);
		}

		$this->banners_model->deleteBanner($groupID, $bannerID, $banner);

		view::setInfo(__('banner_deleted', 'banners'));
		router::redirect('cp/content/banners/browse/' . $groupID);
	}

	public function _is_unique_keyword($keyword, $groupID, $bannerID)
	{
		// Check if keyword already exists
		if ( !$this->banners_model->isUniqueKeyword($groupID, $keyword, $bannerID) )
		{
			validate::setError('_is_unique_keyword', __('banner_keyword_duplicate', 'banners'));
			return false;
		}

		return true;
	}

	protected function parseQuerystring()
	{
		$qstring = array();

		// Get order field and direction
		$qstring['orderby'] = input::post_get('o') && in_array(input::post_get('o'), array('name', 'total_views', 'total_clicks')) ? input::post_get('o') : 'name';
		$qstring['orderdir'] = input::post_get('d') && in_array(input::post_get('d'), array('asc', 'desc')) ? input::post_get('d') : 'asc';
		$qstring['order'] = $qstring['orderby'] ? array($qstring['orderby'] => $qstring['orderdir']) : array();

		// Create url string
		$qstring['url'] = ( $qstring['orderby'] ? 'o=' . $qstring['orderby'] . '&' : '' ) . ( $qstring['orderby'] && $qstring['orderdir'] ? 'd=' . $qstring['orderdir'] . '&' : '' );

		// Assign vars
		view::assign(array('qstring' => $qstring));

		return $qstring;
	}
}
