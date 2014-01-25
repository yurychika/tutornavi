<?php

class CP_Content_News_Controller extends Controller
{
	public $newsPerPage = 30;

	public function __construct()
	{
		parent::__construct();

		// Does user have permission to access this plugin?
		if ( !session::permission('news_manage', 'news') )
		{
			view::noAccess();
		}

		view::setCustomParam('section', 'content');
		view::setCustomParam('options', config::item('cp_top_nav', 'lists', 'content', 'items', 'content/news', 'items'));

		view::setTrail('cp/system/plugins', __('content', 'system_navigation'));
		view::setTrail('cp/content/news', __('news', 'system_navigation'));

		loader::model('news/news');
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
			'delete' => __('delete', 'system'),
		);

		// Check form action
		if ( input::post('do_action') )
		{
			// Delete selected news
			if ( input::post('action') == 'delete' )
			{
				if ( input::post('news_id') && is_array(input::post('news_id')) )
				{
					foreach ( input::post('news_id') as $newsID )
					{
						$newsID = (int)$newsID;
						if ( $newsID && $newsID > 0 )
						{
							$this->delete($newsID);
						}
					}
				}
			}

			// Success
			view::setInfo(__('action_applied', 'system'));
			router::redirect('cp/content/news?' . $qstring['url'] . 'page=' . $qstring['page']);
		}

		// Get news
		$news = array();
		if ( $params['total'] )
		{
			$news = $this->news_model->getEntries('in_list', $params['join_columns'], $params['join_items'], $qstring['order'], $qstring['limit']);
		}

