<?php

class Pictures_Timeline_Hook extends Hook
{
	public function postPicture($items, $users)
	{
		$stream = array();

		loader::model('pictures/pictures');
		loader::model('pictures/albums', array(), 'pictures_albums_model');

		$params = array(
			'select_users' => false,
		);

		// Get file IDs
		$files = array();
		foreach ( $items as $itemID => $data )
		{
			foreach ( $data as $actionID => $item )
			{
				foreach ( $item['attachments'] as $fileID )
				{
					$files[$fileID] = $actionID;
				}
			}
		}

		if ( !$files )
		{
			return array();
		}

		// Get albums
		$columns = array(
			'`a`.`album_id` IN (' . implode(',', array_keys($items)) . ')',
		);

		$albums = codebreeder::instance()->pictures_albums_model->getAlbums('in_list', $columns, array(), false, count($items), $params);

		// Get pictures
		$columns = array(
			'`p`.`file_id` IN (' . implode(',', array_keys($files)) . ')',
		);

		$pictures = codebreeder::instance()->pictures_model->getPictures('in_list', $columns, array(), false, count($files), $params);

		foreach ( $items as $itemID => $data )
		{
			if ( isset($albums[$itemID]) && isset($users[$albums[$itemID]['user_id']]) )
			{
				foreach ( $data as $actionID => $item )
				{
					foreach ( $pictures as $pictureID => $picture )
					{
						if ( isset($item['attachments'][$picture['file_id']]) )
						{
							$item['attachments'][$picture['file_id']] = $picture;
							unset($picture[$pictureID]);
						}
					}

					$stream[$itemID][$actionID]['html'] = view::load(
						'pictures/timeline/pictures',
						array('user' => $users[$albums[$itemID]['user_id']], 'album' => $albums[$itemID], 'pictures' => $item['attachments'], 'params' => $item['params']),
						true
					);

					$stream[$itemID][$actionID]['rating']['total_votes'] = $albums[$itemID]['total_votes'];
					$stream[$itemID][$actionID]['rating']['total_score'] = $albums[$itemID]['total_score'];
					$stream[$itemID][$actionID]['rating']['total_rating'] = $albums[$itemID]['total_rating'];
					$stream[$itemID][$actionID]['rating']['total_likes'] = $albums[$itemID]['total_likes'];
					$stream[$itemID][$actionID]['rating']['type'] = config::item('album_rating', 'pictures');
				}
			}
		}

		return $stream;
	}

	public function likePicture($notice)
	{
		$notice['html'] = __('picture_like', 'timeline_notices', array('%user' => users_helper::anchor($notice['user'])), array('%' => html_helper::anchor('pictures/view/' . $notice['item_id'], '\1')));

		return $notice;
	}

	public function votePicture($notice)
	{
		$notice['html'] = __('picture_vote', 'timeline_notices', array('%user' => users_helper::anchor($notice['user'])), array('%' => html_helper::anchor('pictures/view/' . $notice['item_id'], '\1')));

		return $notice;
	}

	public function commentPicture($notice)
	{
		$notice['html'] = __('picture_comment', 'timeline_notices', array('%user' => users_helper::anchor($notice['user'])), array('%' => html_helper::anchor('pictures/view/' . $notice['item_id'], '\1')));

		return $notice;
	}

	public function likeAlbum($notice)
	{
		$notice['html'] = __('picture_ablum_like', 'timeline_notices', array('%user' => users_helper::anchor($notice['user'])), array('%' => html_helper::anchor('pictures/index/' . $notice['item_id'], '\1')));

		return $notice;
	}

	public function voteAlbum($notice)
	{
		$notice['html'] = __('picture_album_vote', 'timeline_notices', array('%user' => users_helper::anchor($notice['user'])), array('%' => html_helper::anchor('pictures/index/' . $notice['item_id'], '\1')));

		return $notice;
	}
}
