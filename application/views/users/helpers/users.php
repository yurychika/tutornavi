<? if ( $users ): ?>

	<div class="content-box helper-users">

		<? if ( isset($user) && $user ): ?>
			<div class="header clearfix">
				<span><?=html_helper::anchor('users/friends/index/'.text_helper::entities($user['username']), __('users_friends', 'system_navigation'))?></span>
				<div class="header">
					<span><?=html_helper::anchor('users/friends/index/'.text_helper::entities($user['username']), __('users_friends_num'.($user['total_friends'] == 1 ? '_one' : ''), 'system_info', array('%friends' => $user['total_friends'])))?></span>
				</div>
			</div>
		<? endif; ?>

		<ul class="unstyled content-gallery clearfix <?=text_helper::alternate()?>">

			<? foreach ( $users as $user ): ?>

				<li class="<?=text_helper::alternate('one','two','three')?>" id="row-helper-<?=$user['user_id']?>">

					<figure class="image users-image">
						<? view::load('users/profile/elements/picture', array_merge($user, array('picture_file_suffix' => 'l'))); ?>

						<figcaption class="image-caption">
							<span class="nowrap nooverflow"><? $user['name'] = utf8::str_replace(' ', '<br/>', $user['name']); ?><?=users_helper::anchor($user, array('title' => $user['name']))?></span>
						</figcaption>

					</figure>

				</li>

			<? endforeach; ?>

		</ul>

	</div>

<? endif; ?>
