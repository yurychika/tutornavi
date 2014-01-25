<? text_helper::alternate('odd','even'); ?>
<? if ( $actions ): ?>

	<? foreach ( $actions as $actionID => $action ): ?>

		<li class="clearfix <?=text_helper::alternate('odd','even')?>" id="row-timeline-action-<?=$actionID?>">

			<article class="item">

				<figure class="image users-image">
					<? view::load('users/profile/elements/picture', array_merge($action['poster'] ? $action['poster'] : $action['user'], array('picture_file_suffix' => 't'))); ?>
				</figure>

				<?=$action['html']?>

				<footer class="item-footer">

					<ul class="unstyled content-meta clearfix">
						<li class="date">
							<?=date_helper::humanSpan($action['post_date'])?>
						</li>
						<? if ( users_helper::isLoggedin() && config::item('timeline_comments', 'timeline') ): ?>
							<? if ( $action['comments'] ): ?>
								<? if ( $action['comments']['post'] ): ?>
									<li class="comments">
										<?=html_helper::anchor('#', __('comment_new', 'comments'), array('onclick' => "toggleCommentsPost({'resource':'".$action['relative_resource']."','item_id':".$action['item_id']."});return false;\$('#post-comment-timeline-".$actionID."').toggle();return false;"))?>
									</li>
								<? endif; ?>
							<? else: ?>
								<? if ( session::permission('comments_view', 'comments') && session::permission('comments_post', 'comments') ): ?>
									<li class="comments">
										<?=html_helper::anchor('#', __('comment_new', 'comments'), array('onclick' => "toggleCommentsPost({'resource':'timeline','item_id':".$actionID."});return false;\$('#post-comment-timeline-".$actionID."').toggle();return false;"))?>
									</li>
								<? endif; ?>
							<? endif; ?>
						<? endif; ?>
						<? if ( session::permission('actions_delete', 'timeline') && ( $action['user_id'] == session::item('user_id') || $action['poster_id'] && $action['poster_id'] == session::item('user_id') ) ): ?>
							<li class="delete">
								<?=html_helper::anchor('timeline', __('action_delete', 'timeline'), array('onclick' => "timelineDeleteAction(this.href,{'delete':".$actionID."},'".__('action_delete?', 'timeline', array('\'' => '\\\''), array(), false)."');return false;"))?>
								<span class="icon icon-system-ajax ajax hidden" id="ajax-timeline-action-<?=$actionID?>"></span>
							</li>
						<? endif; ?>
						<? if ( config::item('reports_active', 'reports') && users_helper::isLoggedin() && $action['type_id'] == config::item('keywords', 'timeline', 'timeline_message_post') && $action['poster_id'] != session::item('user_id') && session::permission('reports_post', 'reports') ): ?>
							<li class="report">
								<?=html_helper::anchor('report/submit/timeline_message/'.$action['item_id'], __('report', 'system'), array('data-role' => 'modal', 'data-display' => 'iframe', 'data-title' => __('report', 'system')))?>
							</li>
						<? endif; ?>
						<? if ( config::item('timeline_rating', 'timeline') ): ?>
							<? if ( $action['rating'] ): ?>
								<? if ( $action['rating']['type'] == 'stars' ): ?>
									<li class="votes">
										<? view::load('comments/rating', array('resource' => $action['relative_resource'], 'itemID' => $action['item_id'], 'votes' => $action['rating']['total_votes'], 'score' => $action['rating']['total_score'], 'rating' => $action['rating']['total_rating'], 'voted' => (isset($ratings[$action['relative_resource_id']][$action['item_id']]['score']) ? $ratings[$action['relative_resource_id']][$action['item_id']]['score'] : 0), 'date' => (isset($ratings[$action['relative_resource_id']][$action['item_id']]['post_date']) ? $ratings[$action['relative_resource_id']][$action['item_id']]['post_date'] : 0))); ?>
									</li>
								<? endif; ?>
								<? if ( $action['rating']['type'] == 'likes' ): ?>
									<li class="likes">
										<? view::load('comments/likes', array('resource' => $action['relative_resource'], 'itemID' => $action['item_id'], 'likes' => $action['rating']['total_likes'], 'liked' => (isset($ratings[$action['relative_resource_id']][$action['item_id']]['post_date']) ? 1 : 0), 'date' => (isset($ratings[$action['relative_resource_id']][$action['item_id']]['post_date']) ? $ratings[$action['relative_resource_id']][$action['item_id']]['post_date'] : 0))); ?>
									</li>
								<? endif; ?>
							<? else: ?>
								<? if ( config::item('timeline_rating', 'timeline') == 'stars' ): ?>
									<li class="votes">
										<? view::load('comments/rating', array('resource' => 'timeline', 'itemID' => $actionID, 'votes' => $action['total_votes'], 'score' => $action['total_score'], 'rating' => $action['total_rating'], 'voted' => (isset($ratings[$action['relative_resource_id']][$actionID]['score']) ? $ratings[$action['relative_resource_id']][$actionID]['score'] : 0), 'date' => (isset($ratings[$action['relative_resource_id']][$actionID]['post_date']) ? $ratings[$action['relative_resource_id']][$actionID]['post_date'] : 0))); ?>
									</li>
								<? endif; ?>
								<? if ( config::item('timeline_rating', 'timeline') == 'likes' ): ?>
									<li class="likes">
										<? view::load('comments/likes', array('resource' => 'timeline', 'itemID' => $actionID, 'likes' => $action['total_likes'], 'liked' => (isset($ratings[$action['relative_resource_id']][$actionID]['post_date']) ? 1 : 0), 'date' => (isset($ratings[$action['relative_resource_id']][$actionID]['post_date']) ? $ratings[$action['relative_resource_id']][$actionID]['post_date'] : 0))); ?>
									</li>
								<? endif; ?>
							<? endif; ?>
						<? endif; ?>
					</ul>

				</footer>

			</article>

			<? if ( session::permission('comments_view', 'comments') && config::item('timeline_comments', 'timeline') ): ?>
				<? loader::helper('comments/comments'); ?>
				<? if ( $action['comments'] ): ?>
					<? if ( !isset($action['comments']['privacy']) || $action['comments']['privacy'] ): ?>
						<? comments_helper::getComments($action['relative_resource'], 0, $action['item_id'], $action['comments']['total_comments'], 2, $action['comments']['post'], false, true); ?>
					<? endif; ?>
				<? else: ?>
					<? comments_helper::getComments('timeline', 0, $actionID, $action['total_comments'], 2, 1, false, true); ?>
				<? endif; ?>
			<? endif; ?>

		</li>

	<? endforeach; ?>

	<? if ( !input::post('post') ): ?>

		<li class="clearfix loader <?=text_helper::alternate('odd','even')?>">

			<article class="item">
				<?=html_helper::anchor($user ? 'timeline/user/' . $user['slug_id'] : 'timeline', __('actions_load', 'timeline'), array('id' => 'ajax-timeline-load', 'class' => 'icon icon-text icon-timeline-load', 'onclick' => "timelineUpdate(this.href,".$actionID.");return false;"))?>
				<span class="icon icon-system-ajax ajax" id="ajax-timeline-load" style="display:none"></span>
			</article>

		</li>

	<? endif; ?>

<? elseif ( !input::post('post') ): ?>

	<? if ( input::isAjaxRequest() ): ?>

		<li class="clearfix <?=text_helper::alternate('odd','even')?> shift" id="row-action-0">

			<article class="item">
				<?=__('actions_load_last', 'timeline')?>
			</article>

		</li>

	<? else: ?>

		<li class="clearfix <?=text_helper::alternate('odd','even')?> shift" id="row-action-0">

			<article class="item">
				<?=__('actions_none', 'timeline')?>
			</article>

		</li>

	<? endif; ?>

<? endif; ?>

<? if ( !input::post('post') ): ?>

	<?=view::getStylesheets()?>
	<?=view::getJavascripts()?>
	<?=view::getJavascripts('manual')?>

<? endif; ?>