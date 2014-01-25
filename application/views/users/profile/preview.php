<? view::load('header', array('message' => false)); ?>

<section class="plugin-users profile-view clearfix">

	<div class="sidebar">

		<figure class="image users-image">
			<? view::load('users/profile/elements/picture', array_merge($user, array('user_id' => 0, 'picture_file_suffix' => 'p'))); ?>
		</figure>

		<div class="content-box profile-actions">

			<ul class="unstyled content-actions">

				<?=view::load('users/profile/elements/actions-view', array('user' => $user, 'preview' => true));?>

			</ul>

		</div>

		<?=hook::action('users/profile/view-sidebar', array_merge($user, array('preview' => true)))?>

		<div class="content-box profile-sub-actions">

			<ul class="unstyled content-actions sub-actions">

				<?=view::load('users/profile/elements/subactions-view', array('user' => $user));?>

			</ul>

		</div>

	</div>
	<div class="content">

		<? view::load('message'); ?>

	</div>

</section>

<? view::load('footer');
