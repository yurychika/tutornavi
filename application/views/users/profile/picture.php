<? view::load('header'); ?>

<section class="plugin-users profile-picture">

	<?=view::load('system/elements/storage/upload', array(
		'action' => 'users/profile/picture',
		'keyword' => 'picture',
		'maxsize' => config::item('picture_max_size', 'users'),
		'extensions' => 'jpg,jpeg,png,gif',
		'limit' => 1,
	))?>

</section>

<? view::load('footer');
