<?php
/**
 * 이 파일은 코스모스 웹사이트 템플릿의 일부입니다. (https://www.coursemos.kr)
 *
 * 게시판 공지사항 템플릿 - 게시물 작성
 * 
 * @file /modules/naddle/templets/modules/board/notice/write.php
 * @author Arzz (arzz@arzz.com)
 * @license MIT License
 * @version 3.0.0
 * @modified 2021. 12. 10.
 */
if (defined('__IM__') == false) exit;
?>
<ul data-role="form" class="black inner">
	<?php if ($member->isLogged() == false) { ?>
	<li>
		<label><?php echo $me->getText('text/name'); ?></label>
		<div>
			<div data-role="input">
				<input type="text" name="name" value="<?php echo Format::string($post?->name,'input'); ?>">
			</div>
		</div>
	</li>
	<li>
		<label><?php echo $me->getText('text/password'); ?></label>
		<div>
			<div data-role="input" data-default="<?php echo $me->getText('text/password_help'); ?>">
				<input type="password" name="password">
			</div>
		</div>
	</li>
	<?php } ?>
	<?php if (count($categories) > 0) { ?>
	<li>
		<label><?php echo $me->getText('text/category'); ?></label>
		<div>
			<div data-role="input">
				<select name="category">
					<?php for ($i=0, $loop=count($categories);$i<$loop;$i++) { ?>
					<option value="<?php echo $categories[$i]->category_id; ?>"<?php echo $post?->category_id == $categories[$i]->category_id ? ' selected="selected"' : ''; ?>><?php echo $categories[$i]->title; ?></option>
					<?php } ?>
				</select>
			</div>
		</div>
	</li>
	<?php } ?>
	<li>
		<label><?php echo $me->getText('text/title'); ?></label>
		<div>
			<?php if (count($prefixes) == 0) { ?>
			<div data-role="input">
				<input type="text" name="title" value="<?php echo Format::string($post?->title,'input'); ?>">
			</div>
			<?php } else { ?>
			<div data-role="inputset" class="flex">
				<div data-role="input">
					<select name="prefix">
						<option value="0">선택안함</option>
						<?php for ($i=0, $loop=count($prefixes);$i<$loop;$i++) { ?>
						<option value="<?php echo $prefixes[$i]->prefix_id; ?>"<?php echo $post?->prefix == $prefixes[$i]->prefix_id ? ' selected="selected"' : ''; ?>><?php echo $prefixes[$i]->title; ?></option>
						<?php } ?>
					</select>
				</div>
				
				<div data-role="input">
					<input type="text" name="title" value="<?php echo Format::string($post?->title,'input'); ?>">
				</div>
			</div>
			<?php } ?>
		</div>
	</li>
	<li>
		<div data-role="input">
			<?php $wysiwyg->doLayout(); ?>
			<?php //$uploader->doLayout(); ?>
		</div>
	</li>
	<?php if ($me->checkPermission($board->board_id,'notice') == true || $board->allow_secret == true || $board->allow_anonymity == true) { ?>
	<li>
		<label><?php echo $me->getText('text/post_option'); ?></label>
		<div>
			<?php if ($me->checkPermission($board->board_id,'notice') == true) { ?>
			<div data-role="input">
				<label><input type="checkbox" name="is_notice" value="TRUE"<?php echo $post?->is_notice == 'TRUE' ? ' checked="checked"' : ''; ?>><?php echo $me->getText('post_option/notice'); ?></label>
			</div>
			<?php } ?>
			
			<?php if ($board->allow_secret == true) { ?>
			<div data-role="input">
				<label><input type="checkbox" name="is_secret" value="TRUE"<?php echo $post?->is_secret == 'TRUE' ? ' checked="checked"' : ''; ?>><?php echo $me->getText('post_option/secret'); ?></label>
			</div>
			<?php } ?>
			
			<?php if ($board->allow_anonymity == true) { ?>
			<div data-role="input">
				<label><input type="checkbox" name="is_anonymity" value="TRUE"<?php echo $post?->is_anonymity == 'TRUE' ? ' checked="checked"' : ''; ?>><?php echo $me->getText('post_option/anonymity'); ?></label>
			</div>
			<?php } ?>
			
			<input type="hidden" name="field1" value="<?php echo $post->field1 == 'TRUE' ? 'TRUE' : 'FALSE'; ?>">
			<div data-role="input">
				<label><input type="checkbox" name="field1_checked" value="TRUE"<?php echo $post?->field1 == 'TRUE' ? ' checked="checked"' : ''; ?>>고객문의 페이지에 공지사항으로 표시합니다.</label>
			</div>
		</div>
	</li>
	<?php } ?>
</ul>

<div data-role="button">
	<a href="<?php echo $me->getRouteUrl('list'); ?>"><?php echo $me->getText('button/cancel'); ?></a>
	<button type="submit"><?php echo $me->getText('button/post_write'); ?></button>
</div>

<script>
$("input[type=checkbox][name=field1_checked]").on("change",function() {
	$("input[type=hidden][name=field1]").val($(this).checked() == true ? "TRUE" : "FALSE");
});
</script>