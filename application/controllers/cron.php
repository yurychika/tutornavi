<?php

class Cron_Controller extends Controller
{
	public function __construct()
	{
		parent::__construct();

		loader::model('system/cron');
	}

	public function index()
	{
		$this->run();
	}

	public function run()
	{
		$shash = uri::segment(3);

		// Verify security string
		if ( !$shash || strcmp($shash, config::item('cron_shash', 'system')) !== 0 )
		{
			error::show('Invalid security string.');
		}

		if ( strcmp(config::item('cron_last_run', 'system'), date('Ymd', date_helper::now())) === 0 )
		{
			error::show('You may run this file only once per day.');
		}

		// Action hook
		hook::action('cron/run');

		echo "Performed tasks:<br/>";
		echo implode(" <br/>\n", $this->cron_model->getLog());

		$this->cron_model->finalize();

		exit;
	}
}
