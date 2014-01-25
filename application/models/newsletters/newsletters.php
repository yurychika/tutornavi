<?php

class Newsletters_Newsletters_Model extends Model
{
	public function saveNewsletter($newsletterID, $newsletter)
	{
		if ( isset($newsletter['params']) && is_array($newsletter['params']) )
		{
			$newsletter['params'] = json_encode($newsletter['params']);
		}

		// Is this a new newsletter?
		if ( !$newsletterID )
		{
			// Save newsletter
			$newsletterID = $this->db->insert('newsletters', $newsletter);
		}
		else
		{
			// Save newsletter
			$this->db->update('newsletters', $newsletter, array('newsletter_id' => $newsletterID), 1);
		}

		return $newsletterID;
	}

	public function getNewsletter($newsletterID, $escape = true)
	{
		// Get newsletter
		$newsletter = $this->db->query("SELECT * FROM `:prefix:newsletters` WHERE `newsletter_id`=? LIMIT 1", array($newsletterID))->row();

		if ( $newsletter )
		{
			if ( $newsletter['params'] )
			{
				$newsletter['params'] = @json_decode($newsletter['params'], true);
			}

			if ( $escape )
			{
				$newsletter['subject'] = text_helper::entities($newsletter['subject']);
				$newsletter['message_html'] = text_helper::entities($newsletter['message_html']);
				$newsletter['message_text'] = text_helper::entities($newsletter['message_text']);
			}
		}

		return $newsletter;
	}

	public function getNewsletters($escape = true)
	{
		// Get newsletters
		$newsletters = $this->db->query("SELECT * FROM `:prefix:newsletters` ORDER BY `subject` ASC")->result();

		foreach ( $newsletters as $index => $newsletter )
		{
			if ( $escape )
			{
				$newsletter['subject'] = text_helper::entities($newsletter['subject']);
				$newsletter['message_html'] = text_helper::entities($newsletter['message_html']);
				$newsletter['message_text'] = text_helper::entities($newsletter['message_text']);
			}

			$newsletters[$index] = $newsletter;
		}

		return $newsletters;
	}

	public function deleteNewsletter($newsletterID, $newsletter)
	{
		// Delete newsletter
		$retval = $this->db->delete('newsletters', array('newsletter_id' => $newsletterID), 1);

		return $retval;
	}
}
