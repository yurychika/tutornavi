<?php

class Billing_Transactions_Model extends Model
{
	public function __construct()
	{
		parent::__construct();

		loader::helper('money');
	}

	public function saveInvoice($invoiceID, $userID, $typeID, $productID, $name, $amount, $params = '')
	{
		$invoice = array(
			'user_id' => $userID,
			'type_id' => $typeID,
			'product_id' => $productID,
			'params' => $params && is_array($params) ? json_encode($params) : $params,
			'name' => $name,
			'amount' => $amount,
		);

		// Do we have a recent unpaid invoice?
		if ( !$invoiceID && ( $recent = $this->getRecentInvoice($userID, $typeID, $productID) ) )
		{
			$invoiceID = $recent['invoice_id'];
			$invoice['post_date'] = date_helper::now();
		}

		// Is this a new invoice
		if ( !$invoiceID )
		{
			$invoice['post_date'] = date_helper::now();

			// Save invoice
			$invoiceID = $this->db->insert('billing_invoices', $invoice);

			// Action hook
			hook::action('billing/invoices/insert', $invoiceID, $invoice);
		}
		else
		{
			// Save invoice
			$this->db->update('billing_invoices', $invoice, array('invoice_id' => $invoiceID), 1);

			// Action hook
			hook::action('billing/invoices/update', $invoiceID, $invoice);
		}

		return $invoiceID;
	}

