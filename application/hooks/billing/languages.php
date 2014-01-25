<?php

class Billing_Languages_Hook extends Hook
{
	public function install($languageID, $language)
	{
		$this->dbforge->addColumn(':prefix:billing_plans', array(
			'name' => 'name_' . $language,
			'type' => 'varchar',
			'constraint' => 255,
			'null' => true,
		));

		$this->dbforge->addColumn(':prefix:billing_plans', array(
			'name' => 'description_' . $language,
			'type' => 'varchar',
			'constraint' => 255,
			'null' => true,
		));

		$default = config::item('languages', 'core', 'keywords', config::item('language_id', 'system'));

		$this->db->query("UPDATE `:prefix:billing_plans` SET `name_" . $language . "`=`name_" . $default . "`");
		$this->db->query("UPDATE `:prefix:billing_plans` SET `description_" . $language . "`=`description_" . $default . "`");

		return true;
	}

	public function uninstall($languageID, $language)
	{
		$this->dbforge->dropColumns(':prefix:billing_plans', array('name_' . $language));
		$this->dbforge->dropColumns(':prefix:billing_plans', array('description_' . $language));

		return true;
	}
}
