<?php

class CP_Content_Reports_Controller extends Controller
{
	public $reportsPerPage = 30;

	public function __construct()
	{
		parent::__construct();

		// Does user have permission to access this plugin?
		if ( !session::permission('reports_manage', 'reports') )
		{
			view::noAccess();
		}

		view::setCustomParam('section', 'content');
		view::setCustomParam('options', config::item('cp_top_nav', 'lists', 'content', 'items', 'content/reports', 'items'));

		view::setTrail('cp/system/plugins', __('content', 'system_navigation'));
		view::setTrail('cp/content/reports', __('reports', 'system_navigation'));

		loader::model('reports/reports');
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
		);

		// Process filters
		$params = $this->parseCounters($params);

		// Process query string
		$qstring = $this->parseQuerystring($params['total']);

		// Create actions
		$actions = array(
			0 => __('select', 'system'),
			'dismiss' => __('report_dismiss', 'reports')
		);

		// Check form action
		if ( input::post('do_action') )
		{
			// Delete selected reports
			if ( input::post('action') == 'dismiss' )
			{
				if ( input::post('report_id') && is_array(input::post('report_id')) )
				{
					foreach ( input::post('report_id') as $reportID )
					{
						$reportID = (int)$reportID;
						if ( $reportID && $reportID > 0 )
						{
							$this->dismiss($reportID);
						}
					}
				}
			}

			// Success
			view::setInfo(__('action_applied', 'system'));
			router::redirect('cp/content/reports?' . $qstring['url'] . 'page=' . $qstring['page']);
		}

		// Get reports
		$reports = array();
		if ( $params['total'] )
		{
			$reports = $this->reports_model->getReports('', $params['join_columns'], $qstring['order'], $qstring['limit']);
		}

		// Load subjects model
		loader::model('reports/subjects', array(), 'reports_subjects_model');

		// Get subjects
		$subjects = array();
		$data = $this->reports_subjects_model->getSubjects();
		foreach ( $data as $subject )
		{
			$subjects[$subject['subject_id']] = $subject['name'];
		}

		// Create table grid
		$grid = array(
			'uri' => 'cp/content/reports',
			'keyword' => 'reports',
			'header' => array(
				'check' => array(
					'html' => 'report_id',
					'class' => 'check',
				),
				'subject' => array(
					'html' => __('report_subject', 'reports'),
					'class' => 'subject',
				),
				'message' => array(
					'html' => __('report_message', 'reports'),
					'class' => 'comment',
				),
				'item' => array(
					'html' => __('report_item', 'reports'),
					'class' => 'item',
				),
				'user' => array(
					'html' => __('reporter', 'reports'),
					'class' => 'user',
				),
				'post_date' => array(
					'html' => __('post_date', 'system'),
					'class' => 'date',
					'sortable' => true,
				),
				'actions' => array(
					'html' => __('actions', 'system'),
					'class' => 'actions',
				),
			),
			'content' => array()
		);

		// Create grid content
		foreach ( $reports as $report )
		{
			$grid['content'][] = array(
				'check' => array(
					'html' => $report['report_id'],
				),
				'subject' => array(
					'html' => isset($subjects[$report['subject_id']]) ? $subjects[$report['subject_id']] : '',
				),
				'message' => array(
					'html' => text_helper::truncate($report['message'], 56). ( utf8::strlen($report['message']) > 56 ? ' ' . html_helper::anchor('', __('view', 'system'), array('data-title' => __('message', 'reports'), 'data-role' => 'modal', 'data-display' => 'html', 'data-html' => text_helper::entities($report['message']))) : '' ),
				),
				'item' => array(
					'html' => __(config::item('resources', 'core', $report['resource_id']), config::item('resources', 'core', config::item('resources', 'core', $report['resource_id']), 'plugin')),
				),
				'user' => array(
					'html' => users_helper::anchor($report['user']),
				),
				'post_date' => array(
					'html' => date_helper::formatDate($report['post_date']),
				),
				'actions' => array(
					'html' => array(
						'actions' => html_helper::anchor('cp/content/reports/actions/' . $report['report_id'], __('report_actions', 'reports'), array('class' => 'action', 'data-role' => 'modal', 'data-display' => 'iframe', 'data-title' => __('report_action_select', 'reports'))),
						'view' => html_helper::anchor('cp/content/reports/view/' . $report['report_id'], __('report_view', 'reports'), array('class' => 'view')),
						'dismiss' => html_helper::anchor('cp/content/reports/dismiss/' . $report['report_id'] . '?' . $qstring['url'] . 'page=' . $qstring['page'], __('report_dismiss', 'reports'), array('data-html' => __('report_dismiss?', 'reports'), 'data-role' => 'confirm', 'class' => 'delete')),
					),
				),
			);
		}

