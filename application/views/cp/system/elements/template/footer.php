	<? if ( input::isAjaxRequest() || input::get('modal') ): ?>
	<? else: ?>
				</div>
			</div>
		</section>
		<footer id="footer">
			<div class="row-container">
				<div class="float-right">
					<?=html_helper::anchor('http://www.socialscript.com', 'SocialScript v' . config::item('plugins', 'core', 'system', 'version'), array('target' => '_blank'))?> &copy;
					<?=html_helper::anchor('http://www.vldinteractive.com', 'VLD Interactive Inc.', array('target' => '_blank'))?>
				</div>
			</div>
		</footer>
	<? endif; ?>
	<? if ( input::get('modal') ) view::includeJavascript('parent.$.colorbox.resize({innerHeight:$("body").height()});', 'footer'); ?>
	<?=view::getJavascripts('footer')?>
</body>
</html>