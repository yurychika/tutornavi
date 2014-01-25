<?php defined('SYSPATH') || die('No direct script access allowed.');

class News_Helper
{
	static public function getNews($params = array())
	{
		loader::model('news/news');

		$template = isset($params['template']) ? $params['template'] : 'news/helpers/news';

		$params['join_columns'][] = '`n`.`active`=1';

		$params['limit'] = isset($params['limit']) ? $params['limit'] : 10;
		$params['order'] = isset($params['order']) ? $params['order'] : '';

		$entries = codebreeder::instance()->news_model->getEntries('in_list', $params['join_columns'], array(), $params['order'], $params['limit'], $params);

		view::assign(array('entries' => $entries, 'params' => $params), '', $template);

		return view::load($template, array(), 1);
	}
}