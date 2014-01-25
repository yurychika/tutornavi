$(function(){
    $('[data-role="rating"]').each(function()
    {
        $(this).rating();
    })
});

function submitVote(url, params, container)
{
	var container = 'rate-' + params['resource'] + '-' + params['item_id'];

	runAjax(url, params,
		function(message, container){
			replaceContent(message, container);
			initTooltips();
		},
		'rating-container-' + params['resource'] + '-' + params['item_id'],
		'ajax-rating-' + params['resource'] + '-' + params['item_id'],
		null, null, null,
		function(container){
			$('#rating-container-' + params['resource'] + '-' + params['item_id'] + ' a.star-' + params['score']).tipsy("hide");
		},
		'rating-container-' + params['resource'] + '-' + params['item_id']
	);
}
