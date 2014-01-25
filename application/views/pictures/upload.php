<? view::load('header'); ?>

<section class="plugin-pictures picture-upload">

	<?=view::load('system/elements/storage/upload', array(
		'action' => 'pictures/upload/'.$albumID,
		'keyword' => 'picture',
		'maxsize' => config::item('picture_max_size', 'pictures'),
		'extensions' => 'jpg,jpeg,png,gif',
		'limit' => $limit,
	))?>

</section>

<? view::load('footer');
