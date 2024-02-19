<?php
/**
 * 이 파일은 아이모듈 게시판모듈의 일부입니다. (https://www.imodules.io)
 *
 * 게시물을 등록한다.
 *
 * @file /modules/board/processes/post.post.php
 * @author Arzz <arzz@arzz.com>
 * @license MIT License
 * @modified 2024. 2. 19.
 *
 * @var \modules\board\Board $me
 */
if (defined('__IM_PROCESS__') == false) {
    exit();
}

$board_id = Input::get('board_id') ?? 'UNKNOWN';
$board = $me->getBoard($board_id);
if ($board === null) {
    $results->success = false;
    $results->message = $me->getErrorText('NOT_FOUND_BOARD', ['board_id' => $board_id]);
    return;
}

if ($board->checkPermission('POST_WRITE') == false) {
    $results->success = false;
    $results->message = $me->getErrorText('FORBIDDEN');
    return;
}

/**
 * @var \modules\member\Member $mMember
 */
$mMember = \Modules::get('member');

$post_id = Input::get('post_id');
if ($post_id !== null) {
    $post = $me->getPost($post_id);
    if ($post === null) {
        $results->success = false;
        $results->message = $me->getErrorText('NOT_FOUND_DATA');
        return;
    }

    // @todo 본인 게시글 여부 판단추가
    if ($board->checkPermission('POST_EDIT') == false) {
        $results->success = false;
        $results->message = $me->getErrorText('FORBIDDEN');
        return;
    }
} else {
    $post = null;
}

$errors = [];
$insert = [];

$insert['board_id'] = $board_id;
$insert['title'] = Input::get('title', $errors);
if ($mMember->isLogged() == true) {
    $insert['member_id'] = $mMember->getLogged();
} else {
    $insert['name'] = Input::get('name', $errors);
    $insert['password'] = Password::hash(Input::get('password', $errors) ?? '');
    $insert['email'] = Input::get('email');
}

$insert['is_html_title'] = Input::get('is_html_title') ? 'TRUE' : 'FALSE';
$insert['is_notice'] = Input::get('is_notice') ? 'TRUE' : 'FALSE';
$insert['is_anonymity'] = Input::get('is_anonymity') ? 'TRUE' : 'FALSE';
$insert['is_secret'] = Input::get('is_secret') ? 'TRUE' : 'FALSE';

$content = Input::get('content', $errors);

if (count($errors) > 0) {
    $results->success = false;
    $results->errors = $errors;
    return;
}

/**
 * @var \modules\wysiwyg\Wysiwyg $mWysiwyg
 */
$mWysiwyg = Modules::get('wysiwyg');
$content = $mWysiwyg->getEditorContent(Input::get('content'), $me, 'post', $post_id ?? 'new');

$time = time();
$insert['content'] = $content->getContent();
$insert['search'] = Format::string($content->getContent(), 'search');
$insert['updated_at'] = $time;
$insert['updated_by'] = $mMember->getLogged();
$insert['files'] = count($content->getAttachments());

if ($post == null) {
    $insert['ip'] = $_SERVER['REMOTE_ADDR'];
    $insert['created_at'] = $time;

    $me->db()
        ->insert($me->table('posts'), $insert)
        ->execute();

    $post_id = $me->db()->getInsertId();
} else {
    $me->db()
        ->update($me->table('posts'), $insert)
        ->where('post_id', $post_id)
        ->execute();
}

/**
 * @var \modules\attachment\Attachment $mAttachment
 */
$mAttachment = Modules::get('attachment');
$mAttachment->publishFiles($content->getAttachments(), $me, 'post', $post_id);

$results->success = true;
$results->post_id = $post_id;
