<? view::load('header'); ?>

<section class="plugin-news news-index">

	<? if ( $filters ): ?>
		<? view::load('system/elements/search', array('type' => 'news', 'fields' => $filters, 'values' => $values)); ?>
	<? endif; ?>

	<? if ( $news ): ?>

		<ul class="unstyled content-list <?=text_helper::alternate()?>">

			<? foreach ( $news as $entry ): ?>

				<li class="clearfix <?=text_helper::alternate('odd','even')?>" id="row-news-<?=$entry['news_id']?>">

					<article class="item">

						<header class="item-header">
							<h2>
								<?=html_helper::anchor((config::item('news_blog', 'news') ? 'blog' : 'news').'/view/'.$entry['news_id'].'/'.text_helper::slug($entry['data_title'], 100), $entry['data_title'])?>
							</h2>
						</header>

						<div class="item-article">
							<?=text_helper::truncate($entry['data_body'], config::item('news_preview_chars', 'news'))?>
						</div>

						<footer class="item-footer">

							<ul class="unstyled content-meta clearfix">
								<li class="date">
									<?=__('post_date', 'system_info', array('%date' => date_helper::formatDate($entry['post_date'])))?>
								</li>
								<? if ( config::item('news_views', 'news') ): ?>
									<li class="views">
										<? if ( $entry['total_views'] > 0 ): ?>
											<?=__('views_num'.($entry['total_views'] == 1 ? '_one' : ''), 'system_info', array('%views' => $entry['total_views']))?>
										<? else: ?>
											<?=__('views_none', 'system_info')?>
										<? endif; ?>
									</li>
								<? endif; ?>
								<? if ( config::item('news_comments', 'news') && $entry['comments'] ): ?>
									<li class="comments">
										<? if ( $entry['total_comments'] > 0 ): ?>
											<?=__('comments_num'.($entry['total_comments'] == 1 ? '_one' : ''), 'system_info', array('%comments' => $entry['total_comments']))?>
										<? else: ?>
											<?=__('comments_none', 'system_info')?>
										<? endif; ?>
									</li>
								<? endif; ?>
								<? if ( config::item('news_rating', 'news') == 'stars' && $entry['votes'] ): ?>
									<li class="votes">
										<? view::load('comments/rating', array('resource' => 'news', 'itemID' => $entry['news_id'], 'votes' => $entry['total_votes'], 'score' => $entry['total_score'], 'rating' => $entry['total_rating'], 'static' => 1)); ?>
									</li>
								<? endif; ?>
								<? if ( config::item('news_rating', 'news') == 'likes' && $entry['likes'] ): ?>
									<li class="likes">
										<? view::load('comments/likes', array('resource' => 'news', 'itemID' => $entry['news_id'], 'likes' => $entry['total_likes'], 'static' => 1)); ?>
									</li>
								<? endif; ?>
							</ul>

						</footer>

					</article>

				</li>

			<? endforeach; ?>

		</ul>

		<div class="content-footer">
			<? view::load('system/elements/pagination', array('pagination' => $pagination)); ?>
		</div>

	<? endif; ?>

</section>

<? view::load('footer');
