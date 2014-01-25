<?php defined('SYSPATH') || die('No direct script access allowed.');

class Plugins_News_Install extends Plugins
{
	public function __construct($manifest = array())
	{
		parent::__construct($manifest);
	}

	public function update()
	{
		return true;
	}

	public function install()
	{
		$this->dbforge->dropTable(':prefix:news_data');
		$this->dbforge->createTable(':prefix:news_data',
			array(
				array('name' => 'news_id', 'type' => 'bigint', 'constraint' => 11, 'unsigned' => true, 'null' => false, 'auto_increment' => true),
				array('name' => 'post_date', 'type' => 'int', 'constraint' => 10, 'unsigned' => true, 'null' => false, 'default' => 0),
				array('name' => 'total_views', 'type' => 'int', 'constraint' => 10, 'unsigned' => true, 'null' => false, 'default' => 0),
				array('name' => 'total_votes', 'type' => 'int', 'constraint' => 10, 'unsigned' => true, 'null' => false, 'default' => 0),
				array('name' => 'total_score', 'type' => 'int', 'constraint' => 10, 'unsigned' => true, 'null' => false, 'default' => 0),
				array('name' => 'total_rating', 'type' => 'double', 'constraint' => '3,2', 'unsigned' => true, 'null' => false, 'default' => 0),
				array('name' => 'total_likes', 'type' => 'int', 'constraint' => 10, 'unsigned' => true, 'null' => false, 'default' => 0),
				array('name' => 'total_comments', 'type' => 'int', 'constraint' => 8, 'unsigned' => true, 'null' => false, 'default' => 0),
				array('name' => 'votes', 'type' => 'tinyint', 'constraint' => 1, 'unsigned' => true, 'null' => false, 'default' => 1),
				array('name' => 'likes', 'type' => 'tinyint', 'constraint' => 1, 'unsigned' => true, 'null' => false, 'default' => 1),
				array('name' => 'comments', 'type' => 'tinyint', 'constraint' => 1, 'unsigned' => true, 'null' => false, 'default' => 1),
				array('name' => 'active', 'type' => 'tinyint', 'constraint' => 1, 'unsigned' => true, 'null' => false, 'default' => 1),
			),
			array('news_id'),
			array(),
			array(),
			false,
			$this->dbEngine
		);

		foreach ( config::item('languages', 'core', 'keywords') as $language )
		{
			$this->dbforge->addColumn(':prefix:news_data', array(
				'name' => 'data_title_' . $language,
				'type' => 'varchar',
				'constraint' => 255,
				'null' => true,
			));

			$this->dbforge->addColumn(':prefix:news_data', array(
				'name' => 'data_body_' . $language,
				'type' => 'text',
			));

			$this->dbforge->addColumn(':prefix:news_data', array(
				'name' => 'data_meta_keywords_' . $language,
				'type' => 'varchar',
				'constraint' => 255,
				'null' => true,
			));

			$this->dbforge->addColumn(':prefix:news_data', array(
				'name' => 'data_meta_description_' . $language,
				'type' => 'varchar',
				'constraint' => 255,
				'null' => true,
			));
		}

		$this->dbforge->dropTable(':prefix:news_data_items');
		$this->dbforge->createTable(':prefix:news_data_items',
			array(
				array('name' => 'data_id', 'type' => 'bigint', 'constraint' => 11, 'unsigned' => true, 'null' => false),
				array('name' => 'field_id', 'type' => 'int', 'constraint' => 6, 'unsigned' => true, 'null' => false),
				array('name' => 'item_id', 'type' => 'int', 'constraint' => 6, 'unsigned' => true, 'null' => false, 'default' => 0),
			),
			array(),
			array(),
			array('data_id', 'field_id', 'item_id'),
			false,
			$this->dbEngine
		);

		return true;
	}

	public function uninstall()
	{
		$this->dbforge->dropTable(':prefix:news_data');
		$this->dbforge->dropTable(':prefix:news_data_items');

		return true;
	}
}
