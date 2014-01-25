<?php

class Billing_Gateways_Model extends Model
{
	public function __construct()
	{
		parent::__construct();

		loader::helper('money');
	}

	public function getGateways($active = true, $settings = true)
	{
		$gateways = array();

		foreach ( $this->db->query("SELECT `gateway_id`, `name`, `keyword`, `active`, `settings`
			FROM `:prefix:billing_gateways`
			" . ( $active ? "WHERE `active`=1" : "" ) . "
			ORDER BY `name` ASC")->result() as $gateway )
		{
			$gateways[$gateway['gateway_id']] = $gateway;

			if ( $settings )
			{
				if ( !($gateways[$gateway['gateway_id']]['settings'] = @json_decode($gateway['settings'], true)) )
				{
					$gateways[$gateway['gateway_id']]['settings'] = array();
				}
			}
		}

		return $gateways;
	}

	public function getGateway($keyword)
	{
		$gateway = $this->db->query("SELECT `gateway_id`, `name`, `keyword`, `settings`, `active`
			FROM `:prefix:billing_gateways`
			WHERE `keyword`=? LIMIT 1", array($keyword))->row();

		if ( $gateway )
		{
			if ( !($gateway['settings'] = @json_decode($gateway['settings'], true)) )
			{
				$gateway['settings'] = array();
			}
		}

		return $gateway;
	}

	public function scanGateways($merge = true)
	{
		$gateways = array();

		// Load file helper and read gateways directory
		loader::helper('file');
		$dirs = file_helper::scanFileNames(DOCPATH . 'libraries/payments');

		// Loop through found directories
		foreach ( $dirs as $gateway )
		{
			// Remove file extension
			$gateway = substr($gateway, 0, -4);

			if ( $manifest = $this->getManifest($gateway) )
			{
				$gateways[$gateway] = $manifest;
			}
		}

		// Do we need to merge results with installed gateways?
		if ( $merge )
		{
			// Loop through installed gateways
			foreach ( $this->getGateways(false, false) as $gateway )
			{
				if ( isset($gateways[$gateway['keyword']]) )
				{
					$gateways[$gateway['keyword']]['gateway_id'] = $gateway['gateway_id'];
					$gateways[$gateway['keyword']]['name'] = $gateway['name'];
					$gateways[$gateway['keyword']]['active'] = $gateway['active'];
				}
			}
		}

		// Order gateways
		ksort($gateways);

		return $gateways;
	}

	public function getManifest($keyword)
	{
		$manifest = array();

		// Load gateway
		$gateway = loader::library('payments/' . $keyword, array(), null);

		// Does gateway exist?
		if ( $gateway )
		{
			$params = $gateway->getManifest();

			$manifest = array(
				'keyword' => $keyword,
				'name' => $params['name'],
				'settings' => isset($params['settings']) && is_array($params['settings']) ? $params['settings'] : array(),
				'values' => array(),
				'active' => 0,
			);
		}

		return $manifest;
	}

	public function saveSettings($name, $keyword, $settings, $active)
	{
		$retval = $this->db->update('billing_gateways', array('name' => $name, 'settings' => json_encode($settings), 'active' => $active), array('keyword' => $keyword), 1);

		if ( $retval )
		{
			// Action hook
			hook::action('billing/gateways/settings/update', $keyword, $settings);
		}

		return $retval;
	}

	public function install($keyword)
	{
		// Get gateway
		$manifest = $this->getManifest($keyword);

		// Create data array
		$data = array(
			'name' => $manifest['name'],
			'keyword' => $manifest['keyword'],
			'settings' => array(),
		);

		// Parse settings
		foreach ( $manifest['settings'] as $setting )
		{
			if ( isset($setting['value']) )
			{
				$data['settings'][$setting['keyword']] = $setting['value'];
			}
		}

		// Encode settings
		$data['settings'] = json_encode($data['settings']);

		// Insert gateway
		$gatewayID = $this->db->insert('billing_gateways', $data);

		if ( $gatewayID )
		{
			// Action hook
			hook::action('videos/gateways/install', $gatewayID, $data);
		}

		return $gatewayID;
	}

	public function uninstall($gateway)
	{
		// Delete gateway
		$retval = $this->db->delete('billing_gateways', array('gateway_id' => $gateway['gateway_id']), 1);

		if ( $retval )
		{
			// Action hook
			hook::action('billing/gateways/uninstall', $gateway['gateway_id'], $gateway);
		}

		return $retval;
	}

	public function isInUse($gatewayID)
	{
		// Check if there are any existing transactions using this gateway
		$retval = $this->db->query("SELECT COUNT(`transaction_id`) AS `totalrows`
			FROM `:prefix:billing_transactions`
			WHERE `gateway_id`=? LIMIT 1", array($gatewayID))->row();

		return $retval['totalrows'] ? true : false;
	}
}
