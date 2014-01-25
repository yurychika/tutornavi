<? view::load('header'); ?>

<section class="plugin-classifieds ad-view">

	<div class="content-view">

		<article class="item">

			<figure class="image classifieds-image">
				<? if ( $ad['file_service_id'] ): ?>
					<div class="image thumbnail" style="background-image:url('<?=storage_helper::getFileURL($ad['file_service_id'], $ad['file_path'], $ad['file_name'], $ad['file_ext'], 't', $ad['file_modify_date'])?>');">
				<? else: ?>
					<div class="image thumbnail no_image">
				<? endif; ?>
					<? if ( $ad['total_pictures'] ): ?>
						<?=html_helper::anchor('classifieds/pictures/index/'.$ad['ad_id'].'/'.text_helper::slug($ad['data_title'], 100), '<span class="name">'.$ad['data_title'].'</span>', array('class' => 'image'))?>
						<div class="overlay element pictures">
							<?=__('classifieds_pictures_num'.($ad['total_pictures'] == 1 ? '_one' : ''), 'system_info', array('%pictures' => $ad['total_pictures']))?>
						</div>
					<? endif; ?>
				</div>
			</figure>

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
				<?=nl2br($ad['data_body'])?>
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
					<? if ( $user['user_id'] == session::item('user_id') ): ?>
						<li class="actions">
							<?=html_helper::anchor('classifieds/edit/'.$ad['ad_id'], __('ad_edit', 'classifieds'), array('class' => 'edit'))?>
							<?=html_helper::anchor('classifieds/delete/'.$ad['ad_id'], __('ad_delete', 'classifieds'), array('class' => 'delete', 'data-html' => __('ad_delete?', 'classifieds'), 'data-role' => 'confirm'))?>
						</li>
					<? endif; ?>
					<? if ( config::item('reports_active', 'reports') && users_helper::isLoggedin() && $ad['user_id'] != session::item('user_id') && session::permission('reports_post', 'reports') ): ?>
						<li class="report">
							<?=html_helper::anchor('report/submit/classified_ad/'.$adID, __('report', 'system'), array('data-role' => 'modal', 'data-display' => 'iframe', 'data-title' => __('report', 'system')))?>
						</li>
					<? endif; ?>
					<? if ( config::item('ad_rating', 'classifieds') == 'stars' ): ?>
						<li class="votes">
							<? view::load('comments/rating', array('resource' => 'classified_ad', 'itemID' => $ad['ad_id'], 'votes' => $ad['total_votes'], 'score' => $ad['total_score'], 'rating' => $ad['total_rating'], 'voted' => $ad['user_vote']['score'], 'date' => $ad['user_vote']['post_date'])); ?>
						</li>
					<? endif; ?>
					<? if ( config::item('ad_rating', 'classifieds') == 'likes' ): ?>
						<li class="likes">
							<? view::load('comments/likes', array('resource' => 'classified_ad', 'itemID' => $ad['ad_id'], 'likes' => $ad['total_likes'], 'liked' => ($ad['user_vote']['post_date'] ? 1 : 0), 'date' => $ad['user_vote']['post_date'])); ?>
						</li>
					<? endif; ?>
				</ul>

			</footer>

		</article>

	</div>

	<? if ( session::permission('comments_view', 'comments') && config::item('ad_comments', 'classifieds') && $ad['comments'] ): ?>
		<? loader::helper('comments/comments'); ?>
		<? comments_helper::getComments('classified_ad', $ad['user_id'], $ad['ad_id'], $ad['total_comments'], $ad['comments']); ?>
	<? endif; ?>

</section>

<? view::load('footer');
