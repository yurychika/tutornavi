<?php defined('SYSPATH') || die('No direct script access allowed.');

class Classifieds_Helper
{
	static public function getAds($params = array())
	{
		loader::model('classifieds/classifieds');

		$template = isset($params['template']) ? $params['template'] : 'classifieds/helpers/classifieds';
		$user = isset($params['user']) && $params['user'] ? $params['user'] : array();
		$userID = $user ? $user['user_id'] : ( isset($params['user_id']) ? $params['user_id'] : 0 );

		if ( $userID )
		{
			$params['join_columns'][] = '`a`.`user_id`=' . $userID;
		}

		if ( !$userID || $userID != session::item('user_id') )
		{
			if ( $userID )
			{
				$params['join_columns'][] = '`a`.`post_date`>' . ( date_helper::now() - config::item('ad_expiration', 'classifieds')*60*60*24 );
			}
			else
			{
				$params['join_columns'][] = '`u`.`active`=1';
			}
		}

		$params['limit'] = isset($params['limit']) ? $params['limit'] : 10;
		$params['order'] = isset($params['order']) ? $params['order'] : '';

		$ads = codebreeder::instance()->classifieds_model->getAds('in_list', $params['join_columns'], array(), $params['order'], $params['limit'], $params);

		view::assign(array('ads' => $ads, 'user' => $user, 'params' => $params), '', $template);

		return view::load($template, array(), 1);
	}
}