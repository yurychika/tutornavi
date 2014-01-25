<? if ( $entries ): ?>

	<div class="content-box helper-news">

		<ul class="unstyled content-list <?=text_helper::alternate()?>">

			<? foreach ( $entries as $entry ): ?>

				<li class="clearfix <?=text_helper::alternate('odd','even')?>" id="row-news-<?=$entry['news_id']?>">

					<article class="item">

						<header class="item-header">
							<h3>
								<?=html_helper::anchor('news/view/'.$entry['news_id'].'/'.text_helper::slug($entry['data_title'], 100), $entry['data_title'])?>
							</h3>
						</header>

						<div class="item-article">
							<?=text_helper::truncate($entry['data_body'], isset($params['truncate']) && $params['truncate'] ? $params['truncate'] : config::item('news_preview_chars', 'news'))?>
							<?=html_helper::anchor('news/view/'.$entry['news_id'].'/'.text_helper::slug($entry['data_title'], 100), '&raquo;')?>
						</div>

					</article>

				</li>

			<? endforeach; ?>

		</ul>

	</div>

<? endif; ?>