		// Create table grid
		$grid = array(
			'uri' => 'cp/content/news',
			'keyword' => 'news',
			'header' => array(
				'check' => array(
					'html' => 'news_id',
					'class' => 'check',
				),
				'data_title_' . session::item('language') => array(
					'html' => __('name', 'system'),
					'class' => 'name',
					'sortable' => true,
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
		foreach ( $news as $entry )
		{
			$grid['content'][] = array(
				'check' => array(
					'html' => $entry['news_id'],
				),
				'data_title_' . session::item('language') => array(
					'html' => html_helper::anchor('cp/content/news/edit/' . $entry['news_id'], text_helper::truncate($entry['data_title'], 64)),
				),
				'post_date' => array(
					'html' => date_helper::formatDate($entry['post_date']),
				),
				'status' => array(
					'html' => $entry['active'] ? '<span class="label success small">' . __('yes', 'system') . '</span>' : '<span class="label important small">' . __('no', 'system') . '</span>',
				),
				'actions' => array(
					'html' => array(
						'edit' => html_helper::anchor('cp/content/news/edit/'.$entry['news_id'], __('edit', 'system'), array('class' => 'edit')),
						'delete' => html_helper::anchor('cp/content/news/delete/'.$entry['news_id'].'?'.$qstring['url'].'page='.$qstring['page'], __('delete', 'system'), array('data-html' => __('entry_delete?', 'news'), 'data-role' => 'confirm', 'class' => 'delete')),
					),
				),
			);
		}

		// Set pagination
		$config = array(
			'base_url' => config::siteURL('cp/content/news?' . $qstring['url']),
			'total_items' => $params['total'],
			'items_per_page' => $this->newsPerPage,
			'current_page' => $qstring['page'],
			'uri_segment' => 'page',
		);
		$pagination = loader::library('pagination', $config, null);

		// Filter hooks
		hook::filter('cp/content/news/browse/grid', $grid);
		hook::filter('cp/content/news/browse/actions', $actions);

		// Assign vars
		view::assign(array('grid' => $grid, 'actions' => $actions, 'pagination' => $pagination));

		// Set title
		view::setTitle(__('news_manage', 'system_navigation'));

		// Set trail
		if ( $qstring['search_id'] )
		{
			view::setTrail('cp/content/news?' . $qstring['url'] . 'page=' . $qstring['page'], __('search_results', 'system'));
		}

		// Assign actions
		view::setAction('cp/content/news/edit/', __('entry_new', 'news'), array('class' => 'icon-text icon-news-new'));
		view::setAction('#', __('search', 'system'), array('class' => 'icon-text icon-system-search', 'onclick' => '$(\'#news-search\').toggle();return false;'));

		// Load view
		view::load('cp/content/news/browse');
	}

	public function edit()
	{
		// Get URI vars
		$newsID = (int)uri::segment(5);

		// Get fields
		$fields = $this->fields_model->getFields('news', 0, 'edit');

		// Get news
		$news = array();
		if ( $newsID && !( $news = $this->news_model->getEntry($newsID, $fields, array('escape' => false, 'parse' => false, 'multilang' => true)) ) )
		{
			view::setError(__('no_entry', 'news'));
			router::redirect('cp/content/news');
		}

		// Options
		$options = array();

		// Do we need to add enable comments field?
		if ( config::item('news_comments', 'news') )
		{
			$options[] = array(
				'name' => __('comments_enable', 'comments', array(), array(), false),
				'keyword' => 'comments',
				'type' => 'boolean',
				'comments' => 1,
			);
		}

		// Do we need to add enable likes field?
		if ( config::item('news_rating', 'news') == 'likes' )
		{
			$options[] = array(
				'name' => __('likes_enable', 'comments', array(), array(), false),
				'keyword' => 'likes',
				'type' => 'boolean',
				'likes' => 1,
			);
		}

		// Do we need to add enable votes field?
		if ( config::item('news_rating', 'news') == 'stars' )
		{
			$options[] = array(
				'name' => __('rating_enable', 'comments', array(), array(), false),
				'keyword' => 'votes',
				'type' => 'boolean',
				'votes' => 1,
			);
		}

		// Active field
		$options[] = array(
			'name' => __('active', 'system', array(), array(), false),
			'keyword' => 'active',
			'type' => 'boolean',
			'active' => 1,
		);

		// Assign vars
		view::assign(array('newsID' => $newsID, 'news' => $news, 'fields' => $fields, 'options' => $options));

		// Process form values
		if ( input::post('do_save_news') )
		{
			$this->_saveEntry($newsID, $news, $fields);
		}

		// Set title
		view::setTitle($newsID ? __('entry_edit', 'news') : __('entry_new', 'news'));

		// Set trail
		if ( $newsID )
		{
			view::setTrail('cp/content/news/edit/' . $newsID, __('entry_edit', 'news') . ' - ' . text_helper::entities($news['data_title_' . session::item('language')]));
		}
		else
		{
			view::setTrail('cp/content/news/edit/' . $newsID, __('entry_new', 'news'));
		}

		// Set actions
		if ( count(config::item('languages', 'core', 'keywords')) > 1 )
		{
			view::setAction('translate', '');
		}

		// Load view
		view::load('cp/content/news/edit');
	}

	protected function _saveEntry($newsID, $news, $fields)
	{
		// Check if demo mode is enabled
		if ( input::demo() ) return false;

		// Extra rules
		$rules = array(
			'comments' => array('rules' => 'intval'),
			'likes' => array('rules' => 'intval'),
			'votes' => array('rules' => 'intval'),
			'active' => array('rules' => 'intval'),
		);

		// Validate form values
		if ( !$this->fields_model->validateValues($fields, $rules) )
		{
			return false;
		}

		// Extras
		$extra = array();
		$extra['comments'] = config::item('news_comments', 'news') ? (int)input::post('comments') : 1;
		$extra['likes'] = config::item('news_rating', 'news') == 'likes' ? (int)input::post('likes') : 1;
		$extra['votes'] = config::item('news_rating', 'news') == 'stars' ? (int)input::post('votes') : 1;
		$extra['active'] = (int)input::post('active');

		// Save news
		if ( !( $newsID = $this->news_model->saveEntryData($newsID, $news, $fields, $extra) ) )
		{
			if ( !validate::getTotalErrors() )
			{
				view::setError(__('save_error', 'system'));
			}
			return false;
		}

		// Success
		view::setInfo(__('entry_saved', 'news'));
		router::redirect('cp/content/news/edit/' . $newsID);
	}

	public function delete($actionID = false)
	{
		// Check if demo mode is enabled
		if ( input::demo(1, 'cp/content/news') ) return false;

		// Get URI vars
		$newsID = $actionID ? $actionID : (int)uri::segment(5);

		// Get news
		if ( !$newsID || !( $news = $this->news_model->getEntry($newsID) ) )
		{
			view::setError(__('no_entry', 'news'));
			router::redirect('cp/content/news');
		}

		// Delete news
		$this->news_model->deleteEntry($newsID, $news);

		// Is this an action call?
		if ( $actionID ) return;

		// Process query string
		$qstring = $this->parseQuerystring();

		// Success
		view::setInfo(__('entry_deleted', 'news'));
		router::redirect('cp/content/news?' . $qstring['url'] . 'page=' . $qstring['page']);
	}

	protected function parseCounters($params)
	{
		// Get fields
		$filters = $this->fields_model->getFields('news', 0, 'edit', 'in_search', true);

		// Set extra fields
		$filters[] = array(
			'name' => __('search_keyword', 'system'),
			'type' => 'text',
			'keyword' => 'q',
		);
		$filters[] = array(
			'name' => __('status', 'system'),
			'type' => 'boolean',
			'keyword' => 'status',
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
				$params['join_columns'][] = $this->search_model->prepareValue($keyword, 'n', array('data_title_' . session::item('language'), 'data_body_' . session::item('language')));
				$values['q'] = $keyword;
			}

			// Check extra status field
			$status = input::post_get('status');
			if ( $status != '' && ($status == 1 || $status == 0) )
			{
				$params['join_columns'][] = '`n`.`active`=' . $status;
				$values['active'] = $status;
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
				$params['total'] = 0;
				return $params;
			}
			// Redirect to search results
			else
			{
				router::redirect('cp/content/news?search_id=' . $searchID);
			}
		}

		// Do we have a search ID?
		if ( !input::post_get('do_search') && input::get('search_id') )
		{
			// Get search
			if ( !( $search = $this->search_model->getSearch(input::get('search_id')) ) )
			{
				view::setError(__('search_expired', 'system'));
				router::redirect('cp/content/news');
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
			// Count news
			if ( !( $params['total'] = $this->counters_model->countData('news', 0, 0, $params['join_columns'], $params['join_items'], $params) ) )
			{
				view::setInfo(__('no_entries', 'news'));
			}
		}

		return $params;
	}

	protected function parseQuerystring($max = 0)
	{
		$qstring = array();

		// Set max page
		$maxpage = $max ? ceil($max / $this->newsPerPage) : 0;

		// Get current page
		$qstring['page'] = (int)input::get('page', 1);
		$qstring['page'] = $qstring['page'] > 0 ? ( !$maxpage || $qstring['page'] <= $maxpage ? $qstring['page'] : $maxpage ) : 1;

		// Get search id
		$qstring['search_id'] = input::get('search_id');

		// Get order field and direction
		$qstring['orderby'] = input::post_get('o') && in_array(input::post_get('o'), array('data_title_' . session::item('language'), 'post_date', 'total_views', 'total_rating', 'total_votes', 'total_likes', 'total_comments')) ? input::post_get('o') : 'post_date';
		$qstring['orderdir'] = input::post_get('d') && in_array(input::post_get('d'), array('asc', 'desc')) ? input::post_get('d') : 'desc';
		$qstring['order'] = $qstring['orderby'] ? array($qstring['orderby'] => $qstring['orderdir']) : array();

		// Create url string
		$qstring['url'] = ( $qstring['search_id'] ? 'search_id=' . $qstring['search_id'] . '&' : '' ) .
			( $qstring['orderby'] ? 'o=' . $qstring['orderby'] . '&' : '' ) .
			( $qstring['orderby'] && $qstring['orderdir'] ? 'd=' . $qstring['orderdir'] . '&' : '' );

		// Set limit
		$from = ( $qstring['page'] - 1 ) * $this->newsPerPage;
		$qstring['limit'] = ( !$max || $from <= $max ? $from : $max ) . ', ' . $this->newsPerPage;

		// Assign vars
		view::assign(array('qstring' => $qstring));

		return $qstring;
	}
}