	public function payInvoice($invoiceID, $userID, $amount)
	{
		$retval = $this->db->update('billing_invoices', array('status' => 1), array('invoice_id' => $invoiceID), 1);

		if ( $retval )
		{
			// Update user counter
			$this->db->query("UPDATE `:prefix:users`
				SET `total_transactions`=`total_transactions`+1, `total_spent`=`total_spent`+?
				WHERE `user_id`=? LIMIT 1", array($amount, $userID));

			// Action hook
			hook::action('billing/invoices/payment', $invoiceID, $userID, $amount);
		}

		return $retval;
	}

	public function getRecentInvoice($userID, $typeID, $productID)
	{
		$invoice = $this->db->query("SELECT `invoice_id`
			FROM `:prefix:billing_invoices`
			WHERE `user_id`=? AND `type_id`=? AND `product_id`=? AND `status`=0
			ORDER BY `post_date` DESC
			LIMIT 1", array($userID, $typeID, $productID))->row();

		return $invoice;
	}

	public function getInvoice($invoiceID)
	{
		$invoice = $this->db->query("SELECT `invoice_id`, `user_id`, `type_id`, `product_id`, `params`, `name`, `amount`
			FROM `:prefix:billing_invoices` WHERE `invoice_id`=? LIMIT 1", array($invoiceID))->row();

		if ( $invoice )
		{
			$invoice['params'] = @json_decode($invoice['params'], true);
		}

		return $invoice;
	}

	public function saveLog($logID, $gatewayID, $status = 0, $error = '')
	{
		// Is this a new log?
		if ( !$logID )
		{
			$log = array(
				'gateway_id' => $gatewayID,
				'data' => '',
				'status' => $status ? 1 : 0,
				'error' => $error ? substr($error, 0, 255) : '',
				'post_date' => date_helper::now(),
			);

			$log['data'] .= $_GET ? trim("GET data\n" . array_helper::implodeArray(' = ', "\n", $_GET)) . "\n" : '';
			$log['data'] .= $_POST ? trim("POST data\n" . array_helper::implodeArray(' = ', "\n", $_POST)) : '';

			$logID = $this->db->insert('billing_logs', $log);
		}
		// Update error message
		else
		{
			$log = array(
				'status' => $status ? 1 : 0,
				'error' => $error ? substr($error, 0, 255) : '',
			);

			$this->db->update('billing_logs', $log, array('log_id' => $logID), 1);
		}

		return $logID;
	}

	public function isUniqueTransaction($gatewayID, $receiptID)
	{
		$transaction = $this->db->query("SELECT COUNT(`transaction_id`) AS `totalrows`
			FROM `:prefix:billing_transactions`
			WHERE `gateway_id`=? AND `receipt_id`=?
			LIMIT 1", array($gatewayID, $receiptID))->row();

		return $transaction['totalrows'] ? false : true;
	}

	public function saveTransaction($transactionID, $gatewayID, $invoiceID, $receiptID, $userID, $amount)
	{
		$transaction = array(
			'user_id' => $userID,
			'invoice_id' => $invoiceID,
			'gateway_id' => $gatewayID,
			'receipt_id' => $receiptID ? $receiptID : text_helper::random(10),
			'amount' => $amount,
		);

		// Is this a new transaction?
		if ( !$transactionID )
		{
			$transaction['post_date'] = date_helper::now();

			// Save transaction
			$transactionID = $this->db->insert('billing_transactions', $transaction);

			// Action hook
			hook::action('billing/transactions/insert', $transactionID, $transaction);
		}
		else
		{
			// Save transaction
			$this->db->update('billing_transactions', $transaction, array('transaction_id' => $transactionID), 1);

			// Action hook
			hook::action('billing/transactions/update', $transactionID, $transaction);
		}

		return $transactionID;
	}

	public function getTransaction($transactionID, $params = array())
	{
		$params['select_columns'] = "`i`.`invoice_id`, `i`.`type_id`, `i`.`product_id`, `i`.`name`, `i`.`status`";
		$params['join_tables'] = "INNER JOIN `:prefix:billing_invoices` AS `i` ON `t`.`invoice_id`=`i`.`invoice_id`";

		$transaction = $this->fields_model->getRow('billing_transaction', $transactionID, false, $params);

		if ( $transaction && ( !isset($params['escape']) || $params['escape'] ) )
		{
			$transaction['name'] = text_helper::entities($transaction['name']);
		}

		return $transaction;
	}

	public function countTransactions($columns = array(), $items = array(), $params = array())
	{
		$params['count'] = true;

		$total = $this->getTransactions($columns, false, 0, $params);

		return $total;
	}

	public function getTransactions($columns = array(), $order = false, $limit = 15, $params = array())
	{
		$params['select_columns'] = "`i`.`invoice_id`, `i`.`type_id`, `i`.`product_id`, `i`.`name`, `i`.`status`";
		$params['join_tables'] = "INNER JOIN `:prefix:billing_invoices` AS `i` ON `t`.`invoice_id`=`i`.`invoice_id`";

		// Do we need to count transactions?
		if ( isset($params['count']) && $params['count'] )
		{
			$total = $this->fields_model->countRows('billing_transaction', true, $columns, array(), $params);

			return $total;
		}

		// Get transactions
		$transactions = $this->fields_model->getRows('billing_transaction', true, false, $columns, array(), $order, $limit, $params);

		// Escape comments
		if ( !isset($params['escape']) || $params['escape'] )
		{
			foreach ( $transactions as $index => $transaction )
			{
				$transactions[$index]['name'] = text_helper::entities($transaction['name']);
			}
		}

		return $transactions;
	}

	public function deleteTransaction($transactionID, $transaction)
	{
		// Delete transaction
		$retval = $this->db->delete('billing_transactions', array('transaction_id' => $transactionID), 1);
		if ( $retval )
		{
			// Delete invoice
			$this->db->delete('billing_invoices', array('invoice_id' => $transaction['invoice_id']), 1);

			// Update user counter
			$this->db->query("UPDATE `:prefix:users` SET `total_transactions`=`total_transactions`-1 WHERE `user_id`=? LIMIT 1", array('user_id' => $transaction['user_id']));

			// Action hook
			hook::action('billing/transactions/delete', $transactionID, $transaction);
		}

		return $retval;
	}

	public function deleteUser($userID, $user, $update = false)
	{
		$invoiceIDs = array();

		// Get invoice IDs
		$result = $this->db->query("SELECT `invoice_id`, `amount` FROM `:prefix:billing_invoices` WHERE `user_id`=? LIMIT ?", array($userID, $user['total_transactions']))->result();
		foreach ( $result as $invoice )
		{
			$invoiceIDs[] = $invoice['invoice_id'];
		}

		// Delete invoices
		$retval = $this->db->delete('billing_invoices', array('user_id' => $userID), $user['total_transactions']);
		if ( $invoiceIDs )
		{
			// Delete transactions
			$this->db->delete('billing_transactions', array('invoice_id' => $invoiceIDs), count($invoiceIDs));

			if ( $update )
			{
				// Update user counters
				$this->db->update('users', array('total_transactions' => 0, 'total_spent' => 0), array('user_id' => $userID), 1);
			}
		}

		// Action hook
		hook::action('billing/transactions/delete_user', $userID, $user);

		return $retval;
	}
}
