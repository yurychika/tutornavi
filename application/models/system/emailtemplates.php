<?php

class System_EmailTemplates_Model extends Model
{
	public function saveTemplate($templateID, $template)
	{
		$retval = $this->db->update('core_email_templates', $template, array('template_id' => $templateID), 1);

		if ( $retval )
		{
			$this->cache->cleanup();

			// Action hook
			hook::action('system/emailtemplates/update', $templateID, $template);
		}

		return $retval;
	}

	public function toggleStatus($templateID, $template)
	{
		$retval = $this->db->update('core_email_templates', array('active' => $template['active'] ? 0 : 1), array('template_id' => $templateID), 1);

		$this->cache->cleanup();

		return $retval;
	}

	public function getTemplate($templateID)
	{
		$template = $this->db->query("SELECT * FROM `:prefix:core_email_templates` WHERE `template_id`=? LIMIT 1", array($templateID))->row();

		return $template;
	}

	public function getTemplates($plugin)
	{
		$templates = array();

		$result = $this->db->query("SELECT * FROM `:prefix:core_email_templates` WHERE `plugin`=?", array($plugin))->result();

		foreach ( $result as $template )
		{
			$templates[$template['template_id']] = $template;
		}

		return $templates;
	}

	public function getPlugins($escape = true)
	{
		$plugins = array();

		$result = $this->db->query("SELECT `plugin` FROM `:prefix:core_email_templates` GROUP BY `plugin`")->result();

		foreach ( $result as $plugin )
		{
			$plugins[$plugin['plugin']] = $escape ? text_helper::entities(config::item('plugins', 'core', $plugin['plugin'], 'name')) : config::item('plugins', 'core', $plugin['plugin'], 'name');
		}

		asort($plugins);

		return $plugins;
	}

	public function prepareTemplate($keyword, $language)
	{
		$keyword = array('header', 'footer', $keyword);

		$templates = array();

		$result = $this->db->query("SELECT `keyword`, `subject_" . $language . "` as `subject`, `message_html_" . $language . "` as `message_html`, `message_text_" . $language . "` as `message_text`, `active`
			FROM `:prefix:core_email_templates`
			WHERE `keyword` IN ('" . implode("','", $keyword) . "') LIMIT 3")->result();

		foreach ( $result as $template )
		{
			$templates[$template['keyword']] = $template;
		}

		return $templates;
	}
}
