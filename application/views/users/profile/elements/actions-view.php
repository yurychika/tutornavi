<? if ( users_helper::isLoggedin() ): ?>

	<? if ( $user['user_id'] == session::item('user_id') ): // is this our own profile? ?>

		<li>
			<?=html_helper::anchor('users/profile/edit', __('profile_edit', 'users'), array('class' => 'users-profile-edit'))?>
		</li>
		<li data-dropdown="menu-users-picture-edit" class="dropdown">
			<?=html_helper::anchor('users/profile/picture', '<span>'.__('picture_edit', 'users_picture').'</span>', array('class' => 'users-picture-edit'))?>
			<ul class="unstyled" style="display:none" data-dropdown-menu="menu-users-picture-edit">
				<li><?=html_helper::anchor('users/profile/picture', __('picture_change', 'users_picture'), array('data-role' => 'modal', 'data-title' => __('picture_change', 'users_picture'), 'class' => 'users-picture-change'))?></li>
				<? if ( session::item('picture_id') ): ?>
					<li><?=html_helper::anchor('users/profile/thumbnail', __('picture_thumbnail_edit', 'system_files'), array('class' => 'users-picture-thumbnail'))?></li>
					<li><?=html_helper::anchor('users/profile/picture/rotate/left', __('picture_rotate_left', 'system_files'), array('class' => 'users-picture-rotate-left'))?></li>
					<li><?=html_helper::anchor('users/profile/picture/rotate/right', __('picture_rotate_right', 'system_files'), array('class' => 'users-picture-rotate-right'))?></li>
					<li><?=html_helper::anchor('users/profile/picture/delete', __('picture_delete', 'users_picture'), array('data-role' => 'confirm', 'data-html' => __('picture_delete?', 'users_picture'), 'class' => 'users-picture-delete'))?></li>
				<? endif; ?>
			</ul>
		</li>

	<? else: // this is someone else's profile ?>

		<? if ( !config::item('blacklist_active', 'users') || !users_helper::getBlockedUser($user['user_id']) ): // is this user not blocked? ?>

			<? if ( config::item('messages_active', 'messages') ): ?>

				<li><?=html_helper::anchor('messages/send/'.$user['slug_id'], __('message_send', 'users'))?></li>

			<? endif; ?>

			<? if ( config::item('gifts_active', 'gifts') ): ?>

				<li><?=html_helper::anchor('gifts/send/'.$user['slug_id'], __('gift_send', 'users'))?></li>

			<? endif; ?>

			<? if ( config::item('friends_active', 'users') ): // is friends feature enabled? ?>

				<? if ( ( $friend = users_helper::getFriend($user['user_id'], false) ) ): // is there a friend request? ?>

					<? if ( !$friend['active'] ): // friend request is not yet approved ?>

						<? if ( $friend['user_id'] != session::item('user_id') ): ?>
							<li><?=html_helper::anchor('users/friends/confirm/'.$user['slug_id'], __('friend_confirm', 'users'))?></li>
						<? endif; ?>

						<li><?=html_helper::anchor('users/friends/delete/'.$user['slug_id'], __('friend_request_cancel', 'users'), array('data-html' => __('friend_request_cancel?', 'users'), 'data-role' => 'confirm'))?></li>

					<? endif; ?>

				<? else: // we are not friends ?>

					<li><?=html_helper::anchor('users/friends/add/'.$user['slug_id'], __('friend_add', 'users'), array('data-html' => __('friend_add?', 'users'), 'data-role' => 'confirm'))?></li>

				<? endif; ?>

			<? endif; ?>

		<? endif; ?>

	<? endif; ?>

<? endif; ?>

<?=hook::action('users/profile/view/actions', $user, ( isset($preview) && $preview ? true : false) )?>
