function submitLike(url, params)
{
	runAjax(url, params,
		function(message, container){
			replaceContent(message, container);
			initTooltips();
		},
		'like-container-' + params['resource'] + '-' + params['item_id'],
		'like-container-' + params['resource'] + '-' + params['item_id'] + ' a.action',
		'icon-system-ajax',
		null, null,
		function(container){
			$('#like-container-' + params['resource'] + '-' + params['item_id'] + ' a.action').tipsy("hide");
		}
	);
}

