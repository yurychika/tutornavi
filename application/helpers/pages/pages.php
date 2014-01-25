<?php defined('SYSPATH') || die('No direct script access allowed.');

class Pages_Helper
{
	static public function getPage($params = array())
	{
		if ( !isset($params['location']) )
		{
			return '';
		}

		loader::model('pages/pages');

		$template = isset($params['template']) ? $params['template'] : 'pages/helpers/page';

		$params['title'] = isset($params['title']) ? $params['title'] : true;

		$page = codebreeder::instance()->pages_model->getPage($params['location'], 'in_view', $params);

		if ( isset($params['print']) && $params['print'] )
		{
			return $page ? $page['data_body'] : '';
		}

		view::assign(array('page' => $page, 'title' => $params['title'], 'params' => $params), '', $template);

		return view::load($template, array(), 1);
	}
}