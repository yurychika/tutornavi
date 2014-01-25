<?php

class Billing_Payments_Controller extends Users_Settings_Controller
{
	public function __construct($tabs = false)
	{
		parent::__construct($tabs, false);

		loader::model('billing/gateways');
		loader::model('billing/payments');
		loader::model('billing/transactions');
	}

	protected function process($gatewayID, $userID, $type, $productID, $name, $amount, $params = '', $cancel = '', $success = '')
	{
		// Set return URLs
		$cancel = $cancel ? $cancel : 'users/settings';
		$success = $success ? $success : 'billing/invoices';

		// Get payment type
		if ( !( $type = $this->payments_model->getPaymentType($type) ) )
		{
			return false;
		}

		// Get gateway
		if ( !$gatewayID || !( $gateway = $this->gateways_model->getGateway($gatewayID) ) || !$gateway['active'] )
		{
			view::setError(__('no_gateway', 'billing_gateways'));
			return false;
		}

		// Create invoice
		if ( !( $invoiceID = $this->transactions_model->saveInvoice(0, $userID, $type['type_id'], $productID, $name, $amount, $params) ) )
		{
			view::setError(__('invoice_error', 'billing_transactions'));
			return false;
		}

		// Get invoice
		if ( !( $invoice = $this->transactions_model->getInvoice($invoiceID) ) )
		{
			view::setError(__('no_invoice', 'billing_transactions'));
			return false;
		}

		// Load payment library
		$payment = loader::library('payments/' . $gateway['keyword'], $gateway['settings'], null);

		// Get payment method
		$form = $payment->getForm($invoiceID, $name, $amount, $cancel, $success);

		// Is this a URL?
		if ( preg_match('|^\w+://|i', $form) )
		{
			router::redirect($form);
		}
		// Is this a form?
		elseif ( preg_match('|^<form|i', $form) )
		{
			view::load('billing/redirect', array('form' => $form));
			return true;
		}

		view::setError(__('payment_invalid', 'billing_transactions'));
	}

	public function ipn()
	{
		// Get URI vars
		$gatewayID = uri::segment(4);

		// Get gateway
		if ( !$gatewayID || !( $gateway = $this->gateways_model->getGateway($gatewayID) ) || !$gateway['active'] )
		{
			die(__('no_gateway', 'billing_gateways'));
		}

		// Update gateway ID
		$gatewayID = $gateway['gateway_id'];

		// Load payment library
		$payment = loader::library('payments/' . $gateway['keyword'], $gateway['settings'], null);

		// Run IPN function
		if ( !( $invoice = $payment->validatePayment($gatewayID) ) )
		{
			$this->transactions_model->saveLog(0, $gatewayID, 0, $payment->getError());
			die($payment->getError());
		}

		// Save transaction
		if ( $transactionID = $this->transactions_model->saveTransaction(0, $gatewayID, $invoice['invoice_id'], $invoice['receipt_id'], $invoice['user_id'], $invoice['amount']) )
		{
			// Mark invoice as paid
			$this->transactions_model->payInvoice($invoice['invoice_id'], $invoice['user_id'], $invoice['amount']);
		}

		// Get payment type
		if ( !( $type = $this->payments_model->getPaymentType($invoice['type_id']) ) )
		{
			return false;
		}

		// Load payment type model
		$product = loader::model('billing/' . $type['keyword'], array(), null);

		// Setup product
		$product->process($invoice['user_id'], $invoice['product_id'], $invoice['params']);

		// Run complete function
		$payment->completePayment();

		// Log transaction
		$logID = $this->transactions_model->saveLog(0, $gatewayID, 1);

		die('ok');
	}
}
