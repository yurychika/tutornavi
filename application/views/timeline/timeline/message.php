<div class="item-article">

	<div class="action-header">
		<?=__('message_post'.($user['user_id'] == $poster['user_id'] ? '_self' : ''), 'timeline', array('[poster.name]' => users_helper::anchor($poster), '[name]' => users_helper::anchor($user)))?>
	</div>

	<div class="target-article">
		<?=$message['message']?>
	</div>

</div>
