<?php

class CP_Plugins_Comments_Controller extends Controller
{
	public $commentsPerPage = 30;

	public function __construct()
	{
		parent::__construct();

		// Does user have permission to access this plugin?
		if ( !session::permission('comments_manage', 'comments') )
		{
			view::noAccess();
		}

		view::setCustomParam('section', 'plugins');
		view::setCustomParam('options', config::item('cp_top_nav', 'lists', 'plugins', 'items', 'plugins/comments', 'items'));

		view::setTrail('cp/system/plugins', __('plugins', 'system_navigation'));
		view::setTrail('cp/plugins/comments', __('comments', 'system_navigation'));

		loader::model('comments/comments');
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
			'delete' => __('delete', 'system')
		);

		// Check form action
		if ( input::post('do_action') )
		{
			// Delete selected comments
			if ( input::post('action') == 'delete' )
			{
				if ( input::post('comment_id') && is_array(input::post('comment_id')) )
				{
					foreach ( input::post('comment_id') as $commentID )
					{
						$commentID = (int)$commentID;
						if ( $commentID && $commentID > 0 )
						{
							$this->delete($commentID);
						}
					}
				}
			}

			// Success
			view::setInfo(__('action_applied', 'system'));
			router::redirect('cp/plugins/comments?' . $qstring['url'] . 'page=' . $qstring['page']);
		}

		// Get comment
		$comments = array();
		if ( $params['total'] )
		{
			$comments = $this->comments_model->getComments('', 0, $params['join_columns'], $qstring['order'], $qstring['limit']);
		}

		// Create table grid
		$grid = array(
			'uri' => 'cp/plugins/comments',
			'keyword' => 'comments',
			'header' => array(
				'check' => array(
					'html' => 'comment_id',
					'class' => 'check',
				),
				'comment_body' => array(
					'html' => __('comment_body', 'comments'),
					'class' => 'comment',
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
				'actions' => array(
					'html' => __('actions', 'system'),
					'class' => 'actions',
				),
			),
			'content' => array()
		);

		// Create grid content
		foreach ( $comments as $comment )
		{
			$grid['content'][] = array(
				'check' => array(
					'html' => $comment['comment_id'],
				),
				'comment_body' => array(
					'html' => html_helper::anchor('cp/plugins/comments/edit/' . $comment['comment_id'], text_helper::truncate($comment['comment'], 64)),
				),
				'user' => array(
					'html' => users_helper::anchor($comment['user']),
				),
				'post_date' => array(
					'html' => date_helper::formatDate($comment['post_date']),
				),
				'actions' => array(
					'html' => array(
						'edit' => html_helper::anchor('cp/plugins/comments/edit/' . $comment['comment_id'], __('edit', 'system'), array('class' => 'edit')),
						'delete' => html_helper::anchor('cp/plugins/comments/delete/' . $comment['comment_id'] . '?' . $qstring['url'] . 'page=' . $qstring['page'], __('delete', 'system'), array('data-html' => __('comment_delete?', 'comments'), 'data-role' => 'confirm', 'class' => 'delete')),
					),
				),
			);
		}

		// Set pagination
		$config = array(
			'base_url' => config::siteURL('cp/plugins/comments?' . $qstring['url']),
			'total_items' => $params['total'],
			'items_per_page' => $this->commentsPerPage,
			'current_page' => $qstring['page'],
			'uri_segment' => 'page',
		);
		$pagination = loader::library('pagination', $config, null);

		// Filter hooks
		hook::filter('cp/plugins/comments/browse/grid', $grid);
		hook::filter('cp/plugins/comments/browse/actions', $actions);

		// Assign vars
		view::assign(array('grid' => $grid, 'actions' => $actions, 'pagination' => $pagination));

		// Set title
		view::setTitle(__('comments_manage', 'system_navigation'));

		// Set trail
		if ( $qstring['search_id'] )
		{
			view::setTrail('cp/plugins/comments?' . $qstring['url'] . 'page=' . $qstring['page'], __('search_results', 'system'));
		}

		// Assign actions
		view::setAction('#', __('search', 'system'), array('class' => 'icon-text icon-system-search', 'onclick' => '$(\'#comments-search\').toggle();return false;'));

		// Load view
		view::load('cp/plugins/comments/browse');
	}

