<? if ( $user['group_id'] != config::item('group_cancelled_id', 'users') && $user['verified'] && $user['active'] ): ?>
	<? if ( config::item('messages_active', 'messages') ): ?>
		<li><?=html_helper::anchor('messages/send/'.$user['slug_id'], __('message_send', 'users'))?></li>
	<? endif; ?>
<? endif; ?>

<?=hook::action('users/profile/browse/actions', $user);?>