<? view::load('header'); ?>

<section class="plugin-messages message-people">

	<? if ( $participants ): ?>

		<ul class="unstyled content-list users-list <?=text_helper::alternate()?>">

			<? foreach ( $participants as $user ): ?>

				<li class="<?=text_helper::alternate('odd','even')?>" id="row-users-<?=$user['user_id']?>">

					<article class="item">

						<figure class="image users-image">
							<? view::load('users/profile/elements/picture', array_merge($user, array('picture_file_suffix' => 'l'))); ?>
						</figure>

						<dl class="content-grid clearfix">
							<? view::load('users/profile/elements/profile', array('name' => true, 'fields' => $fields[$user['type_id']], 'user' => $user)); ?>
						</dl>

					</article>

				</li>

			<? endforeach; ?>

		</ul>

	<? endif; ?>

</section>

<? view::load('footer');
