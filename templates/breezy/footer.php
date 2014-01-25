	<? if ( input::isAjaxRequest() || input::get('modal') ): ?>
	<? else: ?>
				</section>
			</div>
		</section>
		<footer id="footer">
			<div class="inner clearfix">
				<div class="navigation">
					<? if ( config::item('site_bottom_nav', 'lists') ): ?>
						<ul class="unstyled clearfix">
							<? foreach ( config::item('site_bottom_nav', 'lists') as $list ): ?>
								<li><?=html_helper::anchor($list['uri'], $list['name'])?></li>
							<? endforeach; ?>
						</ul>
					<? endif; ?>
				</div>
				<div class="social-bookmarks">
					<ul class="unstyled clearfix">
						<? if ( config::item('social_facebook', 'template') ): ?>
							<li class="facebook"><?=html_helper::anchor('http://www.facebook.com/'.config::item('social_facebook', 'template'), 'Facebook', array('target' => '_blank'))?></li>
						<? endif; ?>
						<? if ( config::item('social_linkedin', 'template') ): ?>
							<li class="linkedin"><?=html_helper::anchor('http://linkedin.com/in/'.config::item('social_linkedin', 'template'), 'LinkedIn', array('target' => '_blank'))?></li>
						<? endif; ?>
						<? if ( config::item('social_skype', 'template') ): ?>
							<li class="skype"><a href="skype:<?=config::item('social_skype', 'template')?>?call" target="_blank">Skype</a></li>
						<? endif; ?>
						<? if ( config::item('social_twitter', 'template') ): ?>
							<li class="twitter"><?=html_helper::anchor('http://twitter.com/'.config::item('social_twitter', 'template'), 'Twitter', array('target' => '_blank'))?></li>
						<? endif; ?>
						<? if ( config::item('social_youtube', 'template') ): ?>
							<li class="youtube"><?=html_helper::anchor('http://www.youtube.com/'.config::item('social_youtube', 'template'), 'YouTube', array('target' => '_blank'))?></li>
						<? endif; ?>
						<? if ( config::item('social_rss', 'template') ): ?>
							<li class="rss"><?=html_helper::anchor(config::item('social_rss', 'template'), 'RSS', array('target' => '_blank'))?></li>
						<? endif; ?>
					</ul>
				</div>
				<? if ( config::item('branding_text', 'system') ): ?>
					<? /* CREDIT LINE: UNAUTHORIZED REMOVAL WILL VOID LICENSE */ ?>
					<? if ( config::item('social_facebook', 'template') || config::item('social_linkedin', 'template') || config::item('social_skype', 'template') || config::item('social_twitter', 'template') || config::item('social_youtube', 'template') || config::item('social_rss', 'template') ): ?>
						<div class="clearfix"></div>
					<? endif; ?>
    				<div class="copyright <?=(config::item('social_facebook', 'template') || config::item('social_linkedin', 'template') || config::item('social_skype', 'template') || config::item('social_twitter', 'template') || config::item('social_youtube', 'template') || config::item('social_rss', 'template') ? 'social' : 'single' )?>">
						<?=config::item('branding_text', 'system')?>
					</div>
				<? endif; ?>
			</div>
		</footer>
	<? endif; ?>
	<? if ( input::get('modal') ) view::includeJavascript('parent.$.colorbox.resize({innerHeight:$("body").height()});', 'footer'); ?>
	<?=view::getJavascripts('footer')?>
</body>
</html>