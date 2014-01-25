<?php

class Feedback_Feedback_Model extends Model
{
	public function sendFeedback($email, $subject, $message)
	{
		loader::library('email');

		$this->email->reply($email);

		$retval = $this->email->sendEmail(config::item('feedback_email', 'feedback'), $subject, $message);

		if ( $retval )
		{
			// Action hook
			hook::action('feedback/send/post', $email, $subject, $message);
		}

		return $retval;
	}
}
