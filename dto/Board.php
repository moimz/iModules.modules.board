<?php
/**
 * 이 파일은 아이모듈 게시판모듈의 일부입니다. (https://www.imodules.io)
 *
 * 게시판 구조체를 정의한다.
 *
 * @file /modules/board/dto/Board.php
 * @author Arzz <arzz@arzz.com>
 * @license MIT License
 * @modified 2022. 12. 1.
 */
namespace modules\board\dto;
class Board
{
    /**
     * @var object $_board 게시판정보
     */
    private object $_board;

    /**
     * @var string $_id 게시판고유값
     */
    private string $_id;

    /**
     * @var string $title 게시판명
     */
    private string $_title;

    /**
     * @var object $_templet 게시판 설정에 따른 템플릿 정보
     */
    private object $_templet;

    /**
     * @var string 카테고리 사용여부 (NONE : 사용안함, USED : 사용함, FORCE : 항상 사용함)
     */
    private string $_category_type;

    /**
     * @var dto\Category[] $_categories 게시판 카테고리
     */
    private array $_categories;

    /**
     * @var dto\Prefix[] $_prefixes 게시판 카테고리
     */
    private array $_prefixes;

    /**
     * @var int $_post_limit 페이지당 게시물수
     */
    private int $_post_limit;

    /**
     * @var int $_ment_limit 페이지당 댓글수
     */
    private int $_ment_limit;

    /**
     * @var int $_page_limit 페이지네비게이션당 페이지수
     */
    private int $_page_limit;

    /**
     * @var string $_notice_type 공지사항 형태 (INCLUDE : 일반 게시물에 공지사항을 포함하여 페이징처리, FIRST : 첫페이지에만 표시, ALL : 전체 페이지에 노출)
     */
    private string $_notice_type;

    /**
     * @var int $_post_count 게시물수
     */
    private int $_post_count;

    /**
     * @var int $_ment_count 게시물수
     */
    private int $_ment_count;

    /**
     * @var int $_notice_count 공지사항수
     */
    private int $_notice_count;

    /**
     * 게시판 구조체를 정의한다.
     *
     * @param object $board 게시판정보
     */
    public function __construct(object $board)
    {
        $this->_board = $board;

        $this->_id = $board->board_id;
        $this->_title = $board->title;
        $this->_templet = json_decode($board->templet);

        $this->_category_type = $board->category_type;

        $this->_post_limit = $board->post_limit;
        $this->_ment_limit = $board->ment_limit;
        $this->_page_limit = $board->page_limit;

        $this->_notice_type = $board->notice_type;
        $this->_notice_category = $board->notice_category;

        $this->_post_count = $board->post_count;
        $this->_ment_count = $board->ment_count;
        $this->_notice_count = $board->notice_count;
        /*
        $this->wysiwyg = json_decode($board->wysiwyg);
        $this->attachment = json_decode($board->attachment);
        */
    }

    /**
     * 고유값을 가져온다.
     *
     * @return string $board_id
     */
    public function getId(): string
    {
        return $this->_id;
    }

    /**
     * 게시판명을 가져온다.
     *
     * @return string $title
     */
    public function getTitle(): string
    {
        return $this->_title;
    }

    /**
     * 게시판 기본 템플릿설정을 가져온다.
     *
     * @return \Templet $templet
     */
    public function getTempletConfigs(): object
    {
        return $this->_templet;
    }

    /**
     * 게시판 카테고리 사용여부를 가져온다.
     *
     * @return string $category_type (NONE : 사용안함, USED : 사용함, FORCE : 항상 사용함)
     */
    public function getCategoryType(): string
    {
        return $this->_category_type;
    }

    /**
     * 게시판 카테고리를 가져온다.
     *
     * @return Category[] $categories
     */
    public function getCategories(): array
    {
        if (isset($this->_categories) == true) {
            return $this->_categories;
        }

        /**
         * @var \modules\board\Board $mBoard
         */
        $mBoard = \Modules::get('board');

        if ($this->_category_type != 'NONE') {
            $categories = $mBoard
                ->db()
                ->select()
                ->from($mBoard->table('categories'))
                ->where('board_id', $this->_id)
                ->orderBy('sort', 'asc')
                ->get();

            $this->_categories = [];
            foreach ($categories as $category) {
                $this->_categories[] = $mBoard->getCategory($category);
            }
        } else {
            $this->_categories = [];
        }

        return $this->_categories;
    }

    /**
     * 게시판 말머리를 가져온다.
     *
     * @return Prefix[] $prefixes
     */
    public function getPrefixes(): array
    {
        if (isset($this->_prefixes) == true) {
            return $this->_prefixes;
        }

        $this->_prefixes = [];
        return $this->_prefixes;
    }

    /**
     * 공지사항 형태를 가져온다.
     *
     * @return string $notice_type (INCLUDE : 일반 게시물에 공지사항을 포함하여 페이징처리, FIRST : 첫페이지에만 표시, ALL : 전체 페이지에 노출)
     */
    public function getNoticeType(): string
    {
        return $this->_notice_type;
    }

    /**
     * 갯수 한계를 가져온다.
     *
     * @param string $type (post : 페이지당 게시물수, ment : 페이지당 댓글수, page : 페이지네비게이션당 페이지수)
     * @return int $limit
     */
    public function getLimit(string $type): int
    {
        switch ($type) {
            case 'post':
                return $this->_post_limit;

            case 'ment':
                return $this->_ment_limit;

            case 'page':
                return $this->_page_limit;

            default:
                return 0;
        }
    }

    /**
     * 특정 데이터의 갯수를 가져온다.
     *
     * @param string $type (post : 게시물수, ment : 댓글수, notice : 공지사항수)
     * @return int $count
     */
    public function getCount(string $type): int
    {
        switch ($type) {
            case 'post':
                return $this->_post_count;

            case 'ment':
                return $this->_ment_count;

            case 'page':
                return $this->_page_count;

            default:
                return 0;
        }
    }

    /**
     * 게시판 URL 을 가져온다.
     *
     * @param string|int ...$paths 모듈 URL 에 추가할 내부 경로 (없는 경우 모듈 기본 URL만 가져온다.)
     * @return string $url
     */
    public function getUrl(string|int ...$paths): string
    {
        /**
         * 현재 컨텍스트가 해당 게시판의 컨텍스트인 경우 컨텍스트 URL을 활용한다.
         */
        $context = \Contexts::get();
        if ($context->is('MODULE', 'board', $this->_id) == true) {
            $url = $context->getUrl();
        } else {
            // 게시판이 포함된 컨텍스트를 검색한다.
            $context = \Contexts::findOne('MODULE', 'board', $this->_id, [], ['category' => 0], false);
            $url = $context == null ? '/' : $context->getUrl();
        }

        if (count($paths) > 0) {
            $url .= '/' . implode('/', $paths);
        }

        return $url;
    }

    /**
     * 게시판의 특정 기능에 대한 수행권한을 가지고 있는지 확인한다.
     *
     * @param string $code 확인할 기능코드
     * @param ?int $member_id 권한을 확인할 회원고유값 (NULL 인 경우 현재 로그인한 사용자)
     * @return bool $has_permission 권한보유여부
     */
    public function checkPermission(string $code, ?int $member_id = null): bool
    {
        // todo: 권한처리
        return true;
        return false;
    }
}
