<?php

class Blogs_Controller extends Controller
{
	public function __construct()
	{
		parent::__construct();

		if ( !config::item('blogs_active', 'blogs') )
		{
			error::show404();
		}
		elseif ( !session::permission('blogs_access', 'blogs') )
		{
			view::noAccess();
		}

		loader::model('blogs/blogs');
	}

	public function index()
	{
		// Do we have global blogs list enabled?
		if ( !config::item('blogs_gallery', 'blogs') )
		{
			if ( users_helper::isLoggedin() )
			{
				$this->manage();
				return;
			}
			else
			{
				error::show404();
			}
		}
		// Does user have permission to view any of the user groups/types and browse blogs?
		elseif ( !session::permission('users_groups_browse', 'users') || !session::permission('users_types_browse', 'users') || !session::permission('blogs_browse', 'blogs') )
		{
			view::noAccess();
		}

		// Parameters
		$params = array(
			'join_columns' => array(
				'`b`.`active`=1',
				'`b`.`public`=1',
				'`u`.`verified`=1',
				'`u`.`active`=1',
				'`u`.`group_id` IN (' . implode(',', session::permission('users_groups_browse', 'users')) . ')',
				'`u`.`type_id` IN (' . implode(',', session::permission('users_types_browse', 'users')) . ')',
			),
			'join_items' => array(),
		);

		// Process filters
		$params = $this->parseCounters($params, 'index');

		// Process query string
		$qstring = $this->parseQuerystring(config::item('public_blogs_per_page', 'blogs'), $params['max']);

		// Get blogs
		$blogs = array();
		if ( $params['total'] )
		{
			$blogs = $this->blogs_model->getBlogs('in_list', $params['join_columns'], $params['join_items'], $qstring['order'], $qstring['limit']);
		}

		// Set pagination
		$config = array(
			'base_url' => config::siteURL('blogs?' . $qstring['url']),
			'total_items' => $params['total'],
			'max_items' => $params['max'],
			'items_per_page' => config::item('public_blogs_per_page', 'blogs'),
			'current_page' => $qstring['page'],
			'uri_segment' => 'page',
		);
		$pagination = loader::library('pagination', $config, null);

		// Assign vars
		view::assign(array('blogs' => $blogs, 'pagination' => $pagination));

		// Set meta tags
		$this->metatags_model->set('blogs', 'blogs_index');

		// Set title
		view::setTitle(__('blogs', 'system_navigation'), false);

		// Assign actions
		if ( users_helper::isLoggedin() )
		{
			view::setAction('blogs/edit', __('blog_new', 'blogs'), array('class' => 'icon-text icon-blogs-new'));
		}
		if ( session::permission('blogs_search', 'blogs') && ( $params['total'] || input::post_get('do_search') ) )
		{
			view::setAction('#', __('search', 'system'), array('class' => 'icon-text icon-system-search', 'onclick' => '$(\'#blogs-search\').toggle();return false;'));
		}

		// Load view
		view::load('blogs/index');
	}

