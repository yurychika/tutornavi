<? view::load('header'); ?>

<section class="plugin-classifieds ads-manage">

	<? if ( $filters ): ?>
		<? view::load('system/elements/search', array('type' => 'classifieds', 'fields' => $filters, 'values' => $values)); ?>
	<? endif; ?>

	<? if ( $ads ): ?>

		<ul class="unstyled content-list <?=text_helper::alternate()?>">

			<? foreach ( $ads as $ad ): ?>

				<li class="clearfix <?=text_helper::alternate('odd','even')?>" id="row-ad-<?=$ad['ad_id']?>">

					<article class="item">

						<figure class="image classifieds-image">
							<? if ( $ad['file_service_id'] ): ?>
								<div class="image thumbnail" style="background-image:url('<?=storage_helper::getFileURL($ad['file_service_id'], $ad['file_path'], $ad['file_name'], $ad['file_ext'], 't', $ad['file_modify_date'])?>');">
							<? else: ?>
								<div class="image thumbnail no_image">
							<? endif; ?>
								<?=html_helper::anchor('classifieds/view/'.$ad['ad_id'].'/'.text_helper::slug($ad['data_title'], 100), '<span class="name">'.$ad['data_title'].'</span>', array('class' => 'image'))?>
							</div>
						</figure>

						<header class="item-header">
							<h2>
								<?=html_helper::anchor('classifieds/view/'.$ad['ad_id'].'/'.text_helper::slug($ad['data_title'], 100), $ad['data_title'])?>
								<? if ( $ad['post_date'] < date_helper::now() - config::item('ad_expiration', 'classifieds')*60*60*24 ): ?>
									- <?=__('ad_expired', 'classifieds')?>
								<? endif; ?>
							</h2>
						</header>

						<dl class="content-grid">
							<? if ( isset($ad['data_price']) ): ?>
								<dt><?=config::item('fields_classifieds', 'core', 'price')?>:</dt>
								<dd>
									<?=money_helper::symbol(config::item('ad_currency', 'classifieds')).$ad['data_price']?>
								</dd>
							<? endif; ?>
							<? if ( isset($ad['data_location']) ): ?>
								<dt><?=config::item('fields_classifieds', 'core', 'location')?>:</dt>
								<dd>
									<? if ( !is_array($ad['data_location']) ): ?>
										<?=html_helper::anchor('http://maps.google.com/?q='.urlencode($ad['data_location']), $ad['data_location'], array('target' => '_blank'))?>
									<? else: ?>
										<?=html_helper::anchor('http://maps.google.com/?q='.urlencode(implode(',',$ad['data_location'])), implode(', ',$ad['data_location']), array('target' => '_blank'))?>
									<? endif; ?>
								</dd>
							<? endif; ?>
						</dl>

						<div class="item-article">
							<?=text_helper::truncate($ad['data_body'], config::item('ad_preview_chars', 'classifieds'))?>
						</div>

						<footer class="item-footer">

							<ul class="unstyled content-meta clearfix">
								<li class="date">
									<?=__('post_date', 'system_info', array('%date' => date_helper::formatDate($ad['post_date'])))?>
								</li>
								<? if ( config::item('ad_views', 'classifieds') ): ?>
									<li class="views">
										<? if ( $ad['total_views'] > 0 ): ?>
											<?=__('views_num'.($ad['total_views'] == 1 ? '_one' : ''), 'system_info', array('%views' => $ad['total_views']))?>
										<? else: ?>
											<?=__('views_none', 'system_info')?>
										<? endif; ?>
									</li>
								<? endif; ?>
								<? if ( config::item('ad_comments', 'classifieds') && $ad['comments'] ): ?>
									<li class="comments">
										<? if ( $ad['total_comments'] > 0 ): ?>
											<?=__('comments_num'.($ad['total_comments'] == 1 ? '_one' : ''), 'system_info', array('%comments' => $ad['total_comments']))?>
										<? else: ?>
											<?=__('comments_none', 'system_info')?>
										<? endif; ?>
									</li>
								<? endif; ?>
								<li class="actions">
									<?=html_helper::anchor('classifieds/pictures/index/'.$ad['ad_id'], __('pictures', 'classifieds'), array('class' => 'pictures'))?>
									<?=html_helper::anchor('classifieds/edit/'.$ad['ad_id'], __('ad_edit', 'classifieds'), array('class' => 'edit'))?>
									<?=html_helper::anchor('classifieds/delete/'.$ad['ad_id'].'?'.$qstring['url'].'page='.$qstring['page'], __('ad_delete', 'classifieds'), array('class' => 'delete', 'data-html' => __('ad_delete?', 'classifieds'), 'data-role' => 'confirm'))?>
								</li>
								<? if ( config::item('ad_rating', 'classifieds') == 'stars' ): ?>
									<li class="votes">
										<? view::load('comments/rating', array('resource' => 'classified_ad', 'itemID' => $ad['ad_id'], 'votes' => $ad['total_votes'], 'score' => $ad['total_score'], 'rating' => $ad['total_rating'], 'static' => 1)); ?>
									</li>
								<? endif; ?>
								<? if ( config::item('ad_rating', 'classifieds') == 'likes' ): ?>
									<li class="likes">
										<? view::load('comments/likes', array('resource' => 'classified_ad', 'itemID' => $ad['ad_id'], 'likes' => $ad['total_likes'], 'static' => 1)); ?>
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
