<?php defined('SYSPATH') || die('No direct script access allowed.');

class Pictures_Helper
{
	static public function getAlbums($params = array())
	{
		if ( !session::permission('users_groups_browse', 'users') && !session::permission('users_types_browse', 'users') )
		{
			return '';
		}

		loader::model('pictures/albums', array(), 'pictures_albums_model');

		$template = isset($params['template']) ? $params['template'] : 'pictures/helpers/albums';
		$user = isset($params['user']) && $params['user'] ? $params['user'] : array();
		$userID = $user ? $user['user_id'] : ( isset($params['user_id']) ? $params['user_id'] : 0 );

		if ( $userID )
		{
			$params['join_columns'][] = '`a`.`user_id`=' . $userID;
		}

		if ( !$userID || $userID != session::item('user_id') )
		{
			$params['join_columns'][] = '`a`.`total_pictures`>0';

			if ( $userID )
			{
				$params['privacy'] = $userID;
			}
			else
			{
				$params['join_columns'][] = '`a`.`public`=1';
				$params['join_columns'][] = '`u`.`verified`=1';
				$params['join_columns'][] = '`u`.`active`=1';
				$params['join_columns'][] = '`u`.`group_id` IN (' . implode(',', session::permission('users_groups_browse', 'users')) . ')';
				$params['join_columns'][] = '`u`.`type_id` IN (' . implode(',', session::permission('users_types_browse', 'users')) . ')';
			}
		}

		$params['limit'] = isset($params['limit']) ? $params['limit'] : 10;
		$params['order'] = isset($params['order']) ? $params['order'] : '';

		$albums = codebreeder::instance()->pictures_albums_model->getAlbums('in_list', $params['join_columns'], array(), $params['order'], $params['limit'], $params);

		view::assign(array('albums' => $albums, 'user' => $user, 'params' => $params), '', $template);

		return view::load($template, array(), 1);
	}

	static public function getPictures($params = array())
	{
		if ( !session::permission('users_groups_browse', 'users') && !session::permission('users_types_browse', 'users') )
		{
			return '';
		}

		loader::model('pictures/pictures');

		$template = isset($params['template']) ? $params['template'] : 'pictures/helpers/pictures';
		$user = isset($params['user']) && $params['user'] ? $params['user'] : array();
		$userID = $user ? $user['user_id'] : ( isset($params['user_id']) ? $params['user_id'] : 0 );

		$params['albums'] = true;

		if ( $userID )
		{
			$params['join_columns'][] = '`p`.`user_id`=' . $userID;
		}

		if ( !$userID || $userID != session::item('user_id') )
		{
			if ( $userID )
			{
				$params['privacy'] = $userID;
			}
			else
			{
				$params['join_columns'][] = '`a`.`public`=1';
				$params['join_columns'][] = '`u`.`verified`=1';
				$params['join_columns'][] = '`u`.`active`=1';
				$params['join_columns'][] = '`u`.`group_id` IN (' . implode(',', session::permission('users_groups_browse', 'users')) . ')';
				$params['join_columns'][] = '`u`.`type_id` IN (' . implode(',', session::permission('users_types_browse', 'users')) . ')';
			}
		}

		$params['limit'] = isset($params['limit']) ? $params['limit'] : 10;
		$params['order'] = isset($params['order']) ? $params['order'] : '';

		$pictures = codebreeder::instance()->pictures_model->getPictures('in_list', $params['join_columns'], array(), $params['order'], $params['limit'], $params);

		view::assign(array('pictures' => $pictures, 'user' => $user, 'params' => $params), '', $template);

		return view::load($template, array(), 1);
	}
}