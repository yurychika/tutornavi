<? view::load('cp/system/elements/template/header'); ?>

<section class="plugin-newsletters newsletter-send">

	<p id="newsletters-update-output">
		<?=$output?>
	</p>
	<div id="newsletters-update-spinner" class="icon-text icon-system-ajax hidden"></div>

</section>

<script type="text/javascript">
function sendNewsletter(redirect, ajax)
{
	if ( redirect == '' )
	{
		window.location = '<?=html_helper::siteURL('cp/content/newsletters')?>';
	}
	else if ( ajax )
	{
		runAjax('<?=html_helper::siteURL('cp/content/newsletters/send/'.$newsletterID)?>/' + redirect,
			{},
			function(data){
				$('#newsletters-update-output').html(data.output);
				if ( data.redirect != '' )
				{
					setTimeout(function() { sendNewsletter(data.redirect, ajax); }, 500);
				}
				else
				{
					window.location = '<?=html_helper::siteURL('cp/content/newsletters')?>';
				}
			},
			{},
			'newsletters-update-spinner'
		);
	}
	else
	{
		window.location = '<?=html_helper::siteURL('cp/content/newsletters/send/'.$newsletterID)?>/' + redirect;
	}
}
$(function(){
	setTimeout(function() { sendNewsletter('<?=$redirect?>', true); }, 500);
});
</script>

<? view::load('cp/system/elements/template/footer'); ?>