<?php

class Payments_PayPal extends Library
{
	protected $config = array();
	protected $error = '';

	public function __construct($config = array())
	{
		$this->config = $config;
	}

	public function getManifest()
	{
		$params = array(
			'name' => 'PayPal',
			'settings' => array(
				array(
					'name' => 'Email address',
					'keyword' => 'email',
					'type' => 'email',
					'required' => true,
					'class' => 'input-xlarge',
					'value' => '',
				),
				array(
					'name' => 'Test mode',
					'keyword' => 'test',
					'type' => 'boolean',
					'value' => '0',
				),
				array(
					'keyword' => 'button',
					'type' => 'system',
					'value' => 'assets/images/billing/gateways/paypal.gif',
				),
			),
		);

		return $params;
	}

	public function validateSettings($settings)
	{
		return $settings;
	}

	public function getForm($invoiceID, $name, $amount, $cancel, $success)
	{
		$params = array(
			'cmd' => '_xclick',
			'quantity' => '1',
			'no_note' => '1',
			'no_shipping' => '1',
			'rm' => '2',
			'charset' => 'utf8',
			'business' => $this->config['email'],
			'item_name' => $name,
			'item_number' => $invoiceID,
			'amount' => $amount,
			'currency_code' => strtoupper(config::item('currency', 'billing')),
			'cancel_return' => html_helper::siteURL($cancel),
			'return' => html_helper::siteURL($success),
			'notify_url' => html_helper::siteURL('billing/payments/ipn/paypal'),
		);

		$form = ( $this->config['test'] ? 'https://www.sandbox.paypal.com/cgi-bin/webscr' : 'https://www.paypal.com/cgi-bin/webscr' ) . '?' . http_build_query($params, '', '&');

		return $form;
	}

	public function validatePayment($gatewayID)
	{
		// Verify payment status
		if ( strtolower(input::post('payment_status')) != 'completed' || strtolower(input::post('txn_type')) != 'web_accept' )
		{
			$this->setError('Invalid payment status.');
			return false;
		}

		// Verify receiver's email
		if ( strcasecmp($this->config['email'], input::post('business')) || strcasecmp($this->config['email'], input::post('receiver_email')) )
		{
			$this->setError('Invalid receiver email.');
			return false;
		}

		// Load http library
		loader::library('http');

		// Set parameters
		$params = $_POST;
		$params['cmd'] = '_notify-validate';

		// Run paypal request
		$response = $this->http->run(( $this->config['test'] ? 'https://www.sandbox.paypal.com/cgi-bin/webscr' : 'https://www.paypal.com/cgi-bin/webscr' ), 'POST', $params);

		// Verify reponse
		if ( strcasecmp(trim($response), 'verified') )
		{
			$this->setError('Invalid response: ' . $response);
			return false;
		}

		// Get parameters
		$receiptID = input::post('txn_id');
		$invoiceID = input::post('item_number');
		$amount = input::post('mc_gross');
		$currency = input::post('mc_currency');

		// Verify duplicates
		if ( !$this->transactions_model->isUniqueTransaction($gatewayID, $receiptID) )
		{
			$this->setError('Duplicate transaction: ' . $receiptID);
			return false;
		}

		// Get invoice
		if ( !( $invoice = $this->transactions_model->getInvoice($invoiceID) ) )
		{
			$this->setError('Invalid invoice ID: ' . $invoiceID);
			return false;
		}

		// Verify amount
		if ( strcmp($invoice['amount'], $amount) )
		{
			$this->setError('Invalid payment amount: ' . money_helper::symbol(config::item('currency', 'billing')) . $amount);
			return false;
		}

		$invoice['receipt_id'] = $receiptID;

		return $invoice;
	}

	public function completePayment()
	{
		return;
	}

	public function setError($error)
	{
		$this->error = $error;
	}

	public function getError()
	{
		return $this->error;
	}
}