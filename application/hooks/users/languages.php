<?php

class Users_Languages_Hook extends Hook
{
	public function install($languageID, $language)
	{
		$this->dbforge->addColumn(':prefix:users_groups', array(
			'name' => 'name_' . $language,
			'constraint' => 255,
			'type' => 'varchar',
			'null' => true,
		));

		$this->dbforge->addColumn(':prefix:users_types', array(
			'name' => 'name_' . $language,
			'constraint' => 255,
			'type' => 'varchar',
			'null' => true,
		));

		$default = config::item('languages', 'core', 'keywords', config::item('language_id', 'system'));

		$this->db->query("UPDATE `:prefix:users_groups` SET `name_" . $language . "`=`name_" . $default . "`");
		$this->db->query("UPDATE `:prefix:users_types` SET `name_" . $language . "`=`name_" . $default . "`");

		return true;
	}

	public function uninstall($languageID, $language)
	{
		$this->dbforge->dropColumns(':prefix:users_groups', array('name_' . $language));
		$this->dbforge->dropColumns(':prefix:users_types', array('name_' . $language));

		return true;
	}
}
