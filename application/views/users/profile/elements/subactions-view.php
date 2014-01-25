<li class="break-box break"></li>

<? if ( users_helper::isLoggedin() && $user['user_id'] != session::item('user_id') ): ?>

	<? if ( config::item('friends_active', 'users') ): // is friends feature enabled? ?>

		<? if ( ( $friend = users_helper::getFriend($user['user_id'], false) ) ): // is there a friend request? ?>

			<? if ( $friend['active'] ): // we are friends ?>

				<li  class="users_friends_delete"><?=html_helper::anchor('users/friends/delete/'.$user['slug_id'], __('friend_delete', 'users'), array('data-html' => __('friend_delete?', 'users'), 'data-role' => 'confirm'))?></li>

			<? endif; ?>

		<? endif; ?>

	<? endif; ?>

	<? if ( config::item('blacklist_active', 'users') ): // is user block feature enabled? ?>

		<? if ( users_helper::getBlockedUser($user['user_id'], true) ): // is this user blocked? ?>

			<li class="users_block"><?=html_helper::anchor('users/blocked/delete/'.$user['slug_id'], __('user_unblock', 'users'), array('data-html' => __('user_unblock?', 'users'), 'data-role' => 'confirm'))?></li>

		<? else: // this user is not blocked ?>

			<li class="users_block"><?=html_helper::anchor('users/blocked/add/'.$user['slug_id'], __('user_block', 'users'), array('data-html' => __('user_block?', 'users'), 'data-role' => 'confirm'))?></li>

		<? endif; ?>

	<? endif; ?>

	<? if ( config::item('reports_active', 'reports') && session::permission('reports_post', 'reports') ): ?>

		<li class="report">
			<?=html_helper::anchor('report/submit/user/'.$user['user_id'], __('report', 'system'), array('data-role' => 'modal', 'data-display' => 'iframe', 'data-title' => __('report', 'system')))?>
		</li>

	<? endif; ?>

<? endif; ?>

<?=hook::action('users/profile/view/subactions', $user, ( isset($preview) && $preview ? true : false) )?>
