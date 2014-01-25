<? view::load('cp/system/elements/template/header'); ?>

<section class="plugin-system fields-browse">

	<? if ( $fields ): ?>

		<div id="browse_box">
			<? view::load('cp/system/fields/browse_grid', array('grid' => $grid)); ?>
		</div>

	<? endif; ?>

</section>

<script type="text/javascript">
	function switchSortable()
	{
		startAjax('actions_link_reorder', 'icon-system-ajax');

		$.post('<?=html_helper::siteURL('cp/system/fields/' . $plugin . '/browse/' . $categoryID)?>', {'view':'list'},
			function(response)
			{
				stopAjax('actions_link_reorder', 'icon-system-ajax');

				$('#actions li').hide();
				$('#actions_link_save').parent().show();
				$('#actions_link_cancel').parent().show();

				$('#browse_box').html(response);

				$('#plans_sortable').sortable({handle: 'span.handle'});
			}
		);
	}
	function saveSortable()
	{
		startAjax('actions_link_save', 'icon-system-ajax');

		var fieldIDs = [];
		$('.field_ids').each(function(index, object)
		{
			if ( fieldIDs.indexOf(object.value) == -1 )
			{
				fieldIDs.push(object.value);
			}
		});

		$.post('<?=html_helper::siteURL('cp/system/fields/' . $plugin . '/browse/' . $categoryID)?>', {'view':'grid','action':'reorder','ids':fieldIDs},
			function(response)
			{
				stopAjax('actions_link_save', 'icon-system-ajax');

				$('#actions li').show();
				$('#actions_link_save').parent().hide();
				$('#actions_link_cancel').parent().hide();

				$('#browse_box').html(response);
			}
		);
	}
	function cancelSortable()
	{
		startAjax('actions_link_cancel', 'icon-system-ajax');

		$.post('<?=html_helper::siteURL('cp/system/fields/' . $plugin . '/browse/' . $categoryID)?>', {'view':'grid'},
			function(response)
			{
				stopAjax('actions_link_cancel', 'icon-system-ajax');

				$('#actions li').show();
				$('#actions_link_save').parent().hide();
				$('#actions_link_cancel').parent().hide();

				$('#browse_box').html(response);
			}
		);
	}
	$(function(){
		$('#actions_link_save').parent().hide();
		$('#actions_link_cancel').parent().hide();
	});
</script>

<? view::load('cp/system/elements/template/footer'); ?>
