<?php

class Classifieds_Controller extends Controller
{
	public function __construct()
	{
		parent::__construct();

		if ( !config::item('classifieds_active', 'classifieds') )
		{
			error::show404();
		}
		elseif ( !session::permission('ads_access', 'classifieds') )
		{
			view::noAccess();
		}

		loader::model('classifieds/classifieds');
	}

	public function index()
	{
		// Is ads gallery enabled?
		if ( !config::item('ads_gallery', 'classifieds') )
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
		// Does user have permission to view any of the user groups/types and browse ads?
		elseif ( !session::permission('users_groups_browse', 'users') || !session::permission('users_types_browse', 'users') || !session::permission('ads_browse', 'classifieds') )
		{
			view::noAccess();
		}

		// Parameters
		$params = array(
			'join_columns' => array(
				'`a`.`active`=1',
				'`a`.`post_date`>' . ( date_helper::now() - config::item('ad_expiration', 'classifieds')*60*60*24 ),
				'`u`.`verified`=1',
				'`u`.`active`=1',
			),
			'join_items' => array(),
		);

		// Process filters
		$params = $this->parseCounters($params);

		// Process query string
		$qstring = $this->parseQuerystring(config::item('public_ads_per_page', 'classifieds'), $params['max']);

		// Get ads
		$ads = array();
		if ( $params['total'] )
		{
			$ads = $this->classifieds_model->getAds('in_list', $params['join_columns'], $params['join_items'], $qstring['order'], $qstring['limit']);
		}

		// Set pagination
		$config = array(
			'base_url' => config::siteURL('classifieds?' . $qstring['url']),
			'total_items' => $params['total'],
			'max_items' => $params['max'],
			'items_per_page' => config::item('public_ads_per_page', 'classifieds'),
			'current_page' => $qstring['page'],
			'uri_segment' => 'page',
		);
		$pagination = loader::library('pagination', $config, null);

		// Get fields
		$fields = $this->fields_model->getFields('classifieds', 0, 'view', 'in_view');

		// Assign vars
		view::assign(array('ads' => $ads, 'pagination' => $pagination));

		// Set meta tags
		$this->metatags_model->set('classifieds', 'classifieds_index');

		// Set title
		view::setTitle(__('classifieds', 'system_navigation'), false);

		// Assign actions
		if ( users_helper::isLoggedin() )
		{
			view::setAction('classifieds/edit', __('ad_new', 'classifieds'), array('class' => 'icon-text icon-classifieds-new'));
		}
		if ( session::permission('ads_search', 'classifieds') && ( $params['total'] || input::post_get('do_search') ) )
		{
			view::setAction('#', __('search', 'system'), array('class' => 'icon-text icon-system-search', 'onclick' => '$(\'#classifieds-search\').toggle();return false;'));
		}

		// Load view
		view::load('classifieds/index');
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

		// Does user have permission to view this user group/type and browse ads?
		if ( !in_array($user['group_id'], session::permission('users_groups_browse', 'users')) || !in_array($user['type_id'], session::permission('users_types_browse', 'users')) || !session::permission('ads_browse', 'classifieds') )
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
				'`a`.`user_id`=' . $user['user_id'],
				'`a`.`post_date`>' . ( date_helper::now() - config::item('ad_expiration', 'classifieds')*60*60*24 ),
			),
			'join_items' => array(),
			'privacy' => $user['user_id'],
			'select_users' => false,
		);

		// Process filters
		$params = $this->parseCounters($params, 'user');

		// Process query string
		$qstring = $this->parseQuerystring(config::item('user_ads_per_page', 'classifieds'), $params['max']);

		// Get ads
		$ads = array();
		if ( $params['total'] )
		{
			$ads = $this->classifieds_model->getAds('in_list', $params['join_columns'], $params['join_items'], $qstring['order'], $qstring['limit'], $params);
		}

		// Set pagination
		$config = array(
			'base_url' => config::siteURL('classifieds/user/' . $user['slug_id'] . '?' . $qstring['url']),
			'total_items' => $params['total'],
			'max_items' => $params['max'],
			'items_per_page' => config::item('user_ads_per_page', 'classifieds'),
			'current_page' => $qstring['page'],
			'uri_segment' => 'page',
		);
		$pagination = loader::library('pagination', $config, null);

		// Get fields
		$fields = $this->fields_model->getFields('classifieds', 0, 'view', 'in_view');

		// Assign vars
		view::assign(array('slugID' => $slugID, 'user' => $user, 'ads' => $ads, 'pagination' => $pagination));

		// Set meta tags
		$this->metatags_model->set('classifieds', 'classifieds_user', array('user' => $user));

		// Set title
		view::setTitle(__('classifieds', 'system_navigation'), false);

		// Set trail
		view::setTrail($user['slug'], $user['name']);
		view::setTrail('classifieds/user/' . $user['slug_id'], __('classifieds', 'system_navigation'));

		// Assign actions
		if ( session::permission('ads_search', 'classifieds') && ( $params['total'] || input::post_get('do_search') ) )
		{
			view::setAction('#', __('search', 'system'), array('class' => 'icon-text icon-system-search', 'onclick' => '$(\'#classifieds-search\').toggle();return false;'));
		}

		// Load view
		view::load('classifieds/user');
	}

	public function manage()
	{
		// Is user loggedin ?
		if ( !users_helper::isLoggedin() )
		{
			router::redirect('users/login');
		}
		// Does user have permission to create ads?
		elseif ( !session::permission('ads_post', 'classifieds') )
		{
			view::noAccess(session::item('slug'));
		}

		// Assign user from session to variable
		$user = session::section('session');

		// Parameters
		$params = array(
			'select_users' => false,
			'join_columns' => array(
				'`a`.`user_id`=' . session::item('user_id'),
			),
			'join_items' => array(),
			'total' => ( $user['total_classifieds'] + $user['total_classifieds_i'] ),
		);

		// Process filters
		$params = $this->parseCounters($params, 'manage');

		// Process query string
		$qstring = $this->parseQuerystring(config::item('user_ads_per_page', 'classifieds'), $params['max']);

		// Get ads
		$ads = array();
		if ( $params['total'] )
		{
			$ads = $this->classifieds_model->getAds('in_list', $params['join_columns'], $params['join_items'], $qstring['order'], $qstring['limit'], $params);
		}

		// Set pagination
		$config = array(
			'base_url' => config::siteURL('classifieds/manage?' . $qstring['url']),
			'total_items' => $params['total'],
			'max_items' => $params['max'],
			'items_per_page' => config::item('user_ads_per_page', 'classifieds'),
			'current_page' => $qstring['page'],
			'uri_segment' => 'page',
		);
		$pagination = loader::library('pagination', $config, null);

		// Get fields
		$fields = $this->fields_model->getFields('classifieds', 0, 'view', 'in_view');

		// Assign vars
		view::assign(array('user' => $user, 'ads' => $ads, 'pagination' => $pagination));

		// Set title
		view::setTitle(__('my_classifieds', 'system_navigation'));

		// Set trail
		view::setTrail(session::item('slug'), __('my_profile', 'system_navigation'));
		view::setTrail('classifieds/manage',  __('classifieds', 'system_navigation'));

		// Assign actions
		view::setAction('classifieds/edit', __('ad_new', 'classifieds'), array('class' => 'icon-text icon-classifieds-new'));
		if ( session::permission('ads_search', 'classifieds') && ( $params['total'] || input::post_get('do_search') ) )
		{
			view::setAction('#', __('search', 'system'), array('class' => 'icon-text icon-system-search', 'onclick' => '$(\'#classifieds-search\').toggle();return false;'));
		}

		// Load view
		view::load('classifieds/manage');
	}

	public function edit()
	{
		// Is user logged in?
		if ( !users_helper::isLoggedin() )
		{
			router::redirect('users/login');
		}
		// Does user have permission to create ads?
		elseif ( !session::permission('ads_post', 'classifieds') )
		{
			view::noAccess(session::item('slug'));
		}

		// Get URI vars
		$adID = (int)uri::segment(3);

		// Did user reach the max ads limit?
		if ( !$adID && session::permission('ads_limit', 'classifieds') && session::permission('ads_limit', 'classifieds') <= ( session::item('total_classifieds') + session::item('total_classifieds_i') ) )
		{
			view::setError(__('ad_limit_reached', 'classifieds', array('%limit%' => session::permission('ads_limit', 'classifieds'))));
			router::redirect('classifieds/manage');
		}
		// Do we require credits to post ads?
		elseif ( config::item('credits_active', 'billing') && session::permission('ads_credits', 'classifieds') && session::permission('ads_credits', 'classifieds') > session::item('total_credits') )
		{
			view::setError(__('no_credits', 'system', array(), array('%' => html_helper::anchor('billing/credits', '\1'))));
			router::redirect('classifieds/manage');
		}

		// Get fields
		$fields = $this->fields_model->getFields('classifieds', 0, 'edit', 'in_account');

		// Get ad
		$ad = array();
		if ( $adID && ( !( $ad = $this->classifieds_model->getAd($adID, $fields, array('escape' => false, 'parse' => false)) ) || $ad['user_id'] != session::item('user_id') ) )
		{
			view::setError(__('no_ad', 'classifieds'));
			router::redirect('classifieds/manage');
		}

		// Privacy options
		$privacy = array();

		// Do we need to add enable comments field?
		if ( config::item('ad_comments', 'classifieds') && config::item('ad_privacy_comments', 'classifieds') )
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

		// Assign vars
		view::assign(array('adID' => $adID, 'ad' => $ad, 'fields' => $fields, 'privacy' => $privacy));

		// Process form values
		if ( input::post('do_save_ad') )
		{
			$this->_saveAd($adID, $ad, $fields);
		}

		// Set title
		view::setTitle(__($adID ? 'ad_edit' : 'ad_new', 'classifieds'));

		// Set trail
		view::setTrail(session::item('slug'), __('my_profile', 'system_navigation'));
		view::setTrail('classifieds/manage',  __('classifieds', 'system_navigation'));
		if ( $adID )
		{
			view::setTrail('classifieds/view/'.$ad['ad_id'].'/'.text_helper::slug($ad['data_title'], 100), __('ad_view', 'classifieds'), array('side' => true));
		}

		// Assign actions
		if ( $adID )
		{
			view::setAction('classifieds/pictures/upload/' . $adID, __('pictures_new', 'classifieds'), array('class' => 'icon-text icon-classifieds-pictures-new', 'data-role' => 'modal', 'data-title' => __('pictures_new', 'classifieds')));
			if ( $ad['total_pictures'] + $ad['total_pictures_i'] > 0 )
			{
				view::setAction('classifieds/pictures/index/' . $adID, __('pictures', 'classifieds'), array('class' => 'icon-text icon-classifieds-pictures'));
			}
		}

		// Load view
		view::load('classifieds/edit');
	}

	protected function _saveAd($adID, $ad, $fields)
	{
		// Extra rules
		$rules = array(
			'comments' => array('rules' => 'intval'),
		);

		// Validate form values
		if ( !$this->fields_model->validateValues($fields, $rules) )
		{
			return false;
		}

		// Extra fields
		$extra = array();
		$extra['comments'] = config::item('ad_comments', 'classifieds') && config::item('ad_privacy_comments', 'classifieds') ? (int)input::post('comments') : 1;

		// Save ad
		if ( !( $adID = $this->classifieds_model->saveAdData($adID, session::item('user_id'), $ad, $fields, $extra) ) )
		{
			if ( !validate::getTotalErrors() )
			{
				view::setError(__('save_error', 'system'));
			}
			return false;
		}

		// Success
		view::setInfo(__('ad_saved', 'classifieds'));
		router::redirect('classifieds/edit/' . $adID);
	}

	public function view()
	{
		// Get URI vars
		$adID = (int)uri::segment(3);

		// Get ad
		if ( !$adID || !( $ad = $this->classifieds_model->getAd($adID, 'in_view') ) || ( !$ad['active'] && $ad['user_id'] != session::item('user_id') ) )
		{
			error::show404();
		}

		// Is this our own ad?
		if ( $ad['user_id'] == session::item('user_id') )
		{
			// Assign user from session to variable
			$user = session::section('session');
		}
		else
		{
			// Get user
			if ( !( $user = $this->users_model->getUser($ad['user_id']) ) || !$user['active'] || !$user['verified'] )
			{
				error::show404();
			}

			// Does user have permission to view this user group/type and view ads?
			if ( !in_array($user['group_id'], session::permission('users_groups_browse', 'users')) || !in_array($user['type_id'], session::permission('users_types_browse', 'users')) || !session::permission('ads_view', 'classifieds') )
			{
				view::noAccess();
			}
		}

		// Do we have views enabled?
		if ( config::item('ad_views', 'classifieds') )
		{
			// Update views counter
			$this->classifieds_model->updateViews($adID);
		}

		// Get fields
		$fields = $this->fields_model->getFields('classifieds', 0, 'view', 'in_view');

		// Load ratings
		if ( config::item('ad_rating', 'classifieds') == 'stars' )
		{
			// Load votes model
			loader::model('comments/votes');

			// Get votes
			$ad['user_vote'] = $this->votes_model->getVote('classified_ad', $adID);
		}
		elseif ( config::item('ad_rating', 'classifieds') == 'likes' )
		{
			// Load likes model
			loader::model('comments/likes');

			// Get likes
			$ad['user_vote'] = $this->likes_model->getLike('classified_ad', $adID);
		}

		// Assign vars
		view::assign(array('adID' => $adID, 'ad' => $ad, 'user' => $user));

		// Set meta tags
		$this->metatags_model->set('classifieds', 'classifieds_view', array('user' => $user, 'ad' => $ad));

		// Set title
		view::setTitle($ad['data_title'] . ( $ad['post_date'] < date_helper::now() - config::item('ad_expiration', 'classifieds')*60*60*24 ? ' - ' . __('ad_expired', 'classifieds') : '' ), false);

		// Set trail
		if ( $user['user_id'] == session::item('user_id') )
		{
			view::setTrail(session::item('slug'), __('my_profile', 'system_navigation'));
			view::setTrail('classifieds/manage',  __('classifieds', 'system_navigation'));
		}
		else
		{
			view::setTrail($user['slug'], $user['name']);
			view::setTrail('classifieds/user/' . $user['slug_id'], __('classifieds', 'system_navigation'));
		}

		// Assign actions
		if ( $user['user_id'] == session::item('user_id') )
		{
			view::setAction('classifieds/pictures/upload/' . $adID, __('pictures_new', 'classifieds'), array('class' => 'icon-text icon-classifieds-pictures-new', 'data-role' => 'modal', 'data-title' => __('pictures_new', 'classifieds')));
			if ( $ad['total_pictures'] + $ad['total_pictures_i'] > 0 )
			{
				view::setAction('classifieds/pictures/index/' . $adID, __('pictures', 'classifieds'), array('class' => 'icon-text icon-classifieds-pictures'));
			}
		}

		// Load view
		view::load('classifieds/view');
	}

	public function delete()
	{
		// Is user logged in?
		if ( !users_helper::isLoggedin() )
		{
			router::redirect('users/login');
		}
		// Does user have permission to delete ads?
		elseif ( !session::permission('ads_post', 'classifieds') )
		{
			view::noAccess(session::item('slug'));
		}

		// Get URI vars
		$adID = (int)uri::segment(3);

		// Get ad
		if ( !$adID || !( $ad = $this->classifieds_model->getAd($adID) ) || $ad['user_id'] != session::item('user_id') )
		{
			view::setError(__('no_ad', 'classifieds'));
			router::redirect('classifieds/manage');
		}

		// Delete ad
		$this->classifieds_model->deleteAd($adID, session::item('user_id'), $ad);

		// Process query string
		$qstring = $this->parseQuerystring(config::item('user_ads_per_page', 'classifieds'));

		// Success
		view::setInfo(__('ad_deleted', 'classifieds'));
		router::redirect('classifieds/manage?' . $qstring['url'] . 'page=' . $qstring['page']);
	}

	protected function parseCounters($params = array(), $type = 'index')
	{
		// Assign vars
		view::assign(array('filters' => array(), 'values' => array()));

		// Do we have permission to search?
		if ( session::permission('ads_search', 'classifieds') )
		{
			// Get fields
			$filters = $this->fields_model->getFields('classifieds', 0, 'edit', 'in_search', true);

			// Set extra fields
			$filters[] = array(
				'name' => __('search_keyword', 'system'),
				'type' => 'text',
				'keyword' => 'q',
			);

			// Assign vars
			view::assign(array('filters' => $filters));

			// Did user submit the filter form?
			if ( input::post_get('do_search') && session::permission('ads_search', 'classifieds') )
			{
				$values = array();
				$params['total'] = $params['max'] = 0;

				// Check extra keyword
				$keyword = utf8::trim(input::post_get('q'));
				if ( $keyword )
				{
					$params['join_columns'][] = $this->search_model->prepareValue($keyword, 'a', array('data_title', 'data_body'));
					$values['q'] = $keyword;
				}

				// Search ads
				$searchID = $this->search_model->searchData('classified_ad', $filters, $params['join_columns'], $values);

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
							router::redirect('classifieds/user/' . uri::segment(3) . '?search_id=' . $searchID);
							break;

						case 'manage':
							router::redirect('classifieds/manage?search_id=' . $searchID);
							break;

						default:
							router::redirect('classifieds?search_id=' . $searchID);
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
							router::redirect('classifieds/user/' . uri::segment(3));
							break;

						case 'manage':
							router::redirect('classifieds/manage');
							break;

						default:
							router::redirect('classifieds');
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
			// Count ads
			if ( $type == 'manage' && !$params['total'] || $type != 'manage' && !( $params['total'] = $this->counters_model->countData('classified_ad', 0, 0, $params['join_columns'], $params['join_items'], $params) ) )
			{
				if ( $type == 'manage' )
				{
					view::setInfo(__('no_ads_self', 'classifieds'));
				}
				else
				{
					view::setInfo(__('no_ads', 'classifieds'));
				}
			}
			$params['max'] = $params['total'];
			if ( $type == 'index' && $params['total'] > config::item('max_gallery_ads', 'classifieds') )
			{
				$params['max'] = $params['total'] = config::item('max_gallery_ads', 'classifieds');
			}
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
		$qstring['orderby'] = input::post_get('o') && in_array(input::post_get('o'), array('post_date', 'total_views', 'total_rating', 'total_votes', 'total_likes', 'total_pictures')) ? input::post_get('o') : 'post_date';
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
