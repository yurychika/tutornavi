<div id="uploader_<?=$keyword?>_container" class="uploader-container" <?=(isset($hidden) && $hidden ? 'style="display:none;"' : '')?>>

	<div id="uploader-container" class="uploader">

		<?=html_helper::anchor('#', __('file_select'.($limit > 1 ? '_multi' : ''), 'system_files'), array('id' => 'uploader-browse', 'class' => 'button browse'))?>
		<?=html_helper::anchor('#', __('file_upload'.($limit > 1 ? '_multi' : ''), 'system_files'), array('id' => 'uploader-submit', 'class' => 'button upload', 'style' => 'display:none'))?>

		<ul id="uploader-files" class="unstyled files hidden">
		</ul>

		<div class="notes">
			<?=__('file_max_size', 'system_files', array('%size' => $maxsize.__('size_mb', 'system_files')))?>
			<!--
			<br/>
			<?=__('file_extensions', 'system_files', array('%extensions' => str_replace(',', ', ', $extensions)))?>
			-->
			<div class="error">&nbsp;</div>
		</div>

	</div>

	<?=form_helper::openMultiForm($action, array('style' => 'display:none'), isset($params) && is_array($params) && $params ? $params : array())?>

		<fieldset class="form grid">

			<div class="row">
				<label for="input_edit_upload">
					<?=__('file_select', 'system_files')?> <?=(isset($required) && $required ? '<span class="required">*</span>' : '')?>
				</label>
				<div class="field">
					<?=form_helper::upload('file')?>
					<?=form_helper::error('file')?>
				</div>
			</div>

			<div class="row actions">
				<? view::load('system/elements/button', array('value' => __('upload', 'system'))); ?>
			</div>

			<div class="row">
				<div class="field">
					<?=__('file_max_size', 'system_files', array('%size' => $maxsize.__('size_mb', 'system_files')))?>
					<br/>
					<?=__('file_extensions', 'system_files', array('%extensions' => str_replace(',', ', ', $extensions)))?>
				</div>
			</div>

		</fieldset>

	<?=form_helper::closeForm(array('do_upload' => 1))?>

</div>

<?=html_helper::style('externals/plupload/style.css')?>

<script type="text/javascript">
var uploader = null;
head.js('<?=html_helper::baseURL('externals/plupload/plupload.full.js')?>');
$(function () {
	head(function(){
		var redirect = '';
		uploader = new plupload.Uploader({
			runtimes : 'html5,flash,silverlight',
			browse_button : 'uploader-browse',
			container : 'uploader-container',
			max_file_size : '<?=$maxsize?>mb',
			<? if ( $limit == 1 ): ?>
				multi_selection : false,
			<? endif; ?>
			url : '<?=html_helper::siteURL($action)?>?ajax=true',
			flash_swf_url : '<?=html_helper::baseURL()?>externals/plupload/plupload.flash.swf',
			silverlight_xap_url : '<?=html_helper::baseURL()?>externals/plupload/plupload.silverlight.xap',
			filters : [
				{title : "Files", extensions : "<?=$extensions?>"}
			],
			preinit : {
				Init: function(up, params) {

					//$('#uploader-container .error').html("Current runtime: " + params.runtime);

					$('#uploader-submit').click(function(e) {
						uploader.start();
						e.preventDefault();
					});
				},
			},
			init : {
				FilesAdded: function(up, files) {

					var limit = false;

					$.each(files, function(i, file) {
						if ( up.files.length > parseInt('<?=$limit?>') )
						{
							limit = true;
							up.removeFile(file);
						}
						else
						{
							$('#uploader-files').append(
								'<li class="file clearfix file-' + file.id + '">' +
								'<span class="name">' + file.name + '</span>' +
								'<a href="#" class="delete"><?=__('file_delete', 'system_files')?></a>' +
								'<span class="size">' + plupload.formatSize(file.size) + '</span>' +
								'<span class="status"></span>' +
								'<div class="progress hidden"><div class="bar"></div></div>' +
							'</li>');
							$('#uploader-files .file-' + file.id + ' .delete').click(function() {
								up.removeFile(file);
								$('#uploader-files .file-' + file.id).remove();
								return false;
							});
						}
					});

					up.refresh();

					if ( limit )
					{
						alert('<?=__('files_limit_reached'.($limit > 1 ? '_multi' : ''), 'system_files', array('%1' => $limit))?>');
					}
				},
				QueueChanged: function(up) {
					if ( up.files.length > 0 && up.files.length <= parseInt('<?=$limit?>') )
					{
						$('#uploader-files').show();

						<? if ( @$autostart ): ?>
							uploader.start();
						<? else: ?>
							$('#uploader-submit').show();
						<? endif; ?>
					}
					else
					{
						$('#uploader-files').hide();
						$('#uploader-submit').hide();
					}

					if ( up.files.length >= parseInt('<?=$limit?>') )
					{
						//$('#uploader-browse').hide();
					}
					else
					{
						//$('#uploader-browse').show();
					}
				},
				UploadFile: function(up, file) {
					$('#uploader-files .file-' + file.id + ' a.delete').hide();
					$('#uploader-files .file-' + file.id + " .progress").show();
					$('#uploader-files .file-' + file.id).addClass('uploading');
				},
				StateChanged: function(up) {
					if ( up.state == plupload.STARTED )
					{
						$('#uploader-submit').hide();
					}
					else
					{
						$('#uploader-submit').hide();
					}
				},
				UploadProgress: function(up, file) {
					$('#uploader-files .file-' + file.id + " .progress .bar").css('width', file.percent + '%');
				},
				FileUploaded: function(up, file, response) {

					var response = jQuery.parseJSON(response.response);

					$('#uploader-files .file-' + file.id).removeClass('uploading');
					$('#uploader-files .file-' + file.id + ' .progress').hide();

					if ( response.status == 'ok' )
					{
						$('#uploader-files .file-' + file.id).addClass('complete');
						$('#uploader-files .file-' + file.id + ' .status').text('<?=__('file_uploaded', 'system_files')?>');
					}
					else
					{
						$('#uploader-files .file-' + file.id).addClass('error');
						$('#uploader-files .file-' + file.id + ' .status').text(response.message);
						<? if ( $limit == 1 ): ?>
							$('#uploader-files .file-' + file.id + ' a.delete').show();
						<? endif; ?>
					}

					if ( typeof(response.message.redirect) != 'undefined' )
					{
						redirect = response.message.redirect;
					}
				},
				UploadComplete: function(up, files) {
					if ( redirect != '' )
					{
						window.location = redirect;
					}
				},
				Error: function(up, err) {
					$('#uploader-container .error').text("Error: " + err.code + ", Message: " + err.message + ( err.file ? ", File: " + err.file.name : "" ));
					up.refresh();
				}
			}
		});
		uploader.init();
	});
});
</script>
