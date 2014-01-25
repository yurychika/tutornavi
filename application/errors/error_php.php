<div style="font-family: Lucida Grande, Verdana, Sans-serif; font-size: 12px; border: #0080C0 1px solid; background-color: #fff; padding: 20px; margin: 10px;">

	<h2>A PHP Error was encountered</h2>

	<h3>Message:</h3>
	<pre><?=$message?></pre>

	<h3>Location:</h3>
	<pre><?=$file?> on line <?=$line?></pre>

	<? if ( $trace ): ?>

		<h3>Trace:</h3>
		<pre><?=$trace?></pre>

	<? endif; ?>

</div>
