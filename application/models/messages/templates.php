<?php

class Messages_Templates_Model extends Model
{
	public function saveTemplate($templateID, $template)
	{
		// Is this a new template?
		if ( !$templateID )
		{
			// Get last template
			$lastTemplate = $this->db->query("SELECT `order_id` FROM `:prefix:messages_templates` ORDER BY `order_id` DESC LIMIT 1")->row();
			$template['order_id'] = $lastTemplate ? ( $lastTemplate['order_id'] + 1 ) : 1;

			// Save template
			$templateID = $this->db->insert('messages_templates', $template);
		}
		else
		{
			// Save template
			$this->db->update('messages_templates', $template, array('template_id' => $templateID), 1);
		}

		return $templateID;
	}

	public function getTemplate($templateID, $escape = true)
	{
		// Get template
		$template = $this->db->query("SELECT * FROM `:prefix:messages_templates` WHERE `template_id`=? LIMIT 1", array($templateID))->row();

		if ( $template )
		{
			if ( $escape )
			{
				foreach ( config::item('languages', 'core', 'keywords') as $language )
				{
					$template['name_' . $language] = text_helper::entities($template['name_' . $language]);
					$template['subject_' . $language] = text_helper::entities($template['subject_' . $language]);
					$template['message_' . $language] = text_helper::entities($template['message_' . $language]);
				}
			}

			$template['name'] = $template['name_' . session::item('language')];
			$template['subject'] = $template['subject_' . session::item('language')];
			$template['message'] = $template['message_' . session::item('language')];
		}

		return $template;
	}

	public function getTemplates($escape = true, $status = false)
	{
		// Get templates
		$templates = $this->db->query("SELECT * FROM `:prefix:messages_templates` " . ( $status ? "WHERE `active`=1" : "" ) . " ORDER BY `order_id` ASC")->result();

		foreach ( $templates as $index => $template )
		{
			if ( $escape )
			{
				foreach ( config::item('languages', 'core', 'keywords') as $language )
				{
					$template['name_' . $language] = text_helper::entities($template['name_' . $language]);
					$template['subject_' . $language] = text_helper::entities($template['subject_' . $language]);
					$template['message_' . $language] = text_helper::entities($template['message_' . $language]);
				}
			}

			$template['name'] = $template['name_' . session::item('language')];
			$template['subject'] = $template['subject_' . session::item('language')];
			$template['message'] = $template['message_' . session::item('language')];

			$templates[$index] = $template;
		}

		return $templates;
	}

	public function deleteTemplate($templateID, $template)
	{
		// Delete template
		$retval = $this->db->delete('messages_templates', array('template_id' => $templateID), 1);

		if ( $retval )
		{
			// Update order IDs
			$this->db->query("UPDATE `:prefix:messages_templates` SET `order_id`=`order_id`-1 WHERE `order_id`>?", array($template['order_id']));
		}

		return $retval;
	}
}
