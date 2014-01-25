<? view::load('header'); ?>

<section class="plugin-classifieds picture-upload">

	<?=view::load('system/elements/storage/upload', array(
		'action' => 'classifieds/pictures/upload/'.$adID,
		'keyword' => 'classified_picture',
		'maxsize' => config::item('picture_max_size', 'classifieds'),
		'extensions' => 'jpg,jpeg,png,gif',
		'limit' => $limit,
	))?>

</section>

<? view::load('footer');
