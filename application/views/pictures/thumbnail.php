<? view::load('header'); ?>

<section class="plugin-pictures picture-thumbnail">

	<div class="content-view">

		<article class="item clearfix">

			<figure class="image users-image source">
				<img src="<?=storage_helper::getFileURL($picture['file_service_id'], $picture['file_path'], $picture['file_name'], $picture['file_ext'])?>" style="max-width:400px;max-height:400px;" />
			</figure>

			<figure class="image users-image preview">
				<img src="<?=storage_helper::getFileURL($picture['file_service_id'], $picture['file_path'], $picture['file_name'], $picture['file_ext'])?>" />
			</figure>

		</article>

		<?=form_helper::openForm()?>

			<fieldset class="form">

				<div class="row actions">
					<? view::load('system/elements/button'); ?>
					&nbsp;
					<?=html_helper::anchor('pictures/view/'.$picture['picture_id'].'/'.text_helper::slug($picture['data_description'], 100), __('cancel', 'system'))?>
				</div>

			</fieldset>

			<?=form_helper::hidden('picture_thumb_x', 0, array('id' => 'picture_thumb_x'))?>
			<?=form_helper::hidden('picture_thumb_y', 0, array('id' => 'picture_thumb_y'))?>
			<?=form_helper::hidden('picture_thumb_w', config::item('picture_dimensions_t_width', 'pictures'), array('id' => 'picture_thumb_w'))?>
			<?=form_helper::hidden('picture_thumb_h', config::item('picture_dimensions_t_height', 'pictures'), array('id' => 'picture_thumb_h'))?>

		<?=form_helper::closeForm(array('do_save_thumbnail' => 1))?>

	</div>

</section>

<script type="text/javascript">
function createCropArea()
{
	var jcrop_api;
	var bounds, boundx, boundy;

	$('.picture-thumbnail figure.source img').Jcrop({
		onChange: showPreview,
		onSelect: showPreview,
		trueSize: [<?=$picture['file_width']?>,<?=$picture['file_height']?>],
		minSize: [<?=config::item('picture_dimensions_t_width', 'pictures')?>,<?=config::item('picture_dimensions_t_height', 'pictures')?>],
		setSelect: [0, 0, <?=config::item('picture_dimensions_t_width', 'pictures')?>,<?=config::item('picture_dimensions_t_height', 'pictures')?>],
		aspectRatio: <?=number_format(config::item('picture_dimensions_t_width', 'pictures')/config::item('picture_dimensions_t_height', 'pictures'), 2)?>,
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
			var rx = <?=config::item('picture_dimensions_t_width', 'pictures')?> / coords.w;
			var ry = <?=config::item('picture_dimensions_t_height', 'pictures')?> / coords.h;

			$('.picture-thumbnail figure.preview img').css({
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
