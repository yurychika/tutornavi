<?
$picture_file_suffix = isset($picture_file_suffix) ? $picture_file_suffix : '';
$picture = true;

if ( isset($group_id) && $group_id == config::item('group_cancelled_id', 'users') || isset($verified) && !$verified || isset($active) && !$active ):
	$user_id = $picture = 0;
endif;

if ( $picture && isset($picture_file_name, $picture_active) && $picture_file_name && ( $picture_active == 1 || isset($user_id) && $user_id == session::item('user_id') )):
	$picture_file_none = '';
	$picture_path = storage_helper::getFileURL($picture_file_service_id, $picture_file_path, $picture_file_name, $picture_file_ext, $picture_file_suffix, $picture_file_modify_date);
	$picture_path_big = storage_helper::getFileURL($picture_file_service_id, $picture_file_path, $picture_file_name, $picture_file_ext, '', $picture_file_modify_date);
else:
	$picture = 0;
	$picture_file_none = 'no_image'.($picture_file_suffix ? '_'.$picture_file_suffix : '');
	$picture_path = '';
	$picture_path_big = '';
endif;
?>

<? if ( isset($picture_url) && $picture_url && $picture ): ?>

	<div class="image size_<?=$picture_file_suffix?> <?=$picture_file_none?>" <?=($picture_path ? 'style="background-image:url(\'' . $picture_path . '\')"' : '')?>>
		<?=html_helper::anchor($picture_path_big, '<span>'.(isset($name) ? $name : '').'</span>', array('data-role' => 'modal', 'class' => 'image'))?>
	</div>

<? elseif ( isset($user_id) && $user_id ): ?>

	<div class="image size_<?=$picture_file_suffix?> <?=$picture_file_none?>" <?=($picture_path ? 'style="background-image:url(\'' . $picture_path . '\')"' : '')?>>
		<?=html_helper::anchor($slug, '<span>'.$name.'</span>', array('class' => 'image'))?>
	</div>

<? else: ?>

	<div class="image size_<?=$picture_file_suffix?> <?=$picture_file_none?>" <?=($picture_path ? 'style="background-image:url(\'' . $picture_path . '\')"' : '')?>></div>

<? endif;