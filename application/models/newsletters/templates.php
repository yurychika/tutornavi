<?php

class Newsletters_Templates_Model extends Model
{
	public function saveTemplate($templateID, $template)
	{
		// Is this a new template?
		if ( !$templateID )
		{
			// Save template
			$templateID = $this->db->insert('newsletters_templates', $template);
		}
		else
		{
			// Save template
			$this->db->update('newsletters_templates', $template, array('template_id' => $templateID), 1);
		}

		return $templateID;
	}

	public function getTemplate($templateID, $escape = true)
	{
		// Get template
		$template = $this->db->query("SELECT * FROM `:prefix:newsletters_templates` WHERE `template_id`=? LIMIT 1", array($templateID))->row();

		if ( $template && $escape )
		{
			$template['name'] = text_helper::entities($template['name']);
			$template['subject'] = text_helper::entities($template['subject']);
			$template['message_html'] = text_helper::entities($template['message_html']);
			$template['message_text'] = text_helper::entities($template['message_text']);
		}

		return $template;
	}

	public function getTemplates($escape = true)
	{
		// Get templates
		$templates = $this->db->query("SELECT * FROM `:prefix:newsletters_templates` ORDER BY `name` ASC")->result();

		if ( $escape )
		{
			foreach ( $templates as $index => $template )
			{
				$template['name'] = text_helper::entities($template['name']);
				$template['subject'] = text_helper::entities($template['subject']);
				$template['message_html'] = text_helper::entities($template['message_html']);
				$template['message_text'] = text_helper::entities($template['message_text']);

				$templates[$index] = $template;
			}
		}

		return $templates;
	}

	public function deleteTemplate($templateID, $template)
	{
		// Delete template
		$retval = $this->db->delete('newsletters_templates', array('template_id' => $templateID), 1);

		return $retval;
	}
}
