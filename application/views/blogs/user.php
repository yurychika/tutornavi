<? view::load('header'); ?>

<section class="plugin-blogs blogs-user">

	<? if ( $filters ): ?>
		<? view::load('system/elements/search', array('type' => 'blogs', 'fields' => $filters, 'values' => $values)); ?>
	<? endif; ?>

	<? if ( $blogs ): ?>

		<ul class="unstyled content-list <?=text_helper::alternate()?>">

			<? foreach ( $blogs as $blog ): ?>

				<li class="clearfix <?=text_helper::alternate('odd','even')?>" id="row-blog-<?=$blog['blog_id']?>">

					<article class="item">

						<header class="item-header">
							<h2>
								<?=html_helper::anchor('blogs/view/'.$blog['blog_id'].'/'.text_helper::slug($blog['data_title'], 100), $blog['data_title'])?>
							</h2>
						</header>

						<div class="item-article">
							<?=text_helper::truncate($blog['data_body'], config::item('blogs_preview_chars', 'blogs'))?>
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
								<? if ( config::item('blog_comments', 'blogs') && $blog['comments'] ): ?>
									<li class="comments">
										<? if ( $blog['total_comments'] > 0 ): ?>
											<?=__('comments_num'.($blog['total_comments'] == 1 ? '_one' : ''), 'system_info', array('%comments' => $blog['total_comments']))?>
										<? else: ?>
											<?=__('comments_none', 'system_info')?>
										<? endif; ?>
									</li>
								<? endif; ?>
								<? if ( config::item('blog_rating', 'blogs') == 'stars' ): ?>
									<li class="votes">
										<? view::load('comments/rating', array('resource' => 'blog', 'itemID' => $blog['blog_id'], 'votes' => $blog['total_votes'], 'score' => $blog['total_score'], 'rating' => $blog['total_rating'], 'static' => 1)); ?>
									</li>
								<? endif; ?>
								<? if ( config::item('blog_rating', 'blogs') == 'likes' ): ?>
									<li class="likes">
										<? view::load('comments/likes', array('resource' => 'blog', 'itemID' => $blog['blog_id'], 'likes' => $blog['total_likes'], 'static' => 1)); ?>
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
