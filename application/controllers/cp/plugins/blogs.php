<?php

class CP_Plugins_Blogs_Controller extends Controller
{
	public $blogsPerPage = 30;

	public function __construct()
	{
		parent::__construct();

		// Does user have permission to access this plugin?
		if ( !session::permission('blogs_manage', 'blogs') )
		{
			view::noAccess();
		}

		view::setCustomParam('section', 'plugins');
		view::setCustomParam('options', config::item('cp_top_nav', 'lists', 'plugins', 'items', 'plugins/blogs', 'items'));

		view::setTrail('cp/system/plugins', __('plugins', 'system_navigation'));
		view::setTrail('cp/plugins/blogs', __('blogs', 'system_navigation'));

		loader::model('blogs/blogs');
	}

	public function index()
	{
		$this->browse();
	}

	public function browse()
	{
		// Parameters
		$params = array(
			'join_columns' => array(),
			'join_items' => array(),
		);

		// Process filters
		$params = $this->parseCounters($params);

		// Process query string
		$qstring = $this->parseQuerystring($params['total']);

		// Actions
		$actions = array(
			0 => __('select', 'system'),
			'approve' => __('approve', 'system'),
			'decline' => __('decline', 'system'),
			'delete' => __('delete', 'system'),
		);

		// Check form action
		if ( input::post('do_action') )
		{
			// Delete selected blogs
			if ( input::post('action') && isset($actions[input::post('action')]) && input::post('blog_id') && is_array(input::post('blog_id')) )
			{
				foreach ( input::post('blog_id') as $blogID )
				{
					$blogID = (int)$blogID;
					if ( $blogID && $blogID > 0 )
					{
						$this->action(input::post('action'), $blogID);
					}
				}
			}

			// Success
			view::setInfo(__('action_applied', 'system'));
			router::redirect('cp/plugins/blogs?' . $qstring['url'] . 'page=' . $qstring['page']);
		}

		// Get blogs
		$blogs = array();
		if ( $params['total'] )
		{
			$blogs = $this->blogs_model->getBlogs('in_list', $params['join_columns'], $params['join_items'], $qstring['order'], $qstring['limit']);
		}

		// Create table grid
		$grid = array(
			'uri' => 'cp/plugins/blogs',
			'keyword' => 'blogs',
			'header' => array(
				'check' => array(
					'html' => 'blog_id',
					'class' => 'check',
				),
				'data_title' => array(
					'html' => __('name', 'system'),
					'class' => 'name',
					'sortable' => true,
				),
				'user' => array(
					'html' => __('user', 'system'),
					'class' => 'user',
				),
				'post_date' => array(
					'html' => __('post_date', 'system'),
					'class' => 'date',
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
		foreach ( $blogs as $blog )
		{
			if ( $blog['active'] == 1 )
			{
				$status = html_helper::anchor('cp/plugins/blogs/decline/' . $blog['blog_id'] . '?' . $qstring['url'] . 'page=' . $qstring['page'], __('active', 'system'), array('class' => 'label small success'));
			}
			else
			{
				$status = html_helper::anchor('cp/plugins/blogs/approve/' . $blog['blog_id'] . '?' . $qstring['url'] . 'page=' . $qstring['page'], $blog['active'] ? __('pending', 'system') : __('inactive', 'system'), array('class' => 'label small ' . ( $blog['active'] ? 'info' : 'important' )));
			}

			$grid['content'][] = array(
				'check' => array(
					'html' => $blog['blog_id'],
				),
				'data_title' => array(
					'html' => html_helper::anchor('cp/plugins/blogs/edit/' . $blog['blog_id'], text_helper::truncate($blog['data_title'], 64)),
				),
				'user' => array(
					'html' => users_helper::anchor($blog['user']),
				),
				'post_date' => array(
					'html' => date_helper::formatDate($blog['post_date']),
				),
				'status' => array(
					'html' => $status,
				),
				'actions' => array(
					'html' => array(
						'edit' => html_helper::anchor('cp/plugins/blogs/edit/'.$blog['blog_id'], __('edit', 'system'), array('class' => 'edit')),
						'delete' => html_helper::anchor('cp/plugins/blogs/delete/'.$blog['blog_id'].'?'.$qstring['url'].'page='.$qstring['page'], __('delete', 'system'), array('data-html' => __('blog_delete?', 'blogs'), 'data-role' => 'confirm', 'class' => 'delete')),
					),
				),
			);
		}

		// Set pagination
		$config = array(
			'base_url' => config::siteURL('cp/plugins/blogs?' . $qstring['url']),
			'total_items' => $params['total'],
			'items_per_page' => $this->blogsPerPage,
			'current_page' => $qstring['page'],
			'uri_segment' => 'page',
		);
		$pagination = loader::library('pagination', $config, null);

		// Filter hooks
		hook::filter('cp/plugins/blogs/browse/grid', $grid);
		hook::filter('cp/plugins/blogs/browse/actions', $actions);

		// Assign vars
		view::assign(array('grid' => $grid, 'actions' => $actions, 'pagination' => $pagination));

		// Set title
		view::setTitle(__('blogs_manage', 'system_navigation'));

		// Set trail
		if ( $qstring['search_id'] )
		{
			view::setTrail('cp/plugins/blogs?' . $qstring['url'] . 'page=' . $qstring['page'], __('search_results', 'system'));
		}

		// Assign actions
		view::setAction('#', __('search', 'system'), array('class' => 'icon-text icon-system-search', 'onclick' => '$(\'#blogs-search\').toggle();return false;'));

		// Load view
		view::load('cp/plugins/blogs/browse');
	}

	public function edit()
	{
		// Get URI vars
		$blogID = (int)uri::segment(5);

		// Get fields
		$fields = $this->fields_model->getFields('blogs', 0, 'edit');

		// Get blog
		if ( !$blogID || !( $blog = $this->blogs_model->getBlog($blogID, $fields, array('escape' => false, 'parse' => false)) ) )
		{
			view::setError(__('no_blog', 'blogs'));
			router::redirect('cp/plugins/blogs');
		}

		// Get user
		if ( !( $user = $this->users_model->getUser($blog['user_id']) ) )
		{
			view::setError(__('no_user', 'users'));
			router::redirect('cp/plugins/blogs');
		}

		// Privacy and general options
		$privacy = $options = array();

		// Do we need to add privacy field?
		if ( config::item('blog_privacy_view', 'blogs') )
		{
			$items = $this->users_model->getPrivacyOptions(isset($user['config']['privacy_profile']) ? $user['config']['privacy_profile'] : 1);

			$privacy[] = array(
				'name' => __('privacy_blog_view', 'blogs_privacy', array(), array(), false),
				'keyword' => 'privacy',
				'type' => 'select',
				'items' => $items,
			);
		}

		// Do we need to add enable comments field?
		if ( config::item('blog_comments', 'blogs') && config::item('blog_privacy_comments', 'blogs') )
		{
			$items = $this->users_model->getPrivacyOptions(( isset($user['config']['privacy_profile']) ? $user['config']['privacy_profile'] : 1 ), false);
			$items[0] = __('privacy_comments_disable', 'comments_privacy');

			$privacy[] = array(
				'name' => __('privacy_comments_post', 'comments_privacy', array(), array(), false),
				'keyword' => 'comments',
				'type' => 'select',
				'items' => $items,
			);
		}

		// Do we need to add search field?
		if ( config::item('blog_privacy_public', 'blogs') )
		{
			$privacy[] = array(
				'name' => __('privacy_search', 'system', array(), array(), false),
				'keyword' => 'public',
				'type' => 'boolean',
			);
		}

		// Active field
		$options[] = array(
			'name' => __('status', 'system', array(), array(), false),
			'keyword' => 'active',
			'type' => 'select',
			'items' => array(
				1 => __('active', 'system'),
				9 => __('pending', 'system'),
				0 => __('inactive', 'system'),
			),
			'value' => 1,
		);

		// Assign vars
		view::assign(array('blogID' => $blogID, 'blog' => $blog, 'user' => $user, 'fields' => $fields, 'privacy' => $privacy, 'options' => $options));

		// Process form values
		if ( input::post('do_save_blog') )
		{
			$this->_saveBlog($blogID, $blog, $fields);
		}

		// Set title
		view::setTitle(__('blog_edit', 'blogs'));

		// Set trail
		view::setTrail('cp/plugins/blogs/edit/' . $blogID, __('blog_edit', 'blogs') . ' - ' . text_helper::entities($blog['data_title']));

		// Load view
		view::load('cp/plugins/blogs/edit');
	}

	protected function _saveBlog($blogID, $blog, $fields)
	{
		// Check if demo mode is enabled
		if ( input::demo() ) return false;

		// Extra rules
		$rules = array(
			'comments' => array('rules' => 'intval'),
			'privacy' => array('rules' => 'intval'),
			'public' => array('rules' => 'intval'),
			'active' => array('rules' => 'intval'),
		);

		// Validate form values
		if ( !$this->fields_model->validateValues($fields, $rules) )
		{
			return false;
		}

		// Extras
		$extra = array();
		$extra['comments'] = config::item('blog_comments', 'blogs') && config::item('blog_privacy_comments', 'blogs') ? (int)input::post('comments') : 1;
		$extra['privacy'] = config::item('blog_privacy_view', 'blogs') ? (int)input::post('privacy') : 1;
		$extra['public'] = config::item('blog_privacy_public', 'blogs') ? (int)input::post('public') : 1;
		$extra['active'] = (int)input::post('active');

		// Save blog
		if ( !( $blogID = $this->blogs_model->saveBlogData($blogID, 0, $blog, $fields, $extra) ) )
		{
			if ( !validate::getTotalErrors() )
			{
				view::setError(__('save_error', 'system'));
			}
			return false;
		}

		// Success
		view::setInfo(__('blog_saved', 'blogs'));
		router::redirect('cp/plugins/blogs/edit/' . $blogID);
	}

	public function approve()
	{
		$this->action('approve');
	}

	public function decline()
	{
		$this->action('decline');
	}

	public function delete()
	{
		$this->action('delete');
	}

	protected function action($action, $actionID = false)
	{
		// Check if demo mode is enabled
		if ( input::demo(1, 'cp/plugins/blogs') ) return false;

		// Get URI vars
		$blogID = $actionID ? $actionID : (int)uri::segment(5);

		// Get blog
		if ( !$blogID || !( $blog = $this->blogs_model->getBlog($blogID) ) )
		{
			view::setError(__('no_blog', 'blogs'));
			router::redirect('cp/plugins/blogs');
		}

		switch ( $action )
		{
			case 'approve':

				$this->blogs_model->toggleBlogStatus($blogID, $blog['user_id'], $blog, 1);
				$str = __('blog_approved', 'blogs');

				break;

			case 'decline':

				$this->blogs_model->toggleBlogStatus($blogID, $blog['user_id'], $blog, 0);
				$str = __('blog_declined', 'blogs');

				break;

			case 'delete':

				$this->blogs_model->deleteBlog($blogID, $blog['user_id'], $blog);
				$str = __('blog_deleted', 'blogs');

				break;
		}

		// Is this an action call?
		if ( $actionID ) return;

		// Process query string
		$qstring = $this->parseQuerystring();

		// Success
		view::setInfo($str);
		router::redirect('cp/plugins/blogs?' . $qstring['url'] . 'page=' . $qstring['page']);
	}

	protected function parseCounters($params)
	{
		// Get fields
		$filters = $this->fields_model->getFields('blogs', 0, 'edit', 'in_search', true);

		// Set extra fields
		$filters[] = array(
			'name' => __('search_keyword', 'system'),
			'type' => 'text',
			'keyword' => 'q',
		);
		$filters[] = array(
			'name' => __('user', 'system'),
			'type' => 'text',
			'keyword' => 'user',
		);
		$filters[] = array(
			'name' => __('status', 'system'),
			'keyword' => 'active',
			'type' => 'select',
			'items' => array(
				1 => __('active', 'system'),
				9 => __('pending', 'system'),
				0 => __('inactive', 'system'),
			),
		);

		// Assign vars
		view::assign(array('filters' => $filters, 'values' => array()));

		// Did user submit the filter form?
		if ( input::post_get('do_search') )
		{
			$values = array();

			// Check extra keyword
			$keyword = utf8::trim(input::post_get('q'));
			if ( $keyword )
			{
				$params['join_columns'][] = $this->search_model->prepareValue($keyword, 'b', array('data_title', 'data_body'));
				$values['q'] = $keyword;
			}

			// Check extra user field
			$user = utf8::trim(input::post_get('user'));
			if ( $user )
			{
				$params['join_columns'][] = $this->search_model->prepareValue($user, 'u', 'user');
				$values['user'] = $user;
			}

			// Check extra status field
			$status = input::post_get('active');
			if ( $status != '' )
			{
				$params['join_columns'][] = '`b`.`active`=' . (int)$status;
				$values['active'] = $status;
			}

			// Search blogs
			$searchID = $this->search_model->searchData('blog', $filters, $params['join_columns'], $values);

			// Do we have any search terms?
			if ( $searchID == 'no_terms' )
			{
				view::setError(__('search_no_terms', 'system'));
			}
			// Do we have any results?
			elseif ( $searchID == 'no_results' )
			{
				view::setError(__('search_no_results', 'system'));
				$params['total'] = 0;
				return $params;
			}
			// Redirect to search results
			else
			{
				router::redirect('cp/plugins/blogs?search_id=' . $searchID);
			}
		}

		// Do we have a search ID?
		if ( !input::post_get('do_search') && input::get('search_id') )
		{
			// Get search
			if ( !( $search = $this->search_model->getSearch(input::get('search_id')) ) )
			{
				view::setError(__('search_expired', 'system'));
				router::redirect('cp/plugins/blogs');
			}

			// Combine results
			$params['join_columns'] = $search['conditions']['columns'];
			$params['join_items'] = $search['conditions']['items'];
			$params['values'] = $search['values'];
			$params['total'] = $search['results'];

			// Assign vars
			view::assign(array('values' => $search['values']));
		}
		else
		{
			// Count blogs
			if ( !( $params['total'] = $this->counters_model->countData('blog', 0, 0, $params['join_columns'], $params['join_items'], $params) ) )
			{
				view::setInfo(__('no_blogs', 'blogs'));
			}
		}

		return $params;
	}

	protected function parseQuerystring($max = 0)
	{
		$qstring = array();

		// Set max page
		$maxpage = $max ? ceil($max / $this->blogsPerPage) : 0;

		// Get current page
		$qstring['page'] = (int)input::get('page', 1);
		$qstring['page'] = $qstring['page'] > 0 ? ( !$maxpage || $qstring['page'] <= $maxpage ? $qstring['page'] : $maxpage ) : 1;

		// Get search id
		$qstring['search_id'] = input::get('search_id');

		// Get order field and direction
		$qstring['orderby'] = input::post_get('o') && in_array(input::post_get('o'), array('data_title', 'post_date', 'total_views', 'total_rating', 'total_votes', 'total_likes', 'total_comments')) ? input::post_get('o') : 'post_date';
		$qstring['orderdir'] = input::post_get('d') && in_array(input::post_get('d'), array('asc', 'desc')) ? input::post_get('d') : 'desc';
		$qstring['order'] = $qstring['orderby'] ? array($qstring['orderby'] => $qstring['orderdir']) : array();

		// Create url string
		$qstring['url'] = ( $qstring['search_id'] ? 'search_id=' . $qstring['search_id'] . '&' : '' ) .
			( $qstring['orderby'] ? 'o=' . $qstring['orderby'] . '&' : '' ) .
			( $qstring['orderby'] && $qstring['orderdir'] ? 'd=' . $qstring['orderdir'] . '&' : '' );

		// Set limit
		$from = ( $qstring['page'] - 1 ) * $this->blogsPerPage;
		$qstring['limit'] = ( !$max || $from <= $max ? $from : $max ) . ', ' . $this->blogsPerPage;

		// Assign vars
		view::assign(array('qstring' => $qstring));

		return $qstring;
	}
}
