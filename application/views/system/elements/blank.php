<? view::load('header'); ?>

<? if ( isset($autoclose) && $autoclose ): ?>
	<? view::includeJavascript('setTimeout(function(){ parent.$.colorbox.close(); }, 1000);', 'footer'); ?>
<? endif; ?>

<? view::load('footer'); ?>