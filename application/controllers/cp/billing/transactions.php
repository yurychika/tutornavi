<?php

class CP_Billing_Transactions_Controller extends Controller
{
	public $transactionPerPage = 30;

	public function __construct()
	{
		parent::__construct();

		// Does user have permission to access this plugin?
		if ( !session::permission('transactions_manage', 'billing') )
		{
			view::noAccess();
		}

		view::setCustomParam('section', 'billing');
		view::setCustomParam('options', config::item('cp_top_nav', 'lists', 'billing', 'items'));

		loader::model('billing/transactions');
		loader::model('billing/gateways');

		view::setTrail('cp/billing/transactions', __('billing', 'system_navigation'));
		view::setTrail('cp/billing/transactions/', __('billing_transactions', 'system_navigation'));
	}

	public function index()
	{
		$this->browse();
	}

	public function browse()
	{
		// Get gateways
		$gateways = array();
		foreach ( $this->gateways_model->getGateways(false, false) as $gateway )
		{
			$gateways[$gateway['gateway_id']] = $gateway['name'];
		}

		// Parameters
		$params = array(
			'join_columns' => array(),
		);

		// Process filters
		$params = $this->parseCounters($params, $gateways);

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
			// Delete selected transactions
			if ( input::post('action') == 'delete' )
			{
				if ( input::post('transaction_id') && is_array(input::post('transaction_id')) )
				{
					foreach ( input::post('transaction_id') as $transactionID )
					{
						$transactionID = (int)$transactionID;
						if ( $transactionID && $transactionID > 0 )
						{
							$this->delete($transactionID);
						}
					}
				}
			}

			// Success
			view::setInfo(__('action_applied', 'system'));
			router::redirect('cp/billing/transactions?' . $qstring['url'] . 'page=' . $qstring['page']);
		}

		// Get transaction
		$transactions = array();
		if ( $params['total'] )
		{
			$transactions = $this->transactions_model->getTransactions($params['join_columns'], $qstring['order'], $qstring['limit']);
		}

