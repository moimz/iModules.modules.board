<?php
/**
 * 이 파일은 아이모듈 게시판모듈의 일부입니다. (https://www.imodules.io)
 *
 * 게시판모듈 클래스를 정의한다.
 *
 * @file /modules/board/Board.php
 * @author Arzz <arzz@arzz.com>
 * @license MIT License
 * @modified 2024. 2. 14.
 */
namespace modules\board;
class Board extends \Module
{
    /**
     * @var \modules\board\dtos\Board[] $_boards 게시판 정보를 저장한다.
     */
    private static array $_boards = [];

    /**
     * @var \modules\board\dtos\Category[] $_categories 카테고리 정보를 저장한다.
     */
    private static array $_categories = [];

    /**
     * @var \modules\board\dtos\Post[] $_posts 게시물 정보를 저장한다.
     */
    private static array $_posts = [];

    /**
     * 모듈의 컨텍스트 목록을 가져온다.
     *
     * @return array $contexts 컨텍스트목록
     */
    public function getContexts(): array
    {
        $contexts = [];
        $boards = $this->db()
            ->select(['board_id', 'title'])
            ->from($this->table('boards'))
            ->orderBy('board_id', 'ASC')
            ->get();
        foreach ($boards as $board) {
            $contexts[] = ['name' => $board->board_id, 'title' => $board->title . '(' . $board->board_id . ')'];
        }
        return $contexts;
    }

    /**
     * 모듈의 컨텍스트 설정필드를 가져온다.
     *
     * @return array $context 컨텍스트명
     * @return array $fields 설정필드목록
     */
    public function getContextConfigsFields(string $context): array
    {
        $fields = [];
        $template = [
            'name' => 'template',
            'label' => $this->getText('template'),
            'type' => 'template',
            'component' => [
                'type' => 'module',
                'name' => $this->getName(),
                'use_default' => true,
            ],
            'value' => 'default',
        ];
        $fields[] = $template;

        $attachment = [
            'name' => 'attachment',
            'label' => $this->getText('admin.configs.attachment_template'),
            'type' => 'template',
            'component' => [
                'type' => 'module',
                'name' => 'attachment',
                'use_default' => true,
            ],
            'value' => 'default',
        ];
        $fields[] = $attachment;

        return $fields;
    }

    /**
     * 게시판 정보를 가져온다.
     *
     * @param string $board_id 게시판고유값
     * @return \modules\board\dtos\Board $board 게시판정보
     */
    public function getBoard(string $board_id): \modules\board\dtos\Board
    {
        if (isset(self::$_boards[$board_id]) == true) {
            return self::$_boards[$board_id];
        }

        $board = $this->db()
            ->select()
            ->from($this->table('boards'))
            ->where('board_id', $board_id)
            ->getOne();
        if ($board === null) {
            \ErrorHandler::print($this->error('NOT_FOUND_BOARD', $board_id));
        }

        self::$_boards[$board_id] = new \modules\board\dtos\Board($board, $this);
        return self::$_boards[$board_id];
    }

    /**
     * 카테고리 정보를 가져온다.
     *
     * @param int|object $category_id 카테고리고유값
     * @return ?\modules\board\dtos\Category $category 카테고리정보
     */
    public function getCategory(int|object $category_id): ?\modules\board\dtos\Category
    {
        if (is_object($category_id) == true) {
            $category = $category_id;
            $category_id = $category->category_id;
        }

        if (isset(self::$_categories[$category_id]) == true) {
            return self::$_categories[$category_id];
        }

        if (isset($category) == false) {
            $category = $this->db()
                ->select()
                ->from($this->table('categories'))
                ->where('category_id', $category_id)
                ->getOne();
        }

        if ($category === null) {
            self::$_categories[$category_id] = null;
        } else {
            self::$_categories[$category_id] = new \modules\board\dtos\Category($category);
        }

        return self::$_categories[$category_id];
    }

    /**
     * 게시물 정보를 가져온다.
     *
     * @param int|object $post_id 게시물고유값
     * @return ?\modules\board\dtos\Post $post 게시물정보
     */
    public function getPost(int|object $post_id): ?\modules\board\dtos\Post
    {
        if (is_object($post_id) == true) {
            $post = $post_id;
            $post_id = $post->post_id;
        }

        if (isset(self::$_posts[$post_id]) == true) {
            return self::$_posts[$post_id];
        }

        if (isset($post) == false) {
            $post = $this->db()
                ->select()
                ->from($this->table('posts'))
                ->where('post_id', $post_id)
                ->getOne();
        }

        if ($post === null) {
            self::$_posts[$post_id] = null;
        } else {
            self::$_posts[$post_id] = new \modules\board\dtos\Post($post);
        }

        return self::$_posts[$post_id];
    }

