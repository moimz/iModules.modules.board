<?php
/**
 * 이 파일은 아이모듈 게시판모듈의 일부입니다. (https://www.imodules.io)
 *
 * 게시판모듈 클래스를 정의한다.
 *
 * @file /modules/board/Board.php
 * @author Arzz <arzz@arzz.com>
 * @license MIT License
 * @modified 2024. 2. 25.
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
    public function getContext(string $board_id, ?object $configs = null): string
    {
        $board = $this->getBoard($board_id);
        if ($board === null) {
            return \ErrorHandler::get($this->error('NOT_FOUND_BOARD', $board_id));
        }

        /**
         * 컨텍스트 템플릿을 설정한다.
         */
        if (isset($configs?->template) == true && $configs->template->name !== '#') {
            $this->setTemplate($configs->template);
        } else {
            $this->setTemplate($board->getTemplateConfigs());
        }

        $content = '';

        $view = $this->getRouteAt(0) ?? 'list';
        switch ($view) {
            case 'list':
                $content .= $this->getListContent($board_id, $configs);
                break;

            case 'view':
                $content .= $this->getViewContext($board_id, $configs);
                break;

            case 'write':
                $content .= $this->getWriteContext($board_id, $configs);
                break;

            default:
                $content .= \ErrorHandler::get($this->error('NOT_FOUND_CONTEXT'));
        }

        return $this->getTemplate()->getLayout($content);
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
         * 모든 페이지에 고정되는 공지사항을 가져온다.
         */
        $notices = $this->db()
            ->select()
            ->from($this->table('posts'))
            ->where('board_id', $board_id)
            ->where('is_notice', 'FIXED')
            ->orderBy('created_at', 'desc')
            ->get();
        foreach ($notices as &$notice) {
            $notice = $this->getPost($notice)->setUrl(\Router::get()->getUrl() . '/view/' . $notice->post_id);
        }

        $start = ($p - 1) * $board->getLimit('post');
        $limit = $board->getLimit('post');

        /**
         * 일반 게시물을 가져온다.
         */
        $key = \Request::get('key') ?? 'title';
        $keyword = \Request::get('keyword') ?? '';
        $posts = $this->db()
            ->select()
            ->from($this->table('posts'))
            ->where('board_id', $board_id)
            ->where('is_notice', 'FIXED', '!=');
        if ($category_id !== null) {
            $posts->where('category_id', $category_id);
        }
        $total = $posts->copy()->count();
        $posts = $posts
            ->limit($start, $limit)
            ->orderBy('is_notice', 'asc')
            ->orderBy('created_at', 'desc')
            ->get();

        $loopnum = $total - $start;
        foreach ($posts as $idx => &$post) {
            $post = $this->getPost($post)
                ->setLoopnum($loopnum - $idx)
                ->setUrl(\Router::get()->getUrl() . '/view/' . $post->post_id);
        }
        // todo: 페이징 네비게이션 구현

        /**
         * 템플릿파일을 호출한다.
         */
        $header = \Html::tag('<form id="ModuleBoardListForm">');
        $footer = \Html::tag('</form>');

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
            ->getContext('list', $header, $footer);
    }

    /**
     * 게시물 보기 콘텐츠를 가져온다.
     *
     * @param string $board_id 게시판고유값
     * @param ?object $configs 사이트맵 관리를 통해 설정된 페이지 컨텍스트 설정
     * @return string $content 컨텍스트 HTML
     */
    public function getViewContext(string $board_id, ?object $configs = null): string
    {
        $board = $this->getBoard($board_id);
        if ($board->checkPermission('VIEW') == false) {
            return \ErrorHandler::get($this->error('FORBIDDEN', 'VIEW'));
        }

        $post_id = $this->getRouteAt(1);
        if ($post_id == null) {
            return \ErrorHandler::get($this->error('NOT_FOUND_URL'));
        }

        $post = $this->getPost($post_id);
        if ($post == null) {
            return \ErrorHandler::get($this->error('NOT_FOUND_POST'));
        }

        /**
         * 메타 설정
         */
        \Html::title($post->getTitle());
        // @todo 이미지, 설명, OG 태그 등

        $header = $footer = '';

        return $this->getTemplate()
            ->assign([
                'board' => $board,
                'post_id' => $post_id,
                'post' => $post,
            ])
            ->getContext('view', $header, $footer);
    }

    /**
     * 게시물 작성 콘텐츠를 가져온다.
     *
     * @param string $board_id 게시판고유값
     * @param ?object $configs 사이트맵 관리를 통해 설정된 페이지 컨텍스트 설정
     * @return string $content 컨텍스트 HTML
     */
    public function getWriteContext(string $board_id, ?object $configs = null): string
    {
        $board = $this->getBoard($board_id);
        if ($board->checkPermission('POST_WRITE') == false) {
            return \ErrorHandler::get($this->error('FORBIDDEN', 'POST_WRITE'));
        }

        /**
         * 로봇 메타 설정
         */
        \Html::robots('noindex, follow');

        $category_id = $configs->category ?? 0;
        $category_id = $category_id === 0 ? null : $category_id;
        $post_id = null;

        $post_id = $this->getRouteAt(1);
        if ($post_id !== null) {
            $post = $this->getPost($post_id);
        } else {
            $post = null;
        }

        /**
         * @var \modules\member\Member $mMember
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

            // @todo 작성자 권한 확인
            if ($board->checkPermission('POST_EDIT') == false) {
                return \ErrorHandler::get($this->error('FORBIDDEN', 'POST_EDIT'));
            }
        }

        /**
         * @var \modules\attachment\Attachment $mAttachment
         */
        $mAttachment = \Modules::get('attachment');
        $uploader = $mAttachment->getUploader()->setName('attachments');

        /**
         * @var \modules\wysiwyg\Wysiwyg $mWysiwyg
         */
        $mWysiwyg = \Modules::get('wysiwyg');
        $editor = $mWysiwyg
            ->getEditor()
            ->setName('content')
            ->setContent($post?->getEditorContent() ?? null)
            ->setUploader($uploader);

        /**
         * 템플릿파일을 호출한다.
         */
        $header = \Html::tag(
            '<form name="ModuleBoardWriteForm" autosave="true">',
            '<input type="hidden" name="board_id" value="' . $board_id . '">'
        );
        $footer = \Html::tag('</form>');

        return $this->getTemplate()
            ->assign([
                'board' => $board,
                'category_id' => $category_id,
                'post_id' => $post_id,
                'post' => $post,
                'member' => $member,
                'editor' => $editor,
            ])
            ->getContext('write', $header, $footer);
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
                $error = \ErrorHandler::data($code, $this);
                $error->message = $this->getErrorText('NOT_FOUND_BOARD', ['board_id' => $message]);
                return $error;

            /**
             * URL 경로가 존재하지 않는 경우
             */
            case 'NOT_FOUND_CONTEXT':
                $error = \ErrorHandler::data($code, $this);
                $error->message = $this->getErrorText('NOT_FOUND_CONTEXT');
                $error->suffix = $message;
                return $error;

            /**
             * 권한이 부족한 경우, 로그인이 되어 있지 않을 경우, 로그인관련 에러메시지를 표시하고
             * 그렇지 않은 경우 권한이 부족하다는 에러메시지를 표시한다.
             */
            case 'FORBIDDEN':
                $error = \ErrorHandler::data($code, $this);
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
