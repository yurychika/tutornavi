<? view::load('header'); ?>

<section class="plugin-news news-view">

	<div class="content-view">

		<article class="item">

			<div class="item-article">
				<?=nl2br($news['data_body'])?>
			</div>

			<footer class="item-footer">

				<ul class="unstyled content-meta clearfix">
					<li class="date">
						<?=__('post_date', 'system_info', array('%date' => date_helper::formatDate($news['post_date'])))?>
					</li>
					<? if ( config::item('news_views', 'news') ): ?>
						<li class="views">
							<? if ( $news['total_views'] > 0 ): ?>
								<?=__('views_num'.($news['total_views'] == 1 ? '_one' : ''), 'system_info', array('%views' => $news['total_views']))?>
							<? else: ?>
								<?=__('views_none', 'system_info')?>
							<? endif; ?>
						</li>
					<? endif; ?>
					<? if ( config::item('news_rating', 'news') == 'stars' && $news['votes'] ): ?>
						<li class="votes">
							<? view::load('comments/rating', array('resource' => 'news', 'itemID' => $news['news_id'], 'votes' => $news['total_votes'], 'score' => $news['total_score'], 'rating' => $news['total_rating'], 'voted' => $news['user_vote']['score'], 'date' => $news['user_vote']['post_date'])); ?>
						</li>
					<? endif; ?>
					<? if ( config::item('news_rating', 'news') == 'likes' && $news['likes'] ): ?>
						<li class="likes">
							<? view::load('comments/likes', array('resource' => 'news', 'itemID' => $news['news_id'], 'likes' => $news['total_likes'], 'liked' => ($news['user_vote']['post_date'] ? 1 : 0), 'date' => $news['user_vote']['post_date'])); ?>
						</li>
					<? endif; ?>
				</ul>

			</footer>

		</article>

	</div>

	<? if ( session::permission('comments_view', 'comments') && config::item('news_comments', 'news') && $news['comments'] ): ?>
		<? loader::helper('comments/comments'); ?>
		<? comments_helper::getComments('news', 0, $news['news_id'], $news['total_comments'], $news['comments']); ?>
	<? endif; ?>

</section>

<? view::load('footer');
