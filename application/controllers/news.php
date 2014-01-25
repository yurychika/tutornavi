<?php

class News_Controller extends Controller
{
	public function __construct()
	{
		parent::__construct();

		if ( !config::item('news_active', 'news') )
		{
			error::show404();
		}
		elseif ( !session::permission('news_access', 'news') )
		{
			view::noAccess();
		}
		elseif ( config::item('news_blog', 'news') && uri::segment(1) != 'blog' )
		{
			router::redirect('blog/' . utf8::substr(uri::getURI(), 5));
		}

		loader::model('news/news');
	}

	public function index()
	{
		// Parameters
		$params = array(
			'join_columns' => array(
				'`n`.`active`=1',
			),
			'join_items' => array(),
		);

		// Process filters
		$params = $this->parseCounters($params);

		// Process query string
		$qstring = $this->parseQuerystring(config::item('news_per_page', 'news'), $params['max']);

		// Get news
		$news = array();
		if ( $params['total'] )
		{
			$news = $this->news_model->getEntries('in_list', $params['join_columns'], $params['join_items'], $qstring['order'], $qstring['limit']);
		}

		// Set pagination
		$config = array(
			'base_url' => config::siteURL('news?' . $qstring['url']),
			'total_items' => $params['total'],
			'max_items' => $params['max'],
			'items_per_page' => config::item('news_per_page', 'news'),
			'current_page' => $qstring['page'],
			'uri_segment' => 'page',
		);
		$pagination = loader::library('pagination', $config, null);

		// Assign vars
		view::assign(array('news' => $news, 'pagination' => $pagination));

		// Set meta tags
		$this->metatags_model->set('news', 'news_index');

		// Set title
		view::setTitle(__(config::item('news_blog', 'news') ? 'blog' : 'news', 'system_navigation'), false);

		// Assign actions
		if ( session::permission('news_search', 'news') && ( $params['total'] || input::post_get('do_search') ) )
		{
			view::setAction('#', __('search', 'system'), array('class' => 'icon-text icon-system-search', 'onclick' => '$(\'#news-search\').toggle();return false;'));
		}

		// Load view
		view::load('news/index');
	}

	public function view()
	{
		// Get URI vars
		$newsID = (int)uri::segment(3);

		// Get news entry
		if ( !$newsID || !( $news = $this->news_model->getEntry($newsID, 'in_view') ) || !$news['active'] )
		{
			error::show404();
		}

		// Do we have views enabled?
		if ( config::item('news_views', 'news') )
		{
			// Update views counter
			$this->news_model->updateViews($newsID);
		}

		// Load ratings
		if ( config::item('news_rating', 'news') == 'stars' )
		{
			// Load votes model
			loader::model('comments/votes');

			// Get votes
			$news['user_vote'] = $this->votes_model->getVote('news', $newsID);
		}
		elseif ( config::item('news_rating', 'news') == 'likes' )
		{
			// Load likes model
			loader::model('comments/likes');

			// Get likes
			$news['user_vote'] = $this->likes_model->getLike('news', $newsID);
		}

		// Assign vars
		view::assign(array('newsID' => $newsID, 'news' => $news));

		// Set title
		view::setTitle($news['data_title']);

		// Set meta tags
		view::setMetaDescription($news['data_meta_description']);
		view::setMetaKeywords($news['data_meta_keywords']);

		// Load view
		view::load('news/view');
	}

	protected function parseCounters($params = array())
	{
		// Assign vars
		view::assign(array('filters' => array(), 'values' => array()));

		// Do we have permission to search?
		if ( session::permission('news_search', 'news') )
		{
			// Get fields
			$filters = $this->fields_model->getFields('news', 0, 'edit', 'in_search', true);

			// Set extra fields
			$filters[] = array(
				'name' => __('search_keyword', 'system'),
				'type' => 'text',
				'keyword' => 'q',
			);

			// Assign vars
			view::assign(array('filters' => $filters));

			// Did user submit the filter form?
			if ( input::post_get('do_search') && session::permission('news_search', 'news') )
			{
				$values = array();
				$params['total'] = $params['max'] = 0;

				// Check extra keyword
				$keyword = utf8::trim(input::post_get('q'));
				if ( $keyword )
				{
					$params['join_columns'][] = $this->search_model->prepareValue($keyword, 'n', array('data_title_' . session::item('language'), 'data_body_' . session::item('language')));
					$values['q'] = $keyword;
				}

				// Search news
				$searchID = $this->search_model->searchData('news', $filters, $params['join_columns'], $values, array('multilang' => true));

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
					router::redirect('news?search_id=' . $searchID);
				}
			}

			// Do we have a search ID?
			if ( !input::post_get('do_search') && input::get('search_id') )
			{
				// Get search
				if ( !( $search = $this->search_model->getSearch(input::get('search_id')) ) )
				{
					view::setError(__('search_expired', 'system'));
					router::redirect('news');
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
			// Count news
			if ( !( $params['total'] = $this->counters_model->countData('news', 0, 0, $params['join_columns'], $params['join_items'], $params) ) )
			{
				view::setInfo(__('no_entries', 'news'));
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
		$qstring['orderby'] = input::post_get('o') && in_array(input::post_get('o'), array('post_date', 'total_views', 'total_rating', 'total_votes', 'total_likes', 'total_comments')) ? input::post_get('o') : 'post_date';
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