		// Set pagination
		$config = array(
			'base_url' => config::siteURL('cp/content/reports?' . $qstring['url']),
			'total_items' => $params['total'],
			'items_per_page' => $this->reportsPerPage,
			'current_page' => $qstring['page'],
			'uri_segment' => 'page',
		);
		$pagination = loader::library('pagination', $config, null);

		// Filter hooks
		hook::filter('cp/content/reports/browse/grid', $grid);
		hook::filter('cp/content/reports/browse/actions', $actions);

		// Assign vars
		view::assign(array('grid' => $grid, 'actions' => $actions, 'pagination' => $pagination));

		// Set title
		view::setTitle(__('reports_manage', 'system_navigation'));

		// Set trail
		if ( $qstring['search_id'] )
		{
			view::setTrail('cp/content/reports?' . $qstring['url'] . 'page=' . $qstring['page'], __('search_results', 'system'));
		}

		// Assign actions
		view::setAction('#', __('search', 'system'), array('class' => 'icon-text icon-system-search', 'onclick' => '$(\'#reports-search\').toggle();return false;'));

		// Load view
		view::load('cp/content/reports/browse');
	}

	public function view()
	{
		// Get URI vars
		$reportID = (int)uri::segment(5);

		// Get report
		if ( !$reportID || !( $report = $this->reports_model->getReport($reportID) ) )
		{
			view::setError(__('no_report', 'reports'));
			router::redirect('cp/content/reports');
		}

		// Get resource keyword
		$resource = config::item('resources', 'core', config::item('resources', 'core', $report['resource_id']));

		// Process query string
		$qstring = $this->parseQuerystring();

		// Load model
		loader::model(strpos($resource['model'], '_') === false ? $resource['model'] . '/' . $resource['model'] : str_replace('_', '/', $resource['model']), array(), $resource['model'] . '_model');

		// Get reported URL
		if ( !method_exists($this->{$resource['model'] . '_model'}, 'getReportedURL') || !( $url = $this->{$resource['model'] . '_model'}->getReportedURL($report['item_id']) ) )
		{
			view::setError(__('no_url', 'reports'));
			router::redirect('cp/content/reports?' . $qstring['url'] . 'page=' . $qstring['page']);
		}

		// Success
		router::redirect($url);
	}

	public function actions()
	{
		// Get URI vars
		$reportID = (int)uri::segment(5);

		// Get report
		if ( !$reportID || !( $report = $this->reports_model->getReport($reportID) ) )
		{
			view::setError(__('no_report', 'reports'));
			router::redirect('cp/content/reports');
		}

		// Get resource
		$resource = config::item('resources', 'core', config::item('resources', 'core', $report['resource_id']));

		// Load model
		loader::model(strpos($resource['model'], '_') === false ? $resource['model'] . '/' . $resource['model'] : str_replace('_', '/', $resource['model']), array(), $resource['model'] . '_model');

		// Get reported URL
		if ( !method_exists($this->{$resource['model'] . '_model'}, 'getReportedActions') || !( $actions = $this->{$resource['model'] . '_model'}->getReportedActions() ) )
		{
			view::setError(__('no_actions', 'reports'));
			return view::load('cp/system/elements/blank', array('autoclose' => true));
		}

		// Assign vars
		view::assign(array('actions' => $actions));

		// Process form values
		if ( input::post('do_apply_actions') )
		{
			return $this->_applyAction($report, $resource);
		}

		// Set title
		view::setTitle(__('report_action_select', 'reports'));

		// Load view
		view::load('cp/content/reports/actions');
	}

	protected function _applyAction($report, $resource)
	{
		// Check if demo mode is enabled
		if ( input::demo() )
		{
			view::load('cp/system/elements/blank', array('autoclose' => true));
			return false;
		}

		// Process query string
		$qstring = $this->parseQuerystring();

		// Load model
		loader::model(strpos($resource['model'], '_') === false ? $resource['model'] . '/' . $resource['model'] : str_replace('_', '/', $resource['model']), array(), $resource['model'] . '_model');

		// Get reported URL
		if ( !method_exists($this->{$resource['model'] . '_model'}, 'runReportedAction') )
		{
			view::setError(__('no_actions_method', 'reports'));
			return view::load('cp/system/elements/blank', array('autoclose' => true));
		}
		elseif ( !$this->{$resource['model'] . '_model'}->runReportedAction($report['item_id'], input::post('action')) )
		{
			view::setError(__('report_action_failed', 'reports'));
			return view::load('cp/system/elements/blank', array('autoclose' => true));
		}

		// Do we need to delete report?
		if ( input::post('dismiss') )
		{
			// Delete report
			$this->reports_model->deleteReport($report['report_id']);
		}

		// Success
		view::setInfo(__('action_applied', 'system'));
		view::load('cp/system/elements/blank', array('autoclose' => true));
	}

	public function dismiss($actionID = false)
	{
		// Check if demo mode is enabled
		if ( input::demo(1, 'cp/content/reports') ) return false;

		// Get URI vars
		$reportID = $actionID ? $actionID : (int)uri::segment(5);

		// Get report
		if ( !$reportID || !( $report = $this->reports_model->getReport($reportID) ) )
		{
			view::setError(__('no_report', 'reports'));
			router::redirect('cp/content/reports');
		}

		// Delete report
		$this->reports_model->deleteReport($reportID);

		// Is this an action call?
		if ( $actionID ) return;

		// Process query string
		$qstring = $this->parseQuerystring();

		// Success
		view::setInfo(__('report_dismissed', 'reports'));
		router::redirect('cp/content/reports?' . $qstring['url'] . 'page=' . $qstring['page']);
	}

	protected function parseCounters($params)
	{
		// Set filter fields
		$filters = array(
			array(
				'name' => __('keyword', 'system'),
				'type' => 'text',
				'keyword' => 'q',
			),
			array(
				'name' => __('reporter', 'reports'),
				'type' => 'text',
				'keyword' => 'user',
			),
		);

		// Assign vars
		view::assign(array('filters' => $filters, 'values' => array()));

		// Did user submit the filter form?
		if ( input::post_get('do_search') )
		{
			$values = array();

			// Check extra keyword field
			$keyword = utf8::trim(input::post_get('q'));
			if ( $keyword )
			{
				$params['join_columns'][] = $this->search_model->prepareValue($keyword, 'r', 'message');
				$values['q'] = $keyword;
			}

			// Check extra user field
			$user = utf8::trim(input::post_get('user'));
			if ( $user )
			{
				$params['join_columns'][] = $this->search_model->prepareValue($user, 'u', 'user');
				$values['user'] = $user;
			}

			// Search reports
			$searchID = $this->search_model->searchData('report', $filters, $params['join_columns'], $values);

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
				router::redirect('cp/content/reports?search_id=' . $searchID);
			}
		}

		// Do we have a search ID?
		if ( !input::post_get('do_search') && input::get('search_id') )
		{
			// Get search
			if ( !( $search = $this->search_model->getSearch(input::get('search_id')) ) )
			{
				view::setError(__('search_expired', 'system'));
				router::redirect('cp/content/reports');
			}

			// Combine results
			$params['join_columns'] = $search['conditions']['columns'];
			$params['values'] = $search['values'];
			$params['total'] = $search['results'];

			// Assign vars
			view::assign(array('values' => $search['values']));
		}
		else
		{
			// Count reports
			if ( !( $params['total'] = $this->counters_model->countData('report', 0, 0, $params['join_columns'], array(), $params) ) )
			{
				view::setInfo(__('no_reports', 'reports'));
			}
		}

		return $params;
	}

	protected function parseQuerystring($max = 0)
	{
		$qstring = array();

		// Set max page
		$maxpage = $max ? ceil($max / $this->reportsPerPage) : 0;

		// Get current page
		$qstring['page'] = (int)input::get('page', 1);
		$qstring['page'] = $qstring['page'] > 0 ? ( !$maxpage || $qstring['page'] <= $maxpage ? $qstring['page'] : $maxpage ) : 1;

		// Get search id
		$qstring['search_id'] = input::get('search_id');

		// Get order field and direction
		$qstring['orderby'] = input::post_get('o') && in_array(input::post_get('o'), array('post_date')) ? input::post_get('o') : 'post_date';
		$qstring['orderdir'] = input::post_get('d') && in_array(input::post_get('d'), array('asc', 'desc')) ? input::post_get('d') : 'desc';
		$qstring['order'] = $qstring['orderby'] ? array($qstring['orderby'] => $qstring['orderdir']) : array();

		// Create url string
		$qstring['url'] = ( $qstring['search_id'] ? 'search_id=' . $qstring['search_id'] . '&' : '' ) .
			( $qstring['orderby'] ? 'o=' . $qstring['orderby'] . '&' : '' ) .
			( $qstring['orderby'] && $qstring['orderdir'] ? 'd=' . $qstring['orderdir'] . '&' : '' );

		// Set limit
		$from = ( $qstring['page'] - 1 ) * $this->reportsPerPage;
		$qstring['limit'] = ( !$max || $from <= $max ? $from : $max ) . ', ' . $this->reportsPerPage;

		// Assign vars
		view::assign(array('qstring' => $qstring));

		return $qstring;
	}
}
