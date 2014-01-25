<?php

class CP_Content_Pages_Controller extends Controller
{
	public function __construct()
	{
		parent::__construct();

		// Does user have permission to access this plugin?
		if ( !session::permission('pages_manage', 'pages') )
		{
			view::noAccess();
		}

		view::setCustomParam('section', 'content');
		view::setCustomParam('options', config::item('cp_top_nav', 'lists', 'content', 'items', 'content/pages', 'items'));

		view::setTrail('cp/system/plugins', __('content', 'system_navigation'));

		loader::model('pages/pages');
	}

	public function index()
	{
		$this->browse();
	}

	public function browse()
	{
		// Get URI vars
		$parentID = (int)uri::segment(5);

		// Get parent pages
		$parents = $parentID ? $this->pages_model->getParents($parentID) : array();

		if ( $parentID && !$parents )
		{
			view::setError(__('no_parent', 'pages'));
			router::redirect('cp/content/pages');
		}

		// Parameters
		$params = array(
			'join_columns' => array("`p`.`parent_id`=" . (int)$parentID),
			'join_items' => array(),
		);

		// Process query string
		$qstring = $this->parseQuerystring();

		// Check form action
		if ( input::post('do_action') )
		{
			// Delete selected pages
			if ( input::post('action') == 'delete' )
			{
				if ( input::post('page_id') && is_array(input::post('page_id')) )
				{
					foreach ( input::post('page_id') as $pageID )
					{
						$pageID = (int)$pageID;
						if ( $pageID && $pageID > 0 )
						{
							$this->delete($parentID, $pageID);
						}
					}
				}
			}

			// Success
			view::setInfo(__('action_applied', 'system'));
			router::redirect('cp/content/pages/browse/' . $parentID . ( $qstring['url'] ? '?' . $qstring['url'] : '' ));
		}

		// Get pages
		if ( !( $pages = $this->pages_model->getPages($parentID, 'in_list', $params['join_columns'], $params['join_items'], $qstring['order']) ) )
		{
			view::setError(__('no_pages', 'pages'));
		}

		// Create table grid
		$grid = array(
			'uri' => 'cp/content/pages/browse/' . $parentID,
			'keyword' => 'pages',
			'header' => array(
				'check' => array(
					'html' => 'page_id',
					'class' => 'check',
				),
				'data_title_' . session::item('language') => array(
					'html' => __('name', 'system'),
					'class' => 'name',
					'sortable' => true,
				),
				'status' => array(
					'html' => __('status', 'system'),
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
		foreach ( $pages as $page )
		{
			$grid['content'][] = array(
				'check' => array(
					'html' => $page['page_id'],
				),
				'data_title_' . session::item('language') => array(
					'html' => html_helper::anchor('cp/content/pages/edit/' . $page['parent_id'] . '/' . $page['page_id'], text_helper::truncate($page['data_title'], 64)),
				),
				'status' => array(
					'html' => $page['active'] ? '<span class="label success small">' . __('active', 'system') . '</span>' : '<span class="label important small">' . __('inactive', 'system') . '</span>',
				),
				'actions' => array(
					'html' => array(
						'pages' => html_helper::anchor('cp/content/pages/browse/' . $page['page_id'], __('page_inner', 'pages'), array('class' => 'pages')),
						'edit' => html_helper::anchor('cp/content/pages/edit/' . $page['parent_id'] . '/' . $page['page_id'], __('edit', 'system'), array('class' => 'edit')),
						'delete' => html_helper::anchor('cp/content/pages/delete/' . $page['parent_id'] . '/' . $page['page_id'] . '?' . $qstring['url'], __('delete', 'system'), array('data-html' => __('page_delete?', 'pages'), 'data-role' => 'confirm', 'class' => 'delete')),
					),
				),
			);
		}

		// Create actions
		$actions = array(
			0 => __('select', 'system'),
			'delete' => __('delete', 'system')
		);

		// Assign vars
		view::assign(array('grid' => $grid, 'actions' => $actions));

		// Set title
		view::setTitle(__('pages_manage', 'system_navigation'));

		// Set trail
		view::setTrail('cp/content/pages/browse', __('pages', 'pages'));
		foreach ( $parents as $parent )
		{
			view::setTrail('cp/content/pages/edit/' . $parent['parent_id'] . '/' . $parent['page_id'], $parent['data_title']);
		}

		// Assign actions
		view::setAction('cp/content/pages/edit/' . $parentID, __('page_new', 'pages'), array('class' => 'icon-text icon-pages-new'));

		// Load view
		view::load('cp/content/pages/browse');
	}

	public function edit()
	{
		// Get URI vars
		$parentID = (int)uri::segment(5);
		$pageID = (int)uri::segment(6);

		// Get fields
		$fields = $this->fields_model->getFields('pages', 0, 'edit');

		// Get parent pages
		$parents = $parentID ? $this->pages_model->getParents($parentID) : array();
		$parent = $parentID ? end($parents) : array();

		if ( $parentID && !$parents )
		{
			view::setError(__('no_parent', 'pages'));
			router::redirect('cp/content/pages');
		}

		// Get page
		$page = array();
		if ( $pageID && !( $page = $this->pages_model->getPage($pageID, $fields, array('escape' => false, 'parse' => false, 'multilang' => true)) ) )
		{
			view::setError(__('no_page', 'pages'));
			router::redirect('cp/content/pages/browse/' . $parentID);
		}

		$trail = array();
		foreach ( $parents as $parent )
		{
			$trail[] = $parent['keyword'];
		}

		// Options
		$options = array();

		// Custom filename
		$options[] = array(
			'name' => __('page_custom_file', 'pages', array(), array(), false),
			'keyword' => 'file_name',
			'type' => 'text',
			'value' => '',
			'class' => 'input-xlarge',
			'rules' => array('trim', 'callback__is_valid_file_name'),
		);

		// Do we need to add enable comments field?
		if ( config::item('page_comments', 'pages') )
		{
			$options[] = array(
				'name' => __('comments_enable', 'comments', array(), array(), false),
				'keyword' => 'comments',
				'type' => 'boolean',
				'value' => 1,
				'rules' => 'intval',
			);
		}

		// Do we need to add enable likes field?
		if ( config::item('page_rating', 'pages') == 'likes' )
		{
			$options[] = array(
				'name' => __('likes_enable', 'comments', array(), array(), false),
				'keyword' => 'likes',
				'type' => 'boolean',
				'value' => 1,
				'rules' => 'intval',
			);
		}
		// Do we need to add enable votes field?
		elseif ( config::item('page_rating', 'pages') == 'stars' )
		{
			$options[] = array(
				'name' => __('rating_enable', 'comments', array(), array(), false),
				'keyword' => 'votes',
				'type' => 'boolean',
				'value' => 1,
				'rules' => 'intval',
			);
		}

		// Trail field
		$options[] = array(
			'name' => __('page_trail', 'pages', array(), array(), false),
			'keyword' => 'trail',
			'type' => 'boolean',
			'value' => 1,
			'rules' => 'intval',
		);

		// Active field
		$options[] = array(
			'name' => __('active', 'system', array(), array(), false),
			'keyword' => 'active',
			'type' => 'boolean',
			'value' => 1,
			'rules' => 'intval',
		);

		// Assign vars
		view::assign(array('parentID' => $parentID, 'pageID' => $pageID, 'parent' => $parent, 'page' => $page, 'fields' => $fields, 'options' => $options));

		// Process form values
		if ( input::post('do_save_page') )
		{
			$this->_savePage($pageID, $parentID, $page, $fields, $options, $trail);
		}

		// Set title
		view::setTitle(__('page_edit', 'pages'));

		// Set trail
		view::setTrail('cp/content/pages/browse', __('pages', 'pages'));
		foreach ( $parents as $parent )
		{
			view::setTrail('cp/content/pages/edit/' . $parent['parent_id'] . '/' . $parent['page_id'], $parent['data_title']);
		}
		view::setTrail('cp/content/pages/edit/' . $parentID . '/' . ( $pageID ? $pageID : '' ), ( $pageID ? __('page_edit', 'pages') . ' - ' . text_helper::entities($page['data_title_' . session::item('language')]) : __('page_new', 'pages') ));

		// Assign actions
		if ( $pageID )
		{
			view::setAction(implode('/', $trail) . ( $trail ? '/' : '' ) . $page['keyword'], __('page_view', 'pages'), array('class' => 'icon-text icon-pages-view'));
			view::setAction('cp/content/pages/browse/' . $pageID, __('page_inner', 'pages'), array('class' => 'icon-text icon-pages-inner'));
		}
		elseif ( $parentID )
		{
			view::setAction('cp/content/pages/browse/' . $parentID, __('page_inner', 'pages'), array('class' => 'icon-text icon-pages-inner'));
		}
		if ( count(config::item('languages', 'core', 'keywords')) > 1 )
		{
			view::setAction('translate', '');
		}

		// Load view
		view::load('cp/content/pages/edit');
	}

	protected function _savePage($pageID, $parentID, $page, $fields, $options, $trail)
	{
		// Check if demo mode is enabled
		if ( input::demo() ) return false;

		// Extra rules
		$rules = array();
		foreach ( $options as $option )
		{
			if ( isset($option['rules']) )
			{
				$rules[$option['keyword']] = array(
					'label' => $option['name'],
					'rules' => $option['rules'],
				);
			}
		}

		$rules['keyword'] = array(
			'label' => __('keyword', 'system'),
			'rules' => array('trim', 'required', 'max_length' => 128, 'callback__is_valid_keyword' => array($parentID, $pageID)),
		);

		// Validate form values
		if ( !$this->fields_model->validateValues($fields, $rules) )
		{
			return false;
		}

		// Did keyword change?
		if ( $pageID && $page['system'] && strcmp($page['keyword'], input::post('keyword')) )
		{
			validate::setFieldError('keyword', __('page_system_rename', 'pages'));
			return false;
		}

		// Extras
		$extra = array();
		$extra['parent_id'] = $parentID;
		$extra['location'] = ( $trail ? implode('/', $trail) . '/' : '' ) . input::post('keyword');
		$extra['keyword'] = input::post('keyword');
		$extra['file_name'] = input::post('file_name');
		$extra['comments'] = config::item('page_comments', 'pages') ? (int)input::post('comments') : 1;
		$extra['likes'] = config::item('page_rating', 'pages') == 'likes' ? (int)input::post('likes') : 1;
		$extra['votes'] = config::item('page_rating', 'pages') == 'stars' ? (int)input::post('votes') : 1;
		$extra['trail'] = (int)input::post('trail');
		$extra['active'] = (int)input::post('active');

		// Save page
		if ( !( $pageID = $this->pages_model->savePageData($pageID, $parentID, $page, $fields, $extra) ) )
		{
			if ( !validate::getTotalErrors() )
			{
				view::setError(__('save_error', 'system'));
			}
			return false;
		}

		// Success
		view::setInfo(__('page_saved', 'pages'));
		router::redirect('cp/content/pages/edit/' . $parentID . '/' . $pageID);
	}

	public function delete($parentID = false, $actionID = false)
	{
		// Get URI vars
		$parentID = $parentID ? $parentID : (int)uri::segment(5);
		$pageID = $actionID ? $actionID : (int)uri::segment(6);

		// Check if demo mode is enabled
		if ( input::demo(1, 'cp/content/pages/browse/' . $parentID) ) return false;

		// Get parent
		if ( $parentID && !( $parent = $this->pages_model->getPage($parentID )) )
		{
			view::setError(__('no_parent', 'pages'));
			router::redirect('cp/content/pages/browse/' . $parentID);
		}

		// Get page
		if ( !$pageID || !( $page = $this->pages_model->getPage($pageID) ) || $page['parent_id'] != $parentID )
		{
			view::setError(__('no_page', 'pages'));
			router::redirect('cp/content/pages/browse/' . $parentID);
		}
		elseif ( $page['system'] )
		{
			view::setError(__('page_system_delete', 'pages'));
			router::redirect('cp/content/pages/browse/' . $parentID);
		}

		// Delete page
		$this->pages_model->deletePage($pageID, $page);

		// Is this an action call?
		if ( $actionID ) return;

		// Process query string
		$qstring = $this->parseQuerystring();

		// Success
		view::setInfo(__('page_deleted', 'pages'));
		router::redirect('cp/content/pages/browse/' . $parentID . '?' . $qstring['url']);
	}

	public function _is_valid_keyword($keyword, $parentID, $pageID)
	{
		if ( ( $return = $this->pages_model->isValidKeyword($keyword, $parentID, $pageID) ) !== true )
		{
			if ( $return == 'numeric' )
			{
				validate::setError('_is_valid_keyword', __('page_keyword_numeric', 'pages'));
			}
			elseif ( $return == 'duplicate' )
			{
				validate::setError('_is_valid_keyword', __('page_keyword_duplicate', 'pages'));
			}
			elseif ( $return == 'reserved' )
			{
				validate::setError('_is_valid_keyword', __('page_keyword_reserved', 'pages'));
			}
			else
			{
				validate::setError('_is_valid_keyword', __('page_keyword_alpha', 'pages'));
			}
			return false;
		}

		return true;
	}

	public function _is_valid_file_name($file)
	{
		$path1 = DOCPATH . 'views/pages/' . $file . EXT;
		$path2 = BASEPATH . 'templates/' . session::item('template') . '/' . $file . EXT;

		if ( $file && !is_file($path1) && !is_file($path2) )
		{
			validate::setError('_is_valid_file_name', __('page_custom_file_missing', 'pages', array('%1' => '<br/>' . $path1, '%2' => '<br/>' . $path2)));
			return false;
		}

		return true;
	}

	protected function parseQuerystring()
	{
		$qstring = array();

		// Get order field and direction
		$qstring['orderby'] = input::post_get('o') && in_array(input::post_get('o'), array('data_title_' . session::item('language'), 'total_views', 'total_rating', 'total_votes', 'total_likes', 'total_comments', 'order_id')) ? input::post_get('o') : '';
		$qstring['orderdir'] = input::post_get('d') && in_array(input::post_get('d'), array('asc', 'desc')) ? input::post_get('d') : 'desc';
		$qstring['order'] = $qstring['orderby'] ? array($qstring['orderby'] => $qstring['orderdir']) : array();

		// Create url string
		$qstring['url'] = ( $qstring['orderby'] ? 'o=' . $qstring['orderby'] . '&' : '' ) .
			( $qstring['orderby'] && $qstring['orderdir'] ? 'd=' . $qstring['orderdir'] : '' );

		// Assign vars
		view::assign(array('qstring' => $qstring));

		return $qstring;
	}
}