	public function user()
	{
		// Get URI vars
		$slugID = urldecode(utf8::trim(uri::segment(3)));

		// Do we have a slug ID?
		if ( $slugID == '' )
		{
			error::show404();
		}

		// Is this our own account?
		if ( strcasecmp($slugID, session::item('slug_id')) == 0 )
		{
			$this->manage();
			return;
		}

		// Get user
		if ( !( $user = $this->users_model->getUser($slugID) ) || !$user['active'] || !$user['verified'] )
		{
			error::show404();
		}

		// Does user have permission to view this user group/type and browse blogs?
		if ( !in_array($user['group_id'], session::permission('users_groups_browse', 'users')) || !in_array($user['type_id'], session::permission('users_types_browse', 'users')) || !session::permission('blogs_browse', 'blogs') )
		{
			view::noAccess();
		}
		// Validate profile privacy
		elseif ( !$this->users_model->getPrivacyAccess($user['user_id'], ( isset($user['config']['privacy_profile']) ? $user['config']['privacy_profile'] : 1 )) )
		{
			view::noAccess($user['slug']);
		}

		// Parameters
		$params = array(
			'join_columns' => array(
				'`b`.`active`=1',
				'`b`.`user_id`=' . $user['user_id'],
			),
			'join_items' => array(),
			'privacy' => $user['user_id'],
			'select_users' => false,
		);

		// Process filters
		$params = $this->parseCounters($params, 'user');

		// Process query string
		$qstring = $this->parseQuerystring(config::item('user_blogs_per_page', 'blogs'), $params['max']);

		// Get blogs
		$blogs = array();
		if ( $params['total'] )
		{
			$blogs = $this->blogs_model->getBlogs('in_list', $params['join_columns'], $params['join_items'], $qstring['order'], $qstring['limit'], $params);
		}

		// Set pagination
		$config = array(
			'base_url' => config::siteURL('blogs/user/' . $user['slug_id'] . '?' . $qstring['url']),
			'total_items' => $params['total'],
			'max_items' => $params['max'],
			'items_per_page' => config::item('user_blogs_per_page', 'blogs'),
			'current_page' => $qstring['page'],
			'uri_segment' => 'page',
		);
		$pagination = loader::library('pagination', $config, null);

		// Assign vars
		view::assign(array('slugID' => $slugID, 'user' => $user, 'blogs' => $blogs, 'pagination' => $pagination));

		// Set meta tags
		$this->metatags_model->set('blogs', 'blogs_user', array('user' => $user));

		// Set title
		view::setTitle(__('blogs', 'system_navigation'), false);

		// Set trail
		view::setTrail($user['slug'], $user['name']);
		view::setTrail('blogs/user/' . $user['slug_id'], __('blogs', 'system_navigation'));

		// Assign actions
		if ( session::permission('blogs_search', 'blogs') && ( $params['total'] || input::post_get('do_search') ) )
		{
			view::setAction('#', __('search', 'system'), array('class' => 'icon-text icon-system-search', 'onclick' => '$(\'#blogs-search\').toggle();return false;'));
		}

		// Load view
		view::load('blogs/user');
	}

	public function manage()
	{
		// Is user loggedin ?
		if ( !users_helper::isLoggedin() )
		{
			router::redirect('users/login');
		}
		// Does user have permission to post blogs?
		elseif ( !session::permission('blogs_post', 'blogs') )
		{
			view::noAccess(session::item('slug'));
		}

		// Assign user from session to variable
		$user = session::section('session');

		// Parameters
		$params = array(
			'select_users' => false,
			'join_columns' => array(
				'`b`.`user_id`=' . session::item('user_id')
			),
			'join_items' => array(),
			'total' => ( $user['total_blogs'] + $user['total_blogs_i'] ),
		);

		// Process filters
		$params = $this->parseCounters($params, 'manage');

		// Process query string
		$qstring = $this->parseQuerystring(config::item('user_blogs_per_page', 'blogs'), $params['max']);

		// Get blogs
		$blogs = array();
		if ( $params['total'] )
		{
			$blogs = $this->blogs_model->getBlogs('in_list', $params['join_columns'], $params['join_items'], $qstring['order'], $qstring['limit'], $params);
		}

		// Set pagination
		$config = array(
			'base_url' => config::siteURL('blogs/manage?' . $qstring['url']),
			'total_items' => $params['total'],
			'max_items' => $params['max'],
			'items_per_page' => config::item('user_blogs_per_page', 'blogs'),
			'current_page' => $qstring['page'],
			'uri_segment' => 'page',
		);
		$pagination = loader::library('pagination', $config, null);

		// Assign vars
		view::assign(array('user' => $user, 'blogs' => $blogs, 'pagination' => $pagination));

		// Set title
		view::setTitle(__('my_blogs', 'system_navigation'));

		// Set trail
		view::setTrail(session::item('slug'), __('my_profile', 'system_navigation'));
		view::setTrail('blogs/manage',  __('blogs', 'system_navigation'));
		if ( $qstring['search_id'] )
		{
			view::setTrail('blogs/manage?' . $qstring['url'] . 'page=' . $qstring['page'], __('search_results', 'system'));
		}

		// Assign actions
		view::setAction('blogs/edit', __('blog_new', 'blogs'), array('class' => 'icon-text icon-blogs-new'));
		if ( session::permission('blogs_search', 'blogs') && ( $params['total'] || input::post_get('do_search') ) )
		{
			view::setAction('#', __('search', 'system'), array('class' => 'icon-text icon-system-search', 'onclick' => '$(\'#blogs-search\').toggle();return false;'));
		}

		// Load view
		view::load('blogs/manage');
	}

