<?php

class Users_Timeline_Hook extends Hook
{
	public function signupUser($items, $users)
	{
		$stream = array();

		foreach ( $items as $itemID => $data )
		{
			if ( isset($users[$itemID]) )
			{
				foreach ( $data as $actionID => $item )
				{
					$stream[$itemID][$actionID]['html'] = view::load(
						'users/timeline/signup',
						array('user' => $users[$itemID]),
						true
					);
				}
			}
		}

		return $stream;
	}

	public function changeUserPicture($items, $users)
	{
		$stream = array();

		foreach ( $items as $itemID => $data )
		{
			if ( isset($users[$itemID]) )
			{
				foreach ( $data as $actionID => $item )
				{
					$stream[$itemID][$actionID]['html'] = view::load(
						'users/timeline/picture',
						array('user' => $users[$itemID]),
						true
					);
				}
			}
		}

		return $stream;
	}
}
