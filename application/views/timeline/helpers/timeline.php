<div class="content-box helper-timeline">

	<section class="plugin-timeline timeline-index" id="timeline-container">

		<? if ( $user ): ?>
			<div class="header">
				<? /*<span><?=html_helper::anchor('timeline/user/'.$user['slug_id'], __('timeline_recent', 'system_navigation'))?></span> */ ?>
				<span><?=__('timeline_recent', 'system_navigation')?></span>
			</div>
		<? endif; ?>

		<? if ( users_helper::isLoggedin() && $post ): ?>

			<div class="post">

				<? view::load('timeline/post', array('user' => $user)); ?>

			</div>

		<? endif; ?>

		<ul class="unstyled content-list <?=text_helper::alternate()?>">

			<? view::load('timeline/actions', array('actions' => $actions, 'user' => $user, 'ratings' => $ratings)); ?>

		</ul>

	</section>

</div>

<? view::load('timeline/js'); ?>
