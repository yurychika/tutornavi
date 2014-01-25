<?php

class Billing_Credits_Model extends Model
{
	public function __construct()
	{
		parent::__construct();

		loader::helper('money');
	}

	public function savePackage($packageID, $package)
	{
		// Is this a new package?
		if ( !$packageID )
		{
			// Save package
			$packageID = $this->db->insert('billing_credits', $package);

			// Action hook
			hook::action('billing/credits/packages/insert', $packageID, $package);
		}
		else
		{
			// Save package
			$this->db->update('billing_credits', $package, array('package_id' => $packageID), 1);

			// Action hook
			hook::action('billing/credits/packages/update', $packageID, $package);
		}

		return $packageID;
	}

	public function getPackage($packageID)
	{
		// Get package
		$package = $this->db->query("SELECT `package_id`, `price`, `credits`, `active` FROM `:prefix:billing_credits` WHERE `package_id`=? LIMIT 1", array($packageID))->row();

		return $package;
	}

	public function getPackages($active = true)
	{
		$packages = array();

		// Get packages
		$result = $this->db->query("SELECT `package_id`, `price`, `credits`, `active` FROM `:prefix:billing_credits` " . ( $active ? "WHERE `active`=1" : "") . " ORDER BY `credits` ASC")->result();
		foreach ( $result as $package )
		{
			$packages[$package['package_id']] = $package;
		}

		return $packages;
	}

	public function deletePackage($packageID, $package)
	{
		// Delete credits package
		$retval = $this->db->delete('billing_credits', array('package_id' => $packageID), 1);

		if ( $retval )
		{
			// Action hook
			hook::action('billing/credits/packages/delete', $packageID, $package);
		}

		return $retval;
	}

	public function addCredits($userID, $credits)
	{
		$retval = $this->db->query("UPDATE `:prefix:users` SET `total_credits`=`total_credits`+? WHERE `user_id`=? LIMIT 1", array($credits, $userID));

		// Action hook
		hook::action('billing/credits/add', $userID, $credits);

		return $retval;
	}

	public function removeCredits($userID, $credits)
	{
		$retval = $this->db->query("UPDATE `:prefix:users` SET `total_credits`=`total_credits`-? WHERE `user_id`=? LIMIT 1", array($credits, $userID));

		// Action hook
		hook::action('billing/credits/remove', $userID, $credits);

		return $retval;
	}

	public function process($userID, $productID, $params)
	{
		// Get credits package
		$package = $this->getPackage($productID);

		// Update total credits
		$retval = $this->addCredits($userID, $package['credits']);

		// Action hook
		hook::action('billing/credits/process', $productID, $userID, $package['credits']);

		return $retval;
	}
}
