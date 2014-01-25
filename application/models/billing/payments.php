<?php

class Billing_Payments_Model extends Model
{
	public function getPaymentType($type)
	{
		if ( is_numeric($type) )
		{
			$column = 'type_id';
		}
		else
		{
			$column = 'keyword';
		}

		$type = $this->db->query("SELECT `type_id`, `keyword` FROM `:prefix:billing_types` WHERE `" . $column . "`=? LIMIT 1", array($type))->row();

		return $type;
	}

	public function getPaymentTypes()
	{
		// Get types
		$types = array();
		foreach ( $this->db->query("SELECT `type_id`, `keyword` FROM `:prefix:billing_types` ORDER BY `keyword` DESC")->result() as $type )
		{
			$types[$type['keyword']] = $type;
		}

		return $types;
	}
}