    /**
     * 모듈 컨텍스트의 콘텐츠를 가져온다.
     *
     * @param string $board_id 게시판고유값
     * @param ?object $configs 컨텍스트 설정
     * @return string $html
     */
    public function getContent(string $board_id, ?object $configs = null): string
    {
        $board = $this->getBoard($board_id);
        if ($board === null) {
            return \ErrorHandler::get('NOT_FOUND_BOARD', $board_id);
        }

        /**
         * 컨텍스트 템플릿을 설정한다.
         */
        if ($configs?->template == null || $configs?->template == '#') {
            $this->setTemplate($board->getTemplateConfigs());
        } else {
            $this->setTemplate($configs->template);
        }

        $content = '';

        $view = $this->getRouteAt(0) ?? 'list';
        switch ($view) {
            case 'list':
                $content .= $this->getListContent($board_id, $configs);
                break;

            case 'view':
                //				$content.= $this->getViewContext($board_id,$configs);
                break;

            case 'write':
                $content .= $this->getWriteContent($board_id, $configs);
                break;

            default:
                $content .= \ErrorHandler::get($this->error('NOT_FOUND_CONTEXT'));
        }

        return $content;
    }

    /**
     * 게시물 목록 콘텐츠를 가져온다.
     *
     * @param string $board_id 게시판고유값
     * @param ?object $configs 사이트맵 관리를 통해 설정된 페이지 컨텍스트 설정
     * @return string $content 컨텍스트 HTML
     */
    public function getListContent(string $board_id, ?object $configs = null): string
    {
        $board = $this->getBoard($board_id);
        if ($board->checkPermission('LIST') == false) {
            return \ErrorHandler::get($this->error('FORBIDDEN', 'LIST'));
        }

        /**
         * 로봇 메타 설정
         */
        \Html::robots('noindex, follow');

        $category_id = $configs?->category ?? 0;
        $category_id = $category_id === 0 ? null : $category_id;
        $p = null;
        $post_id = null;

        $check = $this->getRouteAt(1);
        if ($check === 'category') {
        } else {
            $p = is_numeric($check) === true && $check > 0 ? intval($check) : 1;
        }

        /**
         * 게시판의 공지사항 출력설정에 따라 공지사항 게시물을 가져온다.
         *
         * INCLUDE : 일반 게시물에 공지사항을 포함하여 페이징처리
         * FIRST : 첫페이지에만 표시
         * ALL : 전체 페이지에 노출
         */
        if ($board->getNoticeType() == 'INCLUDE') {
            /**
             * 현재 페이지에 표시할 공지사항이 있는 경우, 공지사항 게시물을 현재 페이지에 맞게 가져온다.
             */
            if ($board->getLimit('post') * ($p - 1) <= $board->getCount('notice')) {
                /**
                 * 공지사항 게시물 범위
                 */
                $start = $board->getLimit('post') * ($p - 1);
                $limit = min(
                    $board->getLimit('post'),
                    $board->getCount('notice') - $board->getLimit('post') * ($p - 1)
                );
                $notices = $this->db()
                    ->select()
                    ->from($this->table('posts'))
                    ->where('board_id', $board_id)
                    ->where('is_notice', 'TRUE')
                    ->orderBy('created_at', 'desc')
                    ->limit($start, $limit)
                    ->get();

                /**
                 * 일반 게시물을 가져올 범위
                 */
                $start = 0;
                $limit = $board->getLimit('post') - $limit;
            } else {
                $notices = [];

                /**
                 * 일반 게시물을 가져올 범위
                 * 원래 가져와야하는 게시물의 범위에서 공지사항 게시물 갯수만큼 범위를 조절한다.
                 */
                $start = $board->getLimit('post') * ($p - 1) - $board->getCount('notice');
                $limit = $board->getLimit('post');
            }
        } else {
            /**
             * 첫페이지거나, 모든 페이지에 공지사항을 가져오도록 설정된 경우 공지사항 게시물을 가져온다.
             */
            if ($board->getNoticeType() == 'ALL' || $p === 1) {
                $notices = $this->db()
                    ->select()
                    ->from($this->table('posts'))
                    ->where('board_id', $board_id)
                    ->where('is_notice', 'TRUE')
                    ->orderBy('created_at', 'desc')
                    ->get();
            } else {
                $notices = [];
            }

            /**
             * 일반 게시물을 가져올 범위
             */
            $start = ($p - 1) * $board->getLimit('post');
            $limit = $board->getLimit('post');
        }

        /**
         * 일반 게시물을 가져온다.
         */
        $key = \Request::get('key') ?? 'title';
        $keyword = \Request::get('keyword') ?? '';
        $posts = $this->db()
            ->select()
            ->from($this->table('posts'))
            ->where('board_id', $board_id)
            ->where('is_notice', 'FALSE');
        if ($category_id !== null) {
            $posts->where('category_id', $category_id);
        }
        $total = $posts->copy()->count();
        $posts = $posts
            ->limit($start, $limit)
            ->orderBy('created_at', 'desc')
            ->get();

        foreach ($notices as &$notice) {
            $notice = $this->getPost($notice);
        }

        $loopnum = $total - $start;
        foreach ($posts as $idx => &$post) {
            $post = $this->getPost($post);
            $post->setLoopnum($loopnum - $idx);
        }

        /**
         * 페이지 네비게이션을 가져온다.
         * 공지사항 출력방식이 INCLUDE 인 경우, 게시물 갯수에 공지사항 갯수를 더한다.
         */
        if ($board->getNoticeType() == 'INCLUDE') {
            $total = $total + $board->getCount('notice');
        }
        // todo: 페이징 네비게이션 구현

        /**
         * 템플릿파일을 호출한다.
         */
        $header = \Html::tag('<form id="ModuleBoardListForm">');
        $footer = \Html::tag('</form>'); //, '<script>Board.list.init("ModuleBoardListForm");</script>');

        return $this->getTemplate()
            ->assign([
                'board' => $board,
                'notices' => $notices,
                'posts' => $posts,
                'key' => $key,
                'keyword' => $keyword,
                'category_id' => $category_id,
                'post_id' => $post_id,
            ])
            ->getLayout('list', $header, $footer);
    }