		// Create table grid
		$grid = array(
			'uri' => 'cp/billing/transactions',
			'keyword' => 'billing_transactions',
			'header' => array(
				'check' => array(
					'html' => 'transaction_id',
					'class' => 'check',
				),
				'product' => array(
					'html' => __('product', 'billing'),
					'class' => 'product',
				),
				'user' => array(
					'html' => __('user', 'system'),
					'class' => 'user',
				),
				'gateway' => array(
					'html' => __('payment_gateway', 'billing'),
					'class' => 'gateway',
				),
				'amount' => array(
					'html' => __('price', 'billing'),
					'class' => 'price',
					'sortable' => true,
				),
				'post_date' => array(
					'html' => __('payment_date', 'billing'),
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
		foreach ( $transactions as $transaction )
		{
			$grid['content'][] = array(
				'check' => array(
					'html' => $transaction['transaction_id'],
				),
				'product' => array(
					'html' => html_helper::anchor('cp/billing/transactions/view/'.$transaction['transaction_id'], $transaction['name']),
				),
				'user' => array(
					'html' => users_helper::anchor($transaction['user']),
				),
				'gateway' => array(
					'html' => $gateways[$transaction['gateway_id']],
				),
				'amount' => array(
					'html' => money_helper::symbol(config::item('currency', 'billing')) . $transaction['amount'],
				),
				'post_date' => array(
					'html' => date_helper::formatDate($transaction['post_date']),
				),
				'actions' => array(
					'html' => array(
						'edit' => html_helper::anchor('cp/billing/transactions/view/' . $transaction['transaction_id'], __('details', 'system'), array('class' => 'details')),
						'delete' => html_helper::anchor('cp/billing/transactions/delete/' . $transaction['transaction_id'] . '?' . $qstring['url'] . 'page=' . $qstring['page'], __('delete', 'system'), array('data-html' => __('transaction_delete?', 'billing_transactions'), 'data-role' => 'confirm', 'class' => 'delete')),
					),
				),
			);
		}

		// Set pagination
		$config = array(
			'base_url' => config::siteURL('cp/billing/transactions?' . $qstring['url']),
			'total_items' => $params['total'],
			'items_per_page' => $this->transactionPerPage,
			'current_page' => $qstring['page'],
			'uri_segment' => 'page',
		);
		$pagination = loader::library('pagination', $config, null);

		// Filter hooks
		hook::filter('cp/billing/transactions/browse/grid', $grid);
		hook::filter('cp/billing/transactions/browse/actions', $actions);

		// Assign vars
		view::assign(array('grid' => $grid, 'actions' => $actions, 'pagination' => $pagination));

		// Set title
		view::setTitle(__('billing_transactions_manage', 'system_navigation'));

		// Set trail
		if ( $qstring['search_id'] )
		{
			view::setTrail('cp/billing/transactions?' . $qstring['url'] . 'page=' . $qstring['page'], __('search_results', 'system'));
		}

		// Assign actions
		view::setAction('#', __('search', 'system'), array('class' => 'icon-text icon-system-search', 'onclick' => '$(\'#transactions-search\').toggle();return false;'));

		// Load view
		view::load('cp/billing/transactions/browse');
	}

	public function view()
	{
		// Get URI vars
		$transactionID = (int)uri::segment(5);

		// Get transaction
		if ( !$transactionID || !( $transaction = $this->transactions_model->getTransaction($transactionID) ) )
		{
			view::setError(__('no_transaction', 'billing_transactions'));
			router::redirect('cp/billing/transactions');
		}

		// Get user
		if ( !( $user = $this->users_model->getUser($transaction['user_id']) ) )
		{
			view::setError(__('no_user', 'users'));
			router::redirect('cp/billing/transactions');
		}

		// Get gateways
		$gateways = $this->gateways_model->getGateways(false);

		// Assign vars
		view::assign(array('transactionID' => $transactionID, 'transaction' => $transaction, 'user' => $user, 'gateways' => $gateways));

		// Set title
		view::setTitle(__('transaction_view', 'billing_transactions'));

		// Set trail
		view::setTrail('cp/billing/transactions/view/' . $transactionID, __('transaction_view', 'billing_transactions') . ' - ' . text_helper::entities($transaction['transaction_id']));

		// Load view
		view::load('cp/billing/transactions/view');
	}

	public function delete($actionID = false)
	{
		// Check if demo mode is enabled
		if ( input::demo(1, 'cp/billing/transactions') ) return false;

		// Get URI vars
		$transactionID = $actionID ? $actionID : (int)uri::segment(5);

		// Get transaction
		if ( !$transactionID || !( $transaction = $this->transactions_model->getTransaction($transactionID) ) )
		{
			view::setError(__('no_transaction', 'billing_transactions'));
			router::redirect('cp/billing/transactions');
		}

		// Delete transaction
		$this->transactions_model->deleteTransaction($transactionID, $transaction);

		// Is this an action call?
		if ( $actionID ) return;

		// Process query string
		$qstring = $this->parseQuerystring();

		// Success
		view::setInfo(__('transaction_deleted', 'billing_transactions'));
		router::redirect('cp/billing/transactions?' . $qstring['url'] . 'page=' . $qstring['page']);
	}

	protected function parseCounters($params, $gateways)
	{
		// Set filter fields
		$filters = array(
			array(
				'name' => __('receipt_id', 'billing_transactions'),
				'type' => 'text',
				'keyword' => 'receipt_id',
			),
			array(
				'name' => __('product', 'billing'),
				'type' => 'text',
				'keyword' => 'product',
			),
			array(
				'name' => __('payment_gateway', 'billing'),
				'type' => 'select',
				'items' => $gateways,
				'keyword' => 'gateway_id',
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

			// Check extra product field
			$product = input::post_get('product');
			if ( $product != '' )
			{
				$params['join_columns'][] = "`i`.`name` LIKE '" . trim($this->db->escape($product, true), "'") . "'";
				$values['product'] = $product;
			}

			// Check extra receipt field
			$receipt_id = input::post_get('receipt_id');
			if ( $receipt_id != '' )
			{
				$params['join_columns'][] = "`t`.`receipt_id`=" . $this->db->escape($receipt_id);
				$values['receipt_id'] = $receipt_id;
			}

			// Check extra gateway field
			$gateway_id = input::post_get('gateway_id');
			if ( $gateway_id && isset($gateways[$gateway_id]) )
			{
				$params['join_columns'][] = "`t`.`gateway_id`=" . $gateway_id;
				$values['gateway_id'] = $gateway_id;
			}

			// Check extra user field
			$user = utf8::trim(input::post_get('user'));
			if ( $user )
			{
				$params['join_columns'][] = $this->search_model->prepareValue($user, 'u', 'user');
				$values['user'] = $user;
			}

			// Search transactions
			$searchID = $this->search_model->searchData('billing_transaction', $filters, $params['join_columns'], $values);

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
				router::redirect('cp/billing/transactions?search_id=' . $searchID);
			}
		}

		// Do we have a search ID?
		if ( !input::post_get('do_search') && input::get('search_id') )
		{
			// Get search
			if ( !( $search = $this->search_model->getSearch(input::get('search_id')) ) )
			{
				view::setError(__('search_expired', 'system'));
				router::redirect('cp/billing/transactions');
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
			// Count transactions
			if ( !( $params['total'] = $this->counters_model->countData('billing_transaction', 0, 0, $params['join_columns'], array(), $params) ) )
			{
				view::setInfo(__('no_transactions', 'billing_transactions'));
			}
		}

		return $params;
	}

	protected function parseQuerystring($max = 0)
	{
		$qstring = array();

		// Set max page
		$maxpage = $max ? ceil($max / $this->transactionPerPage) : 0;

		// Get current page
		$qstring['page'] = (int)input::get('page', 1);
		$qstring['page'] = $qstring['page'] > 0 ? ( !$maxpage || $qstring['page'] <= $maxpage ? $qstring['page'] : $maxpage ) : 1;

		// Get search id
		$qstring['search_id'] = input::get('search_id');

		// Get order field and direction
		$qstring['orderby'] = input::post_get('o') && in_array(input::post_get('o'), array('amount', 'post_date')) ? input::post_get('o') : 'post_date';
		$qstring['orderdir'] = input::post_get('d') && in_array(input::post_get('d'), array('asc', 'desc')) ? input::post_get('d') : 'desc';
		$qstring['order'] = $qstring['orderby'] ? array($qstring['orderby'] => $qstring['orderdir']) : array();

		// Create url string
		$qstring['url'] = ( $qstring['search_id'] ? 'search_id=' . $qstring['search_id'] . '&' : '' ) .
			( $qstring['orderby'] ? 'o=' . $qstring['orderby'] . '&' : '' ) .
			( $qstring['orderby'] && $qstring['orderdir'] ? 'd=' . $qstring['orderdir'] . '&' : '' );

		// Set limit
		$from = ( $qstring['page'] - 1 ) * $this->transactionPerPage;
		$qstring['limit'] = ( !$max || $from <= $max ? $from : $max ) . ', ' . $this->transactionPerPage;

		// Assign vars
		view::assign(array('qstring' => $qstring));

		return $qstring;
	}
}
