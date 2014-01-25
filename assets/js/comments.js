function postComment(url, params)
{
	params['comment'] = $('#input_edit_' + params['resource'] + '_' + params['item_id'] + '_comment').val();

	runAjax(url, params, 'replaceContent', 'comments-container-' + params['resource'] + '-' + params['item_id'], 'ajax-comments-' + params['resource'] + '-' + params['item_id']);
}

function toggleCommentsPost(params)
{
	if ( $('#comments-container-' + params['resource'] + '-' + params['item_id'] + ' ul.comments-list').length == 0 )
	{
		if ( $('#comments-container-' + params['resource'] + '-' + params['item_id']).css('display') == 'none' )
		{
			$('#comments-container-' + params['resource'] + '-' + params['item_id']).show();
			$('#post-comment-' + params['resource'] + '-' + params['item_id']).show();
		}
		else
		{
			$('#comments-container-' + params['resource'] + '-' + params['item_id']).hide();
			$('#post-comment-' + params['resource'] + '-' + params['item_id']).hide();
		}
	}
	else
	{
		$('#post-comment-' + params['resource'] + '-' + params['item_id']).toggle();
	}
}

function deleteComment(url, params, question)
{
    var is_confirmed = confirm(question);
	if ( is_confirmed )
	{
		runAjax(url, params, 'replaceContent', 'comments-container-' + params['resource'] + '-' + params['item_id'], 'ajax-comments-' + params['resource'] + '-' + params['item_id'] + '-' + params['delete']);
	}
}
