<? view::load('header'); ?>

<section class="plugin-users profile-view clearfix">

	<div class="sidebar">

		<figure class="image users-image">
			<? view::load('users/profile/elements/picture', array_merge($user, array('picture_url' => true, 'picture_file_suffix' => 'p'))); ?>
		</figure>

		<div class="content-box profile-actions">

			<ul class="unstyled content-actions">

				<?=view::load('users/profile/elements/actions-view', array('user' => $user));?>

			</ul>

		</div>

		<?=hook::action('users/profile/view/sidebar', $user)?>

		<div class="content-box profile-sub-actions">

			<ul class="unstyled content-actions sub-actions">

				<?=view::load('users/profile/elements/subactions-view', array('user' => $user));?>

			</ul>

		</div>

	</div>
	<div class="content">

		<dl class="content-grid">
			<? view::load('users/profile/elements/profile', array('name' => false, 'fields' => $fields, 'user' => $user, 'overview' => config::item('user_profile_full', 'users') ? 0 : 1)); ?>
		</dl>

		<? if ( config::item('timeline_active', 'timeline') && config::item('timeline_profile', 'timeline') ): ?>
			<?=timeline_helper::getTimeline($user, ( isset($user['config']['privacy_timeline_messages']) ? $user['config']['privacy_timeline_messages'] : 1 ))?>
		<? endif; ?>

	</div>

</section>

<? view::load('footer');
