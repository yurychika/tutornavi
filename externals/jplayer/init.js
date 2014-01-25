function jplayerInitAudio()
{
	$(function(){
		var history = {};
		$('.jp-jplayer:not(.initialized)').each(function(){
			var index = $(this).data('index');
			var file = $(this).data('file');
			var id = $(this).data('id');
			var ext = $(this).data('ext');
			var html = '<div id="jp_container_' + index + '" class="jp-audio clearfix">' +
			'	<div class="jp-gui jp-interface">' +
			'		<ul class="unstyled jp-controls">' +
			'			<li>' +
			'				<a href="javascript:;" class="jp-button jp-play">play</a>' +
			'				<a href="javascript:;" class="jp-button jp-pause">pause</a>' +
			'			</li>' +
			'			<li>' +
			'				<div class="jp-current-time"></div>' +
			'			</li>' +
			'			<li>' +
			'				<div class="jp-progress">' +
			'					<div class="jp-seek-bar">' +
			'						<div class="jp-play-bar"></div>' +
			'					</div>' +
			'				</div>' +
			'			</li>' +
			'			<li>' +
			'				<div class="jp-duration"></div>' +
			'			</li>' +
			'			<li>' +
			'				<a href="javascript:;" class="jp-button jp-mute">mute</a>' +
			'				<a href="javascript:;" class="jp-button jp-unmute">unmute</a>' +
			'			</li>' +
			'			<li class="jp-volume" style="display: none;">' +
			'				<div class="jp-volume-bar">' +
			'					<div class="jp-volume-bar-value"></div>' +
			'				</div>' +
			'			</li>' +
			'		</ul>' +
			'	</div>' +
			'	<div class="jp-no-solution">' +
			'		<span>Update Required</span>' +
			'		To play the media you will need to either update your browser to a recent version or update your <a href="http://get.adobe.com/flashplayer/" target="_blank">Flash plugin</a>.' +
			'	</div>' +
			'</div>';

			$(this).addClass('initialized');
			$(this).after(html);

			$(this).jPlayer({
				ready: function (){
					$(this).jPlayer('setMedia', {
						mp3: file
					});
				},
				play: function() {
					if ( typeof(history[id]) == 'undefined' )
					{
						$.ajax({type: 'POST', url: site_url + 'music/play/' + id, data: {}, dataType: 'json'});
						history[id] = true;
					}
					$(this).jPlayer('pauseOthers');
				},
				cssSelectorAncestor: '#jp_container_' + index,
				swfPath: base_url+'externals/jplayer/Jplayer2.swf',
				solution: "html,flash",
				wmode: "window",
				supplied: ext
			});

			$('#jp_container_' + index + ' .jp-mute, #jp_container_' + index + ' .jp-unmute, #jp_container_' + index + ' .jp-volume').hover(
				function () {
					$('#jp_container_' + index + ' .jp-volume').show();
				},
				function () {
					$('#jp_container_' + index + ' .jp-volume').hide();
				}
			);

		});
	});
}