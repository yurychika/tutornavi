<?php

class CP_System_Templates_Navigation_Controller extends Controller
{
	public function __construct()
	{
		parent::__construct();

		// Does user have permission to access this plugin?
		if ( !session::permission('templates_manage', 'system') )
		{
			view::noAccess();
		}

		view::setCustomParam('section', 'system');
		view::setCustomParam('options', config::item('cp_top_nav', 'lists', 'system', 'items', 'system/templates', 'items'));

		view::setTrail('cp/system/config/system', __('system', 'system_navigation'));
		view::setTrail('cp/system/templates', __('system_templates', 'system_navigation'));
		view::setTrail('cp/system/templates/navigation', __('system_templates_navigation', 'system_navigation'));

		loader::model('system/lists');
	}

	public function index()
	{
		$this->browse();
	}

	public function browse()
	{
		// Get lists
		if ( !( $lists = $this->lists_model->getLists() ) )
		{
			router::redirect('cp/system/config/system');
		}

		// Create table grid
		$grid = array(
			'uri' => 'cp/system/templates/navigation',
			'keyword' => 'templates',
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
		foreach ( $lists as $type => $name )
		{
			$grid['content'][] = array(
				'name' => array(
					'html' => html_helper::anchor('cp/system/templates/navigation/view/' . $type, $name),
				),
				'actions' => array(
					'html' => array(
						'view' => html_helper::anchor('cp/system/templates/navigation/view/' . $type, __('details', 'system'), array('class' => 'view')),
					),
				),
			);
		}

		// Filter hooks
		hook::filter('cp/system/templates/navigation/browse/grid', $grid);

		// Assign vars
		view::assign(array('grid' => $grid));

		// Set title
		view::setTitle(__('system_templates_navigation_manage', 'system_navigation'));

		// Load view
		view::load('cp/system/templates/navigation/browse');
	}

	public function view()
	{
		// Get URI vars
		$listID = uri::segment(6);

		// Get list
		if ( !$listID || !( $list = $this->lists_model->getList($listID) ) )
		{
			router::redirect('cp/system/config/system');
		}

		// Did we submit the form?
		if ( input::post('action') == 'reorder' && input::post('ids') )
		{
			$this->_reorderItems();
		}

		// Get items
		if ( !( $items = $this->lists_model->getItems($listID) ) )
		{
			router::redirect('cp/system/config/system');
		}

		// Create table grid
		$grid = array(
			'uri' => 'cp/system/templates/navigation',
			'keyword' => 'templates',
			'header' => array(
				'name' => array(
					'html' => __('name', 'system'),
					'class' => 'name',
				),
				'active' => array(
					'html' => __('active', 'system'),
					'class' => 'status',
				),
			),
			'content' => array()
		);

		// Create grid content
		foreach ( $items as $item )
		{
			$grid['content'][] = array(
				'name' => array(
					'html' => $item['name'],
				),
				'status' => array(
					'html' => html_helper::anchor('cp/system/templates/navigation/togglestatus/' . $item['item_id'], $item['active'] ? __('yes', 'system') : __('no', 'system'), array('class' => $item['active'] ? 'label success small' : 'label important small')),
				),
			);
		}

		// Filter hooks
		hook::filter('cp/system/templates/navigation/browse/grid', $grid);

		// Assign vars
		view::assign(array('grid' => $grid, 'items' => $items, 'listID' => $listID));

		// Set title
		view::setTitle(__('system_templates_navigation_manage', 'system_navigation'));

		// Set trail
		view::setTrail('cp/system/templates/navigation/view/' . $listID, $list['name']);

		// Set actions
		view::setAction('#', __('done', 'system'), array('class' => 'icon-text icon-system-done', 'onclick' => 'saveSortable();return false;', 'id' => 'actions_link_save'));
		view::setAction('#', __('cancel', 'system'), array('class' => 'icon-text icon-system-cancel', 'onclick' => 'cancelSortable();return false;', 'id' => 'actions_link_cancel'));
		view::setAction('#', __('reorder', 'system'), array('class' => 'icon-text icon-system-sort', 'onclick' => 'switchSortable();return false;', 'id' => 'actions_link_reorder'));

		// Include sortable vendor files
		view::includeJavascript('externals/html5sortable/html5sortable.js');
		view::includeStylesheet('externals/html5sortable/style.css');

		// Load view
		if ( input::isAjaxRequest() )
		{
			view::load('cp/system/templates/navigation/items/browse_' . ( input::post('view') == 'list' ? 'list' :'grid' ));
		}
		else
		{
			view::load('cp/system/templates/navigation/items/browse');
		}
	}

	public function toggleStatus()
	{
		// Get URI vars
		$itemID = uri::segment(6);

		// Get item
		if ( !$itemID || !( $item = $this->lists_model->getItem($itemID) ) )
		{
			router::redirect('cp/system/config/system');
		}

		// Toggle status
		$this->lists_model->toggleItemStatus($item['plugin'], $item['type'], $item['keyword'], $item['active'] ? 0 : 1);

		router::redirect('cp/system/templates/navigation/view/' . $item['type']);
	}

	protected function _reorderItems()
	{
		// Check if demo mode is enabled
		if ( input::demo(0) ) return false;

		// Get submitted item IDs
		$items = input::post('ids');

		// Do we have any item IDs?
		if ( $items && is_array($items) )
		{
			// Loop through item IDs
			$orderID = 1;
			foreach ( $items as $itemID )
			{
				// Update item ID
				$this->lists_model->updateItem($itemID, array('order_id' => $orderID));
				$orderID++;
			}
		}
	}
}
