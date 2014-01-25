<? view::load('cp/system/elements/template/header'); ?>

<section class="plugin-utilities counters-progress">

	<p id="counters-update-output">
		<?=$output?>
	</p>
	<div id="counters-update-spinner" class="icon-text icon-system-ajax hidden"></div>

</section>

<script type="text/javascript">
function updateDbCounters(redirect, ajax)
{
	if ( ajax )
	{
		if ( redirect != '' )
		{
			runAjax('<?=html_helper::siteURL('cp/utilities/counters')?>/' + redirect,
				{},
				function(data){
					$('#counters-update-output').html(data.output);
					if ( data.redirect != '' )
					{
						setTimeout(function() { updateDbCounters(data.redirect, ajax); }, 500);
					}
					else
					{
						window.location = '<?=html_helper::siteURL('cp/utilities/counters')?>';
					}
				},
				{},
				'counters-update-spinner'
			);
		}
		else
		{
			window.location = '<?=html_helper::siteURL('cp/utilities/counters')?>';
		}
	}
	else
	{
		window.location = '<?=html_helper::siteURL('cp/utilities/counters')?>/' + redirect;
	}
}
$(function(){
	setTimeout(function() { updateDbCounters('<?=$redirect?>', true); }, 500);
});
</script>

<? view::load('cp/system/elements/template/footer'); ?>
