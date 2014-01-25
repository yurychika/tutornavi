<script type="text/javascript">
	var languages = ['<?=implode("','", config::item('languages', 'core', 'keywords'))?>'];
	var selectedLanguage = '<?=session::item('language')?>';
	function switchLanguage(language)
	{
		if ( selectedLanguage == language )
		{
			return;
		}
		$(languages).each(function(index, language)
		{
			if ( language == selectedLanguage )
			{
				$('.translate_item_'+language).addClass('hidden');
			}
			else
			{
				$('.translate_item_'+language).removeClass('hidden');
			}
		});
		selectedLanguage = language;
	}
	$(function(){
		switchLanguage('<?=session::item('language')?>');
	});
</script>
