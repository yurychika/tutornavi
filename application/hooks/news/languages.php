<?php

class News_Languages_Hook extends Hook
{
	public function install($languageID, $language)
	{
		$this->dbforge->addColumn(':prefix:news_data', array(
			'name' => 'data_title_' . $language,
			'constraint' => 255,
			'type' => 'varchar',
			'null' => true,
		));

		$this->dbforge->addColumn(':prefix:news_data', array(
			'name' => 'data_body_' . $language,
			'type' => 'text',
		));

		$this->dbforge->addColumn(':prefix:news_data', array(
			'name' => 'data_meta_keywords_' . $language,
			'constraint' => 255,
			'type' => 'varchar',
			'null' => true,
		));

		$this->dbforge->addColumn(':prefix:news_data', array(
			'name' => 'data_meta_description_' . $language,
			'constraint' => 255,
			'type' => 'varchar',
			'null' => true,
		));

		$default = config::item('languages', 'core', 'keywords', config::item('language_id', 'system'));

		$this->db->query("UPDATE `:prefix:news_data` SET `data_title_" . $language . "`=`data_title_" . $default . "`");
		$this->db->query("UPDATE `:prefix:news_data` SET `data_body_" . $language . "`=`data_body_" . $default . "`");
		$this->db->query("UPDATE `:prefix:news_data` SET `data_meta_keywords_" . $language . "`=`data_meta_keywords_" . $default . "`");
		$this->db->query("UPDATE `:prefix:news_data` SET `data_meta_description_" . $language . "`=`data_meta_description_" . $default . "`");

		return true;
	}

	public function uninstall($languageID, $language)
	{
		$this->dbforge->dropColumns(':prefix:news_data', array('data_title_' . $language));
		$this->dbforge->dropColumns(':prefix:news_data', array('data_body_' . $language));
		$this->dbforge->dropColumns(':prefix:news_data', array('data_meta_keywords_' . $language));
		$this->dbforge->dropColumns(':prefix:news_data', array('data_meta_description_' . $language));

		return true;
	}
}
