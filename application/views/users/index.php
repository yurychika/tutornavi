<? view::load('header'); ?>

<section class="plugin-users search-results">

	<? if ( $users ): ?>

		<ul class="unstyled content-list users-list <?=text_helper::alternate()?>">

			<? foreach ( $users as $user ): ?>

				<li class="<?=text_helper::alternate('odd','even')?>" id="row-users-<?=$user['user_id']?>">

					<article class="item item-actions">

						<figure class="image users-image">
							<? view::load('users/profile/elements/picture', array_merge($user, array('picture_file_suffix' => 'l'))); ?>
						</figure>

						<ul class="unstyled content-actions">
							<? view::load('users/profile/elements/actions-browse', array('user' => $user)); ?>
						</ul>

						<dl class="content-grid clearfix">
							<? view::load('users/profile/elements/profile', array('name' => true, 'fields' => $fields, 'user' => $user)); ?>
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