	public function edit()
	{
		// Get URI vars
		$commentID = (int)uri::segment(5);

		// Get comment
		if ( !$commentID || !( $comment = $this->comments_model->getComment($commentID, array('escape' => false)) ) )
		{
			view::setError(__('no_comment', 'comments'));
			router::redirect('cp/plugins/comments');
		}

		// Get user
		if ( !( $user = $this->users_model->getUser($comment['poster_id']) ) )
		{
			view::setError(__('no_user', 'users'));
			router::redirect('cp/plugins/comments');
		}

		// Create fields
		$fields = array(
			array(
				'name' => __('comment_body', 'comments'),
				'keyword' => 'comment',
				'type' => 'textarea',
				'required' => 1,
				'rules' => array('trim', 'required'),
			),
		);

		// Assign vars
		view::assign(array('commentID' => $commentID, 'fields' => $fields, 'comment' => $comment, 'user' => $user));

		// Process form values
		if ( input::post('do_save_comment') )
		{
			$this->_saveComment($commentID, $comment, $fields);
		}

		// Set title
		view::setTitle(__('comment_edit', 'comments'));

		// Set trail
		view::setTrail('cp/plugins/comments/edit/' . $commentID, __('comment_edit', 'comments') . ' - ' . text_helper::entities($comment['comment']));

		// Load view
		view::load('cp/plugins/comments/edit');
	}

	protected function _saveComment($commentID, $comment, $fields)
	{
		// Check if demo mode is enabled
		if ( input::demo() ) return false;

		// Create rules
		$rules = array();
		foreach ( $fields as $field )
		{
			if ( isset($field['rules']) )
			{
				$rules[$field['keyword']] = array(
					'label' => $field['name'],
					'rules' => $field['rules'],
				);
			}
		}


		// Assign rules
		validate::setRules($rules);

		// Validate fields
		if ( !validate::run() )
		{
			return false;
		}

		// Get input data
		$data = array();
		foreach ( $fields as $field )
		{
			$data[$field['keyword']] = input::post($field['keyword']);
		}

		// Save comment
		if ( !( $commentID = $this->comments_model->saveComment($commentID, $data) ) )
		{
			if ( !validate::getTotalErrors() )
			{
				view::setError(__('save_error', 'system'));
			}
			return false;
		}

		// Success
		view::setInfo(__('comment_saved', 'comments'));
		router::redirect('cp/plugins/comments/edit/' . $commentID);
	}

	public function delete($actionID = false)
	{
		// Check if demo mode is enabled
		if ( input::demo(1, 'cp/plugins/comments') ) return false;

		// Get URI vars
		$commentID = $actionID ? $actionID : (int)uri::segment(5);

		// Get comment
		if ( !$commentID || !( $comment = $this->comments_model->getComment($commentID) ) )
		{
			view::setError(__('no_comment', 'comments'));
			router::redirect('cp/plugins/comments');
		}

		// Is this a valid resource?
		if ( $resource = config::item('resources', 'core', $comment['resource_id']) )
		{
			// Delete comment
			$this->comments_model->deleteComment($commentID, $resource, $comment['user_id'], $comment['poster_id'], $comment['item_id']);
		}

		// Is this an action call?
		if ( $actionID ) return;

		// Process query string
		$qstring = $this->parseQuerystring();

		// Success
		view::setInfo(__('comment_deleted', 'comments'));
		router::redirect('cp/plugins/comments?' . $qstring['url'] . 'page=' . $qstring['page']);
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
				'name' => __('user', 'system'),
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
				$params['join_columns'][] = $this->search_model->prepareValue($keyword, 'c', 'comment');
				$values['q'] = $keyword;
			}

			// Check extra user field
			$user = utf8::trim(input::post_get('user'));
			if ( $user )
			{
				$params['join_columns'][] = $this->search_model->prepareValue($user, 'u', 'user');
				$values['user'] = $user;
			}

			// Search comments
			$searchID = $this->search_model->searchData('comment', $filters, $params['join_columns'], $values);

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
				router::redirect('cp/plugins/comments?search_id=' . $searchID);
			}
		}

		// Do we have a search ID?
		if ( !input::post_get('do_search') && input::get('search_id') )
		{
			// Get search
			if ( !( $search = $this->search_model->getSearch(input::get('search_id')) ) )
			{
				view::setError(__('search_expired', 'system'));
				router::redirect('cp/plugins/comments');
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
			// Count comments
			if ( !( $params['total'] = $this->counters_model->countData('comment', 0, 0, $params['join_columns'], array(), $params) ) )
			{
				view::setInfo(__('no_comments', 'comments'));
			}
		}

		return $params;
	}

	protected function parseQuerystring($max = 0)
	{
		$qstring = array();

		// Set max page
		$maxpage = $max ? ceil($max / $this->commentsPerPage) : 0;

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
		$from = ( $qstring['page'] - 1 ) * $this->commentsPerPage;
		$qstring['limit'] = ( !$max || $from <= $max ? $from : $max ) . ', ' . $this->commentsPerPage;

		// Assign vars
		view::assign(array('qstring' => $qstring));

		return $qstring;
	}
}
