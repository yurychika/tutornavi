<?php

class Billing_Invoices_Controller extends Billing_Payments_Controller
{
	public function __construct()
	{
		parent::__construct(true);

		// Is user loggedin ?
		if ( !users_helper::isLoggedin() )
		{
			router::redirect('users/login');
		}
		elseif ( !config::item('invoices_active', 'billing') )
		{
			router::redirect('users/settings');
		}

		loader::model('billing/gateways');
		loader::model('billing/transactions');
	}

	public function index()
	{
		$this->invoices();
	}

	public function invoices()
	{
		// Get page
		$page = is_numeric(input::get('page')) && input::get('page') > 0 ? input::get('page') : 1;

		// Parameters
		$params = array(
			'join_columns' => array(
				'`t`.`user_id`=' . session::item('user_id'),
			),
		);

		// Process query string
		$qstring = $this->parseQuerystring(config::item('invoices_per_page', 'billing'), session::item('total_transactions'));

		// Get invoices
		$invoices = array();
		if ( session::item('total_transactions') )
		{
			$invoices = $this->transactions_model->getTransactions($params['join_columns'], '', $qstring['limit']);
		}
		else
		{
			view::setInfo(__('no_invoices_user', 'billing_transactions'));
		}

		// Set pagination
		$config = array(
			'base_url' => config::siteURL('billing/invoices?'),
			'total_items' => session::item('total_transactions'),
			'items_per_page' => config::item('invoices_per_page', 'billing'),
			'current_page' => $page,
			'uri_segment' => 'page',
		);
		$pagination = loader::library('pagination', $config, null);

		// Assign vars
		view::assign(array('invoices' => $invoices, 'pagination' => $pagination));

		// Set title
		view::setTitle(__('invoices', 'billing_transactions'));

		// Load view
		view::load('billing/invoices');
	}

	protected function parseQuerystring($pagination = 15, $max = 0)
	{
		$qstring = array();

		// Set max page
		$maxpage = $max ? ceil($max / $pagination) : 0;

		// Get current page
		$qstring['page'] = (int)input::get('page', 1);
		$qstring['page'] = $qstring['page'] > 0 ? ( !$maxpage || $qstring['page'] <= $maxpage ? $qstring['page'] : $maxpage ) : 1;

		// Set limit
		$from = ( $qstring['page'] - 1 ) * $pagination;
		$qstring['limit'] = ( !$max || $from <= $max ? $from : $max ) . ', ' . ( !$max || $max >= $pagination ? $pagination : $max );

		// Assign vars
		view::assign(array('qstring' => $qstring));

		return $qstring;
	}
}
