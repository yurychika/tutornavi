<?php

class Messages_Languages_Hook extends Hook
{
	public function install($languageID, $language)
	{
		$this->dbforge->addColumn(':prefix:messages_templates', array(
			'name' => 'name_' . $language,
			'constraint' => 255,
			'type' => 'varchar',
			'null' => true,
		));

		$this->dbforge->addColumn(':prefix:messages_templates', array(
			'name' => 'subject_' . $language,
			'constraint' => 255,
			'type' => 'varchar',
			'null' => true,
		));

		$this->dbforge->addColumn(':prefix:messages_templates', array(
			'name' => 'message_' . $language,
			'type' => 'text',
		));

		$default = config::item('languages', 'core', 'keywords', config::item('language_id', 'system'));

		$this->db->query("UPDATE `:prefix:messages_templates` SET `name_" . $language . "`=`name_" . $default . "`");
		$this->db->query("UPDATE `:prefix:messages_templates` SET `subject_" . $language . "`=`subject_" . $default . "`");
		$this->db->query("UPDATE `:prefix:messages_templates` SET `message_" . $language . "`=`message_" . $default . "`");

		return true;
	}

	public function uninstall($languageID, $language)
	{
		$this->dbforge->dropColumns(':prefix:messages_templates', array('name_' . $language));
		$this->dbforge->dropColumns(':prefix:messages_templates', array('subject_' . $language));
		$this->dbforge->dropColumns(':prefix:messages_templates', array('message_' . $language));

		return true;
	}
}
