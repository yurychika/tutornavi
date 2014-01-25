<?php

class Email extends CodeBreeder_Email
{
	public function __construct($config = array())
	{
		$config = array(
			'protocol' => config::item('email_protocol', 'system'),
			'hostname' => config::item('email_smtp_address', 'system'),
			'port' => config::item('email_smtp_port', 'system'),
			'secure' => config::item('email_secure', 'system'),
			'username' => config::item('email_smtp_username', 'system'),
			'password' => config::item('email_smtp_password', 'system'),
			'mailtype' => config::item('email_format', 'system'),
			'debug' => config::item('devmode', 'system') ? 1 : 0,
		);

		parent::__construct($config);

		$this->from(config::item('email_from_address', 'system'), config::item('email_from_name', 'system'));
	}

	public function sendEmail($email, $subject, $messageText, $messageHTML = '', $tags = array())
	{
		foreach ( $tags as $tag => $value )
		{
			if ( is_array($value) )
			{
				$value = implode(', ', $value);
			}
			$subject = utf8::str_replace('[' . $tag . ']', $value, $subject);
			$messageHTML = utf8::str_replace('[' . $tag . ']', $value, $messageHTML);
			$messageText = utf8::str_replace('[' . $tag . ']', $value, $messageText);
		}

		$this->to($email);
		$this->subject($subject);
		$this->message($messageText, $messageHTML);

		return $this->send();
	}

	public function sendTemplate($keyword, $email, $tags = array(), $language = '')
	{
		loader::model('system/emailtemplates');

		if ( !$language )
		{
			$language = config::item('language_id', 'system');
		}

		if ( is_numeric($language) )
		{
			$language = config::item('languages', 'core', 'keywords', $language);
		}
		elseif ( !in_array($language, config::item('languages', 'core', 'keywords')) )
		{
			return false;
		}

		if ( !( $template = config::item($keyword . '_' . $language, '_system_emails_cache') ) )
		{
			if ( !( $template = $this->cache->item('core_email_template_' . $keyword . '_' . $language) ) )
			{
				$template = $this->emailtemplates_model->prepareTemplate($keyword, $language);

				if ( count($template) == 3 )
				{
					if ( $template[$keyword]['active'] )
					{
						$template = array(
							'subject' => $template[$keyword]['subject'],
							'message_html' => utf8::trim($template['header']['message_html'] . $template[$keyword]['message_html'] . $template['footer']['message_html']),
							'message_text' => utf8::trim($template['header']['message_text'] . "\n\n" . $template[$keyword]['message_text'] . "\n\n" . $template['footer']['message_text']),
						);
					}
					else
					{
						$template = 'none';
					}
				}
				else
				{
					error::show('Could not fetch email template from the database: '. $keyword);
				}

				$this->cache->set('core_email_template_' . $keyword . '_' . $language, $template, 60*60*24*30);
			}

			config::set(array($keyword . '_' . $language => $template), '', '_system_emails_cache');
		}

		$retval = true;
		if ( is_array($template) && $template )
		{
			$retval = $this->sendEmail($email, $template['subject'], $template['message_text'], $template['message_html'], $tags);
		}

		return $retval;
	}
}
