<section class="plugin-comments comments-index" id="comments-container-<?=$resource?>-<?=$itemID?>" <?=(!$comments && !$info ? 'style="display:none"' : '')?>>

	<? view::load('message'); ?>

	<? if ( $info ): ?>

		<div class="info">

			<ul class="unstyled content-meta clearfix">
				<li class="comments">
					<? if ( $pagination->getTotalItems() ): ?>
						<?=__('comments_num'.($pagination->getTotalItems() == 1 ? '_one' : ''), 'system_info', array('%comments' => $pagination->getTotalItems()))?>
					<? else: ?>
						<?=__('comments_none', 'system_info')?>
					<? endif; ?>
				</li>
				<? if ( $post ): ?>
					<li class="post">
						<?=html_helper::anchor('#', __('comment_new', 'comments'), array('onclick' => "\$('#post-comment-".$resource."-".$itemID."').toggle();return false;"))?>
					</li>
				<? endif; ?>
			</ul>

		</div>

	<? endif; ?>

	<? if ( $post ): ?>

		<div class="post" id="post-comment-<?=$resource?>-<?=$itemID?>" <?=(form_helper::getTotalErrors() ? '' : 'style="display:none"')?>>

			<?=form_helper::openForm()?>

				<fieldset class="form">
					<div class="row">
						<div class="field">

							<? view::load('system/elements/field/edit', array(
								'prefix' => $resource.'_'.$itemID,
								'field' => array(
									'keyword' => 'comment',
									'type' => 'textarea',
									'class' => 'input-wide input-small',
								),
							)); ?>

						</div>
					</div>
					<div class="row actions clearfix">
						<? view::load('system/elements/button', array(
							'onclick' => "postComment('".config::siteURL('comments/browse')."',{'do_save_comment':1,'resource':'".$resource."','item_id':".$itemID.",'split':".$split.",'post':".($post?1:0).",'info':".($info?1:0)."});return false;",
							'class' => 'small'
						)); ?>
						<span class="icon icon-system-ajax ajax" style="display:none" id="ajax-comments-<?=$resource?>-<?=$itemID?>"></span>
					</div>
				</fieldset>

			<?=form_helper::closeForm(array('do_save_comment' => 1))?>

		</div>

	<? endif; ?>

	<? if ( $comments ): ?>

		<ul class="unstyled comments-list">

			<? foreach ( $comments as $comment ): ?>

				<li class="clearfix <?=text_helper::alternate('odd','even')?>" id="comments-<?=$itemID?>-<?=$comment['comment_id']?>">

					<article class="item">

						<figure class="image users-image">
							<? view::load('users/profile/elements/picture', array_merge($comment['user'], array('picture_file_suffix' => 't'))); ?>
						</figure>

						<div class="item-article">
							<span class="author">
								<?=users_helper::anchor($comment['user'])?>
							</span>
							<?=$comment['comment']?>
						</div>

						<ul class="unstyled content-meta clearfix">
							<li class="date">
								<?=date_helper::humanSpan($comment['post_date'])?>
							</li>
							<? if ( session::permission('comments_delete', 'comments') && ( $comment['poster_id'] == session::item('user_id') || $comment['user_id'] && $comment['user_id'] == session::item('user_id') ) ): ?>
								<li class="delete">
									<?=html_helper::anchor('comments/browse', __('comment_delete', 'comments'), array('onclick' => "deleteComment(this.href,{'resource':'$resource','item_id':$itemID,'page':".$pagination->getCurrentPage().",'delete':".$comment['comment_id'].",'split':".$split.",'post':".($post?1:0).",'info':".($info?1:0)."},'".__('comment_delete?', 'comments')."');return false;"))?>
									<span class="icon icon-system-ajax ajax hidden" id="ajax-comments-<?=$resource?>-<?=$itemID?>-<?=$comment['comment_id']?>"></span>
								</li>
							<? endif; ?>
							<? if ( config::item('reports_active', 'reports') && users_helper::isLoggedin() && $comment['poster_id'] != session::item('user_id') && session::permission('reports_post', 'reports') ): ?>
								<li class="report">
									<?=html_helper::anchor('report/submit/comment/'.$comment['comment_id'], __('report', 'system'), array('data-role' => 'modal', 'data-display' => 'iframe', 'data-title' => __('report', 'system')))?>
								</li>
							<? endif; ?>
						</ul>

					</article>

				</li>

			<? endforeach; ?>

			<? if ( $pagination->getTotalPages() > 1 ): ?>

				<li class="pagination">
					<? view::load('system/elements/pagination', array('pagination' => $pagination, 'type' => $resource, 'item' => $itemID)); ?>
				</li>

			<? endif; ?>

		</ul>

	<? endif; ?>

</section>
