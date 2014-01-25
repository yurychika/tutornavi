<? view::load('header'); ?>

<section class="plugin-users profile-picture-thumbnail">

	<div class="content-view">

		<article class="item clearfix">

			<figure class="image users-image source">
				<img src="<?=storage_helper::getFileURL(session::item('picture_file_service_id'), session::item('picture_file_path'), session::item('picture_file_name'), session::item('picture_file_ext'))?>" style="max-width:400px;max-height:400px;" />
			</figure>

			<figure class="image users-image preview">
				<img src="<?=storage_helper::getFileURL(session::item('picture_file_service_id'), session::item('picture_file_path'), session::item('picture_file_name'), session::item('picture_file_ext'))?>" />
			</figure>

		</article>

		<?=form_helper::openForm()?>

			<fieldset class="form">

				<div class="row actions">
					<? view::load('system/elements/button'); ?>
					&nbsp;
					<?=html_helper::anchor(session::item('slug'), __('cancel', 'system'))?>
				</div>

			</fieldset>

			<?=form_helper::hidden('picture_thumb_x', 0, array('id' => 'picture_thumb_x'))?>
			<?=form_helper::hidden('picture_thumb_y', 0, array('id' => 'picture_thumb_y'))?>
			<?=form_helper::hidden('picture_thumb_w', config::item('picture_dimensions_l_width', 'users'), array('id' => 'picture_thumb_w'))?>
			<?=form_helper::hidden('picture_thumb_h', config::item('picture_dimensions_l_height', 'users'), array('id' => 'picture_thumb_h'))?>

		<?=form_helper::closeForm(array('do_save_thumbnail' => 1))?>

	</div>

</section>

<script type="text/javascript">
function createCropArea()
{
	var jcrop_api;
	var bounds, boundx, boundy;

	$('.profile-picture-thumbnail figure.source img').Jcrop({
		onChange: showPreview,
		onSelect: showPreview,
		trueSize: [<?=session::item('picture_file_width')?>,<?=session::item('picture_file_height')?>],
		minSize: [<?=config::item('picture_dimensions_p_width', 'users')?>,<?=config::item('picture_dimensions_p_height', 'users')?>],
		setSelect: [0, 0, <?=config::item('picture_dimensions_p_width', 'users')?>,<?=config::item('picture_dimensions_p_height', 'users')?>],
		aspectRatio: <?=number_format(config::item('picture_dimensions_p_width', 'users')/config::item('picture_dimensions_p_height', 'users'), 2)?>,
		keySupport: false,
		boxWidth: 400,
		boxHeight: 400
	},
	function(){
		jcrop_api = this;
		bounds = jcrop_api.getBounds();
		boundx = bounds[0];
		boundy = bounds[1];
	});

	function showPreview(coords)
	{
		if ( parseInt(coords.w) > 0 )
		{
			var rx = <?=config::item('picture_dimensions_p_width', 'users')?> / coords.w;
			var ry = <?=config::item('picture_dimensions_p_height', 'users')?> / coords.h;

			$('.profile-picture-thumbnail figure.preview img').css({
				width: Math.round(rx * boundx) + 'px',
				height: Math.round(ry * boundy) + 'px',
				marginLeft: '-' + Math.round(rx * coords.x) + 'px',
				marginTop: '-' + Math.round(ry * coords.y) + 'px'
			});

			$('#picture_thumb_x').val(coords.x);
			$('#picture_thumb_y').val(coords.y);
			$('#picture_thumb_w').val(coords.w);
			$('#picture_thumb_h').val(coords.h);
		}
	};
}
$(function(){
	head(function(){
		setTimeout(createCropArea, 250);
	});
});
</script>
<? view::load('footer');