	public function edit()
	{
		// Is user logged in?
		if ( !users_helper::isLoggedin() )
		{
			router::redirect('users/login');
		}
		// Does user have permission to post blogs?
		elseif ( !session::permission('blogs_post', 'blogs') )
		{
			view::noAccess(session::item('slug'));
		}

		// Get URI vars
		$blogID = (int)uri::segment(3);

		// Did user reach the max blogs limit?
		if ( !$blogID && session::permission('blogs_limit', 'blogs') && session::permission('blogs_limit', 'blogs') <= ( session::item('total_blogs') + session::item('total_blogs_i') ) )
		{
			view::setError(__('blog_limit_reached', 'blogs', array('%limit%' => session::permission('blogs_limit', 'blogs'))));
			router::redirect('blogs/manage');
		}

		// Get blog fields
		$fields = $this->fields_model->getFields('blogs', 0, 'edit', 'in_account');

		// Get blog
		$blog = array();
		if ( $blogID && ( !( $blog = $this->blogs_model->getBlog($blogID, $fields, array('escape' => false, 'parse' => false)) ) || $blog['user_id'] != session::item('user_id') ) )
		{
			view::setError(__('no_blog', 'blogs'));
			router::redirect('blogs/manage');
		}

		// Privacy options
		$privacy = array();

		// Do we need to add privacy field?
		if ( config::item('blog_privacy_view', 'blogs') )
		{
			$items = $this->users_model->getPrivacyOptions(session::item('privacy_profile', 'config'));

			$privacy[] = array(
				'name' => __('privacy_blog_view', 'blogs_privacy', array(), array(), false),
				'keyword' => 'privacy',
				'type' => 'select',
				'items' => $items,
				'privacy' => config::item('privacy_default', 'users'),
			);
		}

		// Do we need to add enable comments field?
		if ( config::item('blog_comments', 'blogs') && config::item('blog_privacy_comments', 'blogs') )
		{
			$items = $this->users_model->getPrivacyOptions(session::item('privacy_profile', 'config'), false);
			$items[0] = __('privacy_comments_disable', 'comments_privacy');

			$privacy[] = array(
				'name' => __('privacy_comments_post', 'comments_privacy', array(), array(), false),
				'keyword' => 'comments',
				'type' => 'select',
				'items' => $items,
				'comments' => config::item('privacy_default', 'users'),
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

		// Assign vars
		view::assign(array('blogID' => $blogID, 'blog' => $blog, 'fields' => $fields, 'privacy' => $privacy));

		// Process form values
		if ( input::post('do_save_blog') )
		{
			$this->_saveBlog($blogID, $blog, $fields);
		}

		// Set title
		view::setTitle(__($blogID ? 'blog_edit' : 'blog_new', 'blogs'));

		// Set trail
		view::setTrail(session::item('slug'), __('my_profile', 'system_navigation'));
		view::setTrail('blogs/manage', __('blogs', 'system_navigation'));
		if ( $blogID )
		{
			view::setTrail('blogs/view/'.$blog['blog_id'].'/'.text_helper::slug($blog['data_title'], 100), __('blog_view', 'blogs'), array('side' => true));
		}

		// Load view
		view::load('blogs/edit');
	}

	protected function _saveBlog($blogID, $blog, $fields)
	{
		// Extra rules
		$rules = array(
			'comments' => array('rules' => 'intval'),
			'privacy' => array('rules' => 'intval'),
			'public' => array('rules' => 'intval'),
		);

		// Validate form values
		if ( !$this->fields_model->validateValues($fields, $rules) )
		{
			return false;
		}

		// Extra fields
		$extra = array();
		$extra['comments'] = config::item('blog_comments', 'blogs') && config::item('blog_privacy_comments', 'blogs') ? (int)input::post('comments') : 1;
		$extra['privacy'] = config::item('blog_privacy_view', 'blogs') ? (int)input::post('privacy') : 1;
		$extra['public'] = config::item('blog_privacy_public', 'blogs') ? (int)input::post('public') : 1;

		// Save blog
		if ( !( $blogID = $this->blogs_model->saveBlogData($blogID, session::item('user_id'), $blog, $fields, $extra) ) )
		{
			if ( !validate::getTotalErrors() )
			{
				view::setError(__('save_error', 'system'));
			}
			return false;
		}

		// Success
		view::setInfo(__('blog_saved', 'blogs'));
		router::redirect('blogs/edit/' . $blogID);
	}

	public function view()
	{
		// Get URI vars
		$blogID = (int)uri::segment(3);

		// Get blog
		if ( !$blogID || !( $blog = $this->blogs_model->getBlog($blogID, 'in_view') ) || ( !$blog['active'] && $blog['user_id'] != session::item('user_id') ) )
		{
			error::show404();
		}

		// Is this our own blog?
		if ( $blog['user_id'] == session::item('user_id') )
		{
			// Assign user from session to variable
			$user = session::section('session');
		}
		else
		{
			// Get user
			if ( !( $user = $this->users_model->getUser($blog['user_id']) ) || !$user['active'] || !$user['verified'] )
			{
				error::show404();
			}

			// Does user have permission to view this user group/type and view blogs?
			if ( !in_array($user['group_id'], session::permission('users_groups_browse', 'users')) || !in_array($user['type_id'], session::permission('users_types_browse', 'users')) || !session::permission('blogs_view', 'blogs') )
			{
				view::noAccess();
			}
			// Validate profile and blog privacy
			elseif ( !$this->users_model->getPrivacyAccess($user['user_id'], ( isset($user['config']['privacy_profile']) ? $user['config']['privacy_profile'] : 1 )) || !$this->users_model->getPrivacyAccess($user['user_id'], $blog['privacy']) )
			{
				view::noAccess($user['slug']);
			}
		}

		// Do we have views enabled?
		if ( config::item('blog_views', 'blogs') )
		{
			// Update views counter
			$this->blogs_model->updateViews($blogID);
		}

		// Load ratings
		if ( config::item('blog_rating', 'blogs') == 'stars' )
		{
			// Load votes model
			loader::model('comments/votes');

			// Get votes
			$blog['user_vote'] = $this->votes_model->getVote('blog', $blogID);
		}
		elseif ( config::item('blog_rating', 'blogs') == 'likes' )
		{
			// Load likes model
			loader::model('comments/likes');

			// Get likes
			$blog['user_vote'] = $this->likes_model->getLike('blog', $blogID);
		}

		// Assign vars
		view::assign(array('blogID' => $blogID, 'blog' => $blog, 'user' => $user));

		// Set meta tags
		$this->metatags_model->set('blogs', 'blogs_view', array('user' => $user, 'blog' => $blog));

		// Set title
		view::setTitle($blog['data_title'], false);

		// Set trail
		if ( $user['user_id'] == session::item('user_id') )
		{
			view::setTrail(session::item('slug'), __('my_profile', 'system_navigation'));
			view::setTrail('blogs/manage',  __('blogs', 'system_navigation'));
		}
		else
		{
			view::setTrail($user['slug'], $user['name']);
			view::setTrail('blogs/user/' . $user['slug_id'], __('blogs', 'system_navigation'));
		}

		// Load view
		view::load('blogs/view');
	}

	public function delete()
	{
		// Is user logged in?
		if ( !users_helper::isLoggedin() )
		{
			router::redirect('users/login');
		}
		// Does user have permission to delete blogs?
		elseif ( !session::permission('blogs_post', 'blogs') )
		{
			view::noAccess(session::item('slug'));
		}

		// Get URI vars
		$blogID = (int)uri::segment(3);

		// Get blog
		if ( !$blogID || !( $blog = $this->blogs_model->getBlog($blogID) ) || $blog['user_id'] != session::item('user_id') )
		{
			view::setError(__('no_blog', 'blogs'));
			router::redirect('blogs/manage');
		}

		// Delete blog
		$this->blogs_model->deleteBlog($blogID, session::item('user_id'), $blog);

		// Process query string
		$qstring = $this->parseQuerystring(config::item('user_blogs_per_page', 'blogs'));

		// Success
		view::setInfo(__('blog_deleted', 'blogs'));
		router::redirect('blogs/manage?' . $qstring['url'] . 'page=' . $qstring['page']);
	}

	protected function parseCounters($params = array(), $type = 'index')
	{
		// Assign vars
		view::assign(array('filters' => array(), 'values' => array()));

		// Do we have permission to search?
		if ( session::permission('blogs_search', 'blogs') )
		{
			// Get fields
			$filters = $this->fields_model->getFields('blogs', 0, 'edit', 'in_search', true);

			// Set extra fields
			$filters[] = array(
				'name' => __('search_keyword', 'system'),
				'type' => 'text',
				'keyword' => 'q',
			);

			// Assign vars
			view::assign(array('filters' => $filters));

			// Did user submit the filter form?
			if ( input::post_get('do_search') )
			{
				$values = array();
				$params['total'] = $params['max'] = 0;

				// Check extra keyword
				$keyword = utf8::trim(input::post_get('q'));
				if ( $keyword )
				{
					$params['join_columns'][] = $this->search_model->prepareValue($keyword, 'b', array('data_title', 'data_body'));
					$values['q'] = $keyword;
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
					return $params;
				}
				// Redirect to search results
				else
				{
					switch ( $type )
					{
						case 'user':
							router::redirect('blogs/user/' . uri::segment(3) . '?search_id=' . $searchID);
							break;

						case 'manage':
							router::redirect('blogs/manage?search_id=' . $searchID);
							break;

						default:
							router::redirect('blogs?search_id=' . $searchID);
							break;
					}
				}
			}

			// Do we have a search ID?
			if ( !input::post_get('do_search') && input::get('search_id') )
			{
				// Get search
				if ( !( $search = $this->search_model->getSearch(input::get('search_id')) ) )
				{
					view::setError(__('search_expired', 'system'));
					switch ( $type )
					{
						case 'user':
							router::redirect('blogs/user/' . uri::segment(3));
							break;

						case 'manage':
							router::redirect('blogs/manage');
							break;

						default:
							router::redirect('blogs');
							break;
					}
				}

				// Set results
				$params['join_columns'] = $search['conditions']['columns'];
				$params['join_items'] = $search['conditions']['items'];
				$params['values'] = $search['values'];
				$params['total'] = $search['results'];
				$params['max'] = config::item('max_search_results', 'system') && config::item('max_search_results', 'system') < $params['total'] ? config::item('max_search_results', 'system') : $params['total'];

				// Assign vars
				view::assign(array('values' => $search['values']));
			}
		}

		if ( !input::get('search_id') )
		{
			// Count blogs
			if ( $type == 'manage' && !$params['total'] || $type != 'manage' && !( $params['total'] = $this->counters_model->countData('blog', 0, 0, $params['join_columns'], $params['join_items'], $params) ) )
			{
				if ( $type == 'manage' )
				{
					view::setInfo(__('no_blogs_self', 'blogs'));
				}
				else
				{
					view::setInfo(__('no_blogs', 'blogs'));
				}
			}
			$params['max'] = $params['total'];
		}

		return $params;
	}

	protected function parseQuerystring($pagination = 15, $max = 0)
	{
		$qstring = array();

		// Set max page
		$maxpage = $max ? ceil($max / $pagination) : 0;

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
		$from = ( $qstring['page'] - 1 ) * $pagination;
		$qstring['limit'] = ( !$max || $from <= $max ? $from : $max ) . ', ' . ( !$max || $max >= $pagination ? $pagination : $max );

		// Assign vars
		view::assign(array('qstring' => $qstring));

		return $qstring;
	}
}
