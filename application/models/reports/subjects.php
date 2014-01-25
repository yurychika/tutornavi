<?php

class Reports_Subjects_Model extends Model
{
	public function saveSubject($subjectID, $subject)
	{
		// Is this a new subject?
		if ( !$subjectID )
		{
			// Get last subject
			$lastSubject = $this->db->query("SELECT `order_id` FROM `:prefix:reports_subjects` ORDER BY `order_id` DESC LIMIT 1")->row();
			$subject['order_id'] = $lastSubject ? ( $lastSubject['order_id'] + 1 ) : 1;

			// Save subject
			$subjectID = $this->db->insert('reports_subjects', $subject);
		}
		else
		{
			// Save subject
			$this->db->update('reports_subjects', $subject, array('subject_id' => $subjectID), 1);
		}

		return $subjectID;
	}

	public function getSubject($subjectID, $escape = true)
	{
		// Get subject
		$subject = $this->db->query("SELECT * FROM `:prefix:reports_subjects` WHERE `subject_id`=? LIMIT 1", array($subjectID))->row();

		if ( $subject )
		{
			if ( $escape )
			{
				foreach ( config::item('languages', 'core', 'keywords') as $language )
				{
					$subject['name_' . $language] = text_helper::entities($subject['name_' . $language]);
				}
			}

			$subject['name'] = $subject['name_' . session::item('language')];
		}

		return $subject;
	}

	public function getSubjects($escape = true, $active = false)
	{
		// Get subjects
		$subjects = $this->db->query("SELECT * FROM `:prefix:reports_subjects` " . ( $active ? "WHERE `active`=1" : "" ) . " ORDER BY `order_id` ASC")->result();

		foreach ( $subjects as $index => $subject )
		{
			if ( $escape )
			{
				foreach ( config::item('languages', 'core', 'keywords') as $language )
				{
					$subject['name_' . $language] = text_helper::entities($subject['name_' . $language]);
				}
			}

			$subject['name'] = $subject['name_' . session::item('language')];

			$subjects[$index] = $subject;
		}

		return $subjects;
	}

	public function deleteSubject($subjectID, $subject)
	{
		// Delete subject
		$retval = $this->db->delete('reports_subjects', array('subject_id' => $subjectID), 1);

		if ( $retval )
		{
			// Update order IDs
			$this->db->query("UPDATE `:prefix:reports_subjects` SET `order_id`=`order_id`-1 WHERE `order_id`>?", array($subject['order_id']));
		}

		return $retval;
	}
}
