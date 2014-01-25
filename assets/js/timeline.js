function timelineNoticesToggle()
{
	if ( typeof(cache['timeline.notices']) == 'undefined' )
	{
		runAjax('[conf.config.site_url]timeline/notices/recent', {},
			function(message, container){
				$('#' + container + ' ul').html(message);
				$('#timeline-notices-recent-popup').toggle();
				cache['timeline.notices'] = true;
			},
			'timeline-notices-recent-popup',
			'timeline-notices-recent-action',
			'icon-system-ajax',
			null, null
		);
	}
	else
	{
		$('#timeline-notices-recent-popup').toggle();
	}
}