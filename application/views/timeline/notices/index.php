<? view::load('header'); ?>

<section class="plugin-timeline notices-index" id="timeline-notices-container">

	<ul class="unstyled content-list <?=text_helper::alternate()?>">

		<? view::load('timeline/notices/items', array('notices' => $notices)); ?>

	</ul>

</section>

<script type="text/javascript">
function timelineNoticesUpdate(url,last_id)
{
	runAjax(url,{'last_id':last_id}, function(content, args){
		$('#timeline-notices-container li.loader').remove();
		$('#timeline-notices-container > ul').append(content);
	}, '', 'ajax-timeline-load', 'icon-system-ajax');
}
function timelineDeleteAction(url, params, question)
{
    var is_confirmed = confirm(question);
	if ( is_confirmed )
	{
		runAjax(url, params, function(content, args){
			$('#row-timeline-notices-action-' + params['delete']).remove();
		}, '', 'ajax-timeline-notices-action-' + params['delete']);
	}
}
</script>

<? view::load('footer');