    /**
     * 게시물 작성 콘텐츠를 가져온다.
     *
     * @param string $board_id 게시판고유값
     * @param ?object $configs 사이트맵 관리를 통해 설정된 페이지 컨텍스트 설정
     * @return string $content 컨텍스트 HTML
     */
    public function getWriteContent(string $board_id, ?object $configs = null): string
    {
        $board = $this->getBoard($board_id);
        if ($board->checkPermission('POST_WRITE') == false) {
            return \ErrorHandler::get($this->error('FORBIDDEN', 'LIST'));
        }

        /**
         * 로봇 메타 설정
         */
        \Html::robots('noindex, follow');

        $category_id = $configs->category ?? 0;
        $category_id = $category_id === 0 ? null : $category_id;
        $post_id = null;

        $check = $this->getRouteAt(1);
        if (preg_match('/^[0-9]+$/', $check) === true) {
            $post_id = intval($check);
        }

        /**
         * @var \modules\member\Module $mMember 회원모듈
         */
        $mMember = \Modules::get('member');
        $member = $mMember->getMember();

        $post = null;
        if ($post_id !== null) {
            $post = $this->getPost($post_id);
            if (
                $post === null ||
                $post->getBoardId() != $board_id ||
                ($category_id !== null && $post->getCategoryId() !== 0 && $post->getCategoryId() != $category_id)
            ) {
                return \ErrorHandler::get($this->error('NOT_FOUND_POST'));
            }

            if ($board->checkPermission('POST_MODIFY') == false && $post->getAuthor()->getId() != $member->getId()) {
                return \ErrorHandler::get($this->error('FORBIDDEN', 'POST_MODIFY'));
            }
        }

        /**
         * @var \modules\wysiwyg\Module $mWysiwyg 위지윅모듈
         */
        $mWysiwyg = \Modules::get('wysiwyg');
        $wysiwyg = $mWysiwyg->setName('content');

        /**
         * 템플릿파일을 호출한다.
         */
        $header = \Html::tag('<form id="ModuleBoardWriteForm">');
        $footer = \Html::tag('</form>'); //, '<script>Board.write.init("ModuleBoardWriteForm");</script>');

        return $this->getTemplate()
            ->assign([
                'board' => $board,
                'category_id' => $category_id,
                'post_id' => $post_id,
                'post' => $post,
                'member' => $member,
                'wysiwyg' => $wysiwyg,
            ])
            ->getLayout('write', $header, $footer);
    }

    /**
     * 특수한 에러코드의 경우 에러데이터를 현재 클래스에서 처리하여 에러클래스로 전달한다.
     *
     * @param string $code 에러코드
     * @param ?string $message 에러메시지
     * @param ?object $details 에러와 관련된 추가정보
     * @return \ErrorData $error
     */
    public function error(string $code, ?string $message = null, ?object $details = null): \ErrorData
    {
        switch ($code) {
            /**
             * 게시판이 존재하지 않는 경우
             */
            case 'NOT_FOUND_BOARD':
                $error = \ErrorHandler::data();
                $error->message = $this->getErrorText('NOT_FOUND_BOARD', ['board_id' => $message]);
                return $error;

            /**
             * URL 경로가 존재하지 않는 경우
             */
            case 'NOT_FOUND_CONTEXT':
                $error = \ErrorHandler::data();
                $error->message = $this->getErrorText('NOT_FOUND_CONTEXT');
                $error->suffix = $message;
                return $error;

            /**
             * 권한이 부족한 경우, 로그인이 되어 있지 않을 경우, 로그인관련 에러메시지를 표시하고
             * 그렇지 않은 경우 권한이 부족하다는 에러메시지를 표시한다.
             */
            case 'FORBIDDEN':
                $error = \ErrorHandler::data();
                /**
                 * @var ModuleMember $mMember
                 */
                $mMember = \Modules::get('member');
                if ($mMember->isLogged() == true) {
                    $error->prefix = $this->getErrorText('FORBIDDEN');
                    $error->message = $this->getErrorText('FORBIDDEN_DETAIL', [
                        'code' => $this->getErrorText('FORBIDDEN_CODE/' . $message),
                    ]);
                } else {
                    $error->prefix = $this->getErrorText('REQUIRED_LOGIN');
                }
                return $error;

            default:
                return parent::error($code, $message, $details);
        }
    }
}
