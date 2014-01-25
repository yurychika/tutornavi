<script type="text/javascript">
head(function(){
	timelineMessageBoxToggle();
});
function timelineMessageBoxToggle()
{
	$('#input_edit_timeline_message').focus(function(){
		$('#timeline-container > div.post .actions').show();
		$('#timeline-container > div.post textarea').removeClass('preview');
	});
	$('#input_edit_timeline_message').focusout(function(){
		if ( $('#input_edit_timeline_message').val() == '' )
		{
			$('#timeline-container > div.post textarea').addClass('preview');
			$('#timeline-container > div.post .actions').hide();
		}
	});
}
function timelinePost(url, params)
{
	params['message'] = $('#input_edit_timeline_message').val();

	runAjax(url, params, function(content, args){
		$('#timeline-container > ul').prepend(content);
		$('#input_edit_timeline_message').val('');
		$('#timeline-container > div.post span.error').remove();
		$('#timeline-container > div.post textarea').blur();
	}, '', 'ajax-timeline-post', '', function(code, content){
		$('#timeline-container .post').html(content);
		timelineMessageBoxToggle();
		$('#timeline-container > div.post textarea').focus();
	});
}
function timelineUpdate(url,last_id)
{
	runAjax(url,{'last_id':last_id}, function(content, args){
		$('#timeline-container li.loader').remove();
		$('#timeline-container > ul').append(content);
	}, '', 'ajax-timeline-load', 'icon-system-ajax');
}
function timelineDeleteAction(url, params, question)
{
    var is_confirmed = confirm(question);
	if ( is_confirmed )
	{
		runAjax(url, params, function(content, args){
			$('#row-timeline-action-' + params['delete']).remove();
		}, '', 'ajax-timeline-action-' + params['delete']);
	}
}
</script>
