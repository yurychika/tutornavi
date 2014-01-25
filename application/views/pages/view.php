<? view::load('header'); ?>

<section class="plugin-pages page-view">

	<div class="content-view">

		<article class="item">

			<div class="item-article">
				<?=$page['data_body']?>
			</div>

			<? if ( config::item('page_views', 'pages') || config::item('page_rating', 'pages') == 'likes' && $page['likes'] || config::item('page_rating', 'pages') == 'stars' && $page['votes'] ): ?>

				<footer class="item-footer">

					<ul class="unstyled content-meta clearfix">
						<? if ( config::item('page_views', 'pages') ): ?>
							<li class="views">
								<? if ( $page['total_views'] > 0 ): ?>
									<?=__('views_num'.($page['total_views'] == 1 ? '_one' : ''), 'system_info', array('%views' => $page['total_views']))?>
								<? else: ?>
									<?=__('views_none', 'system_info')?>
								<? endif; ?>
							</li>
						<? endif; ?>
						<? if ( config::item('page_rating', 'pages') == 'stars' && $page['votes'] ): ?>
							<li class="votes">
								<? view::load('comments/rating', array('resource' => 'page', 'itemID' => $page['page_id'], 'votes' => $page['total_votes'], 'score' => $page['total_score'], 'rating' => $page['total_rating'], 'voted' => $page['user_vote']['score'], 'date' => $page['user_vote']['post_date'])); ?>
							</li>
						<? endif; ?>
						<? if ( config::item('page_rating', 'pages') == 'likes' && $page['likes'] ): ?>
							<li class="likes">
								<? view::load('comments/likes', array('resource' => 'page', 'itemID' => $page['page_id'], 'likes' => $page['total_likes'], 'liked' => ($page['user_vote']['post_date'] ? 1 : 0), 'date' => $page['user_vote']['post_date'])); ?>
							</li>
						<? endif; ?>
					</ul>

				</footer>

			<? endif; ?>

		</article>

	</div>

	<? if ( session::permission('comments_view', 'comments') && config::item('page_comments', 'pages') && $page['comments'] ): ?>
		<? loader::helper('comments/comments'); ?>
		<? comments_helper::getComments('page', 0, $page['page_id'], $page['total_comments'], $page['comments']); ?>
	<? endif; ?>

</section>

<? view::load('footer');
