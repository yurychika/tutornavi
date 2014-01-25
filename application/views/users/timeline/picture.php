<div class="item-article">

	<div class="action-header">
		<?=__('user_picture', 'timeline', array('[name]' => users_helper::anchor($user)))?>
	</div>

	<div class="target-article media clearfix">
		<figure class="image users-image">
			<? view::load('users/profile/elements/picture', array_merge($user, array('picture_file_suffix' => 'l'))); ?>
		</figure>
	</div>

</div>
