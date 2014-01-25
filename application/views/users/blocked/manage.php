<? view::load('header'); ?>

<section class="plugin-users blocked-manage">

	<? if ( $users ): ?>

		<ul class="unstyled content-list users-list <?=text_helper::alternate()?>">

			<? foreach ( $users as $user ): ?>

				<li class="<?=text_helper::alternate('odd','even')?>" id="row-user-<?=$user['user_id']?>">

					<article class="item item-actions">

						<figure class="image users-image">
							<? view::load('users/profile/elements/picture', array_merge($user, array('user_id' => 0, 'picture_file_suffix' => 'l'))); ?>
						</figure>

						<ul class="unstyled content-actions">
							<li><?=html_helper::anchor('users/blocked/delete/'.$user['username'].'?'.$qstring['url'].'page='.$qstring['page'], __('user_unblock', 'users'), array('data-html' => __('user_unblock?', 'users'), 'data-role' => 'confirm'))?></li>
						</ul>

						<dl class="content-grid clearfix">
							<? view::load('users/profile/elements/profile', array('name' => true, 'fields' => $fields[$user['type_id']], 'user' => $user)); ?>
						</dl>

					</article>

				</li>

			<? endforeach; ?>

		</ul>

		<div class="content-footer">
			<? view::load('system/elements/pagination', array('pagination' => $pagination)); ?>
		</div>

	<? endif; ?>

</section>

<? view::load('footer');
