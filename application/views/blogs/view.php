<? view::load('header'); ?>

<section class="plugin-blogs blog-view">

	<div class="content-view">

		<article class="item">

			<div class="item-article">
				<?=nl2br($blog['data_body'])?>
			</div>

			<footer class="item-footer">

				<ul class="unstyled content-meta clearfix">
					<li class="date">
						<?=__('post_date', 'system_info', array('%date' => date_helper::formatDate($blog['post_date'])))?>
					</li>
					<? if ( config::item('blog_views', 'blogs') ): ?>
						<li class="views">
							<? if ( $blog['total_views'] > 0 ): ?>
								<?=__('views_num'.($blog['total_views'] == 1 ? '_one' : ''), 'system_info', array('%views' => $blog['total_views']))?>
							<? else: ?>
								<?=__('views_none', 'system_info')?>
							<? endif; ?>
						</li>
					<? endif; ?>
					<? if ( $user['user_id'] == session::item('user_id') ): ?>
						<li class="actions">
							<?=html_helper::anchor('blogs/edit/'.$blog['blog_id'], __('blog_edit', 'blogs'), array('class' => 'edit'))?>
							<?=html_helper::anchor('blogs/delete/'.$blog['blog_id'], __('blog_delete', 'blogs'), array('class' => 'delete', 'data-html' => __('blog_delete?', 'blogs'), 'data-role' => 'confirm'))?>
						</li>
					<? endif; ?>
					<? if ( config::item('reports_active', 'reports') && users_helper::isLoggedin() && $blog['user_id'] != session::item('user_id') && session::permission('reports_post', 'reports') ): ?>
						<li class="report">
							<?=html_helper::anchor('report/submit/blog/'.$blogID, __('report', 'system'), array('data-role' => 'modal', 'data-display' => 'iframe', 'data-title' => __('report', 'system')))?>
						</li>
					<? endif; ?>
					<? if ( config::item('blog_rating', 'blogs') == 'stars' ): ?>
						<li class="votes">
							<? view::load('comments/rating', array('resource' => 'blog', 'itemID' => $blog['blog_id'], 'votes' => $blog['total_votes'], 'score' => $blog['total_score'], 'rating' => $blog['total_rating'], 'voted' => $blog['user_vote']['score'], 'date' => $blog['user_vote']['post_date'])); ?>
						</li>
					<? endif; ?>
					<? if ( config::item('blog_rating', 'blogs') == 'likes' ): ?>
						<li class="likes">
							<? view::load('comments/likes', array('resource' => 'blog', 'itemID' => $blog['blog_id'], 'likes' => $blog['total_likes'], 'liked' => ($blog['user_vote']['post_date'] ? 1 : 0), 'date' => $blog['user_vote']['post_date'])); ?>
						</li>
					<? endif; ?>
				</ul>

			</footer>

		</article>

	</div>

	<? if ( session::permission('comments_view', 'comments') && config::item('blog_comments', 'blogs') && $blog['comments'] ): ?>
		<? loader::helper('comments/comments'); ?>
		<? comments_helper::getComments('blog', $blog['user_id'], $blog['blog_id'], $blog['total_comments'], $blog['comments']); ?>
	<? endif; ?>

</section>

<? view::load('footer');
