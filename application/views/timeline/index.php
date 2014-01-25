<? view::load('header'); ?>

<section class="plugin-timeline timeline-index" id="timeline-container">

	<? if ( users_helper::isLoggedin() && $post ): ?>

		<div class="post">

			<? view::load('timeline/post', array('user' => $user)); ?>

		</div>

	<? endif; ?>

	<ul class="unstyled content-list <?=text_helper::alternate()?>">

		<? view::load('timeline/actions', array('actions' => $actions, 'user' => $user, 'ratings' => $ratings)); ?>

	</ul>

</section>

<? view::load('timeline/js'); ?>

<? view::load('footer');
