<? if ( ($system_message_error = view::getError('message_error')) || ($system_message_error = isset($message_error) ? $message_error : false )): ?>
	<div class="alert error">
		<a href="#" class="close" onclick="$(this).parent().fadeOut();return false;">x</a>
		<?=$system_message_error?>
	</div>
<? endif; ?>
<? if ( ($system_message_info = view::getInfo('message_info')) || ($message_info = isset($message_info) ? $message_info : false ) ): ?>
	<div class="alert success">
		<a href="#" class="close" onclick="$(this).parent().fadeOut();return false;">x</a>
		<?=$system_message_info?>
	</div>
<? endif; ?>
