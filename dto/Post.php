<?php
/**
 * 이 파일은 아이모듈 게시판모듈의 일부입니다. (https://www.imodules.io)
 *
 * 게시판 게시물 구조체를 정의한다.
 *
 * @file /modules/board/dto/Post.php
 * @author Arzz <arzz@arzz.com>
 * @license MIT License
 * @modified 2022. 12. 1.
 */
namespace modules\board\dto;
class Post
{
    /**
     * @var object $_post 게시물정보
     */
    private object $_post;

    /**
     * @var int $_id 게시물고유값
     */
    private int $_id;

    /**
     * @var string $_board_id 게시판고유값
     */
    private string $_board_id;

    /**
     * @var int $_category_id 카테고리고유값
     */
    private int $_category_id;

    /**
     * @var int $_prefix_id 말머리고유값
     */
    private int $_prefix_id;

    /**
     * @var string $title 게시물 제목
     */
    private string $_title;

    /**
     * @var int $_member_id 작성자 회원고유값
     */
    private int $_member_id;

    /**
     * @var \modules\member\dto\Member 작성자
     */
    private \modules\member\dto\Member $_author;

    /**
     * @var int $_ment_count 댓글수
     */
    private int $_ment_count;

    /**
     * @var int $_latest_ment_at 마지막 댓글 작성일시
     */
    private int $_latest_ment_at;

    /**
     * @var int $_good 추천수
     */
    private int $_good;

    /**
     * @var int $_bad 비추천수
     */
    private int $_bad;

    /**
     * @var int $_file_count 첨부파일수
     */
    private int $_file_count;

    /**
     * @var int $_created_at 게시물 등록일시
     */
    private int $_created_at;

    /**
     * @var int $_loopnum 게시물 목록에서의 순번
     */
    private int $_loopnum;

    /**
     * 게시물 구조체를 정의한다.
     *
     * @param object $post 게시물정보
     */
    public function __construct(object $post)
    {
        $this->_id = intval($post->post_id);
        $this->_board_id = $post->board_id;
        $this->_category_id = $post->category_id;
        $this->_prefix_id = $post->prefix_id;

        $this->_title = $post->title;
        $this->_member_id = $post->member_id;

        $this->_ment_count = intval($post->ment_count);
        $this->_latest_ment_at = intval($post->latest_ment_at);
        $this->_good = intval($post->good);
        $this->_bad = intval($post->bad);

        $this->_file_count = intval($post->file_count);

        $this->_created_at = intval($post->created_at);
    }

    /**
     * 고유값을 가져온다.
     *
     * @return int $id
     */
    public function getId(): int
    {
        return $this->_id;
    }

    /**
     * 게시판 고유값을 가져온다.
     *
     * @return string $board_id
     */
    public function getBoardId(): string
    {
        return $this->_board_id;
    }

    /**
     * 카테고리 고유값을 가져온다.
     *
     * @return int $categry_id
     */
    public function getCategoryId(): int
    {
        return $this->_category_id;
    }

    /**
     * 말커리 고유값을 가져온다.
     *
     * @return int $prefix_id
     */
    public function getPrefixId(): int
    {
        return $this->_prefix_id;
    }

    /**
     * 게시물 제목을 가져온다.
     *
     * @return string $title
     */
    public function getTitle(): string
    {
        return $this->_title;
    }

    /**
     * 작성자 정보를 가져온다.
     *
     * @return \modules\member\dto\Member $author
     */
    public function getAuthor(): \modules\member\dto\Member
    {
        if (isset($this->_author) == true) {
            return $this->_author;
        }

        /**
         * @var \modules\member\Member $mMember 회원모듈
         */
        $mMember = \Modules::get('member');
        $this->_author = $mMember->getMember($this->_member_id);

        if ($this->_member_id == 0) {
        }

        return $this->_author;
    }

    /**
     * 게시물 등록일시를 가져온다.
     *
     * @return int $created_at;
     */
    public function getCreatedAt(): int
    {
        return $this->_created_at;
    }

    /**
     * 특정 데이터의 갯수를 가져온다.
     *
     * @param string $type (ment : 댓글수, file : 첨부파일수, good : 추천수, bad : 비추천수, vote : 추천수합)
     * @return int $count
     */
    public function getCount(string $type): int
    {
        switch ($type) {
            case 'ment':
                return $this->_ment_count;

            case 'good':
                return $this->_good;

            case 'bad':
                return $this->_bad;

            case 'vote':
                return $this->_good - $this->_bad;
        }

        return 0;
    }

    /**
     * 마지막 댓글 작성일시를 가져온다.
     *
     * @return int $latest_ment_at
     */
    public function getLatestMentAt(): int
    {
        return $this->_latest_ment_at;
    }

    /**
     * 첨부파일이 존재하는지 확인한다.
     *
     * @return bool $has_file
     */
    public function hasFile(): bool
    {
        return $this->_file_count > 0;
    }

    /**
     * 목록에서의 순번은 상황에 따라 변경되기 때문에 목록 페이지를 가져올 때 목록순번을 지정한다.
     *
     * @param int $loopnum
     * @return Post $this
     */
    public function setLoopnum(int $loopnum): Post
    {
        $this->_loopnum = $loopnum;
        return $this;
    }

    /**
     * 목록에서의 순번은 상황에 따라 변경되기 때문에 목록 페이지를 가져올 때 목록순번을 지정한다.
     *
     * @return int $loopnum
     */
    public function getLoopnum(): int
    {
        return $this->_loopnum ?? 0;
    }

    /**
     * 게시물 URL 을 가져온다.
     *
     * @return string $url
     */
    public function getUrl(): string
    {
        /**
         * 게시물의 카테고리가 없는 경우, 게시판 URL 을 통해 게시물 URL 을 가져오고,
         * 카테고리가 있는 경우 해당 카테고리를 가진 컨텍스트가 존재여부를 확인한다.
         */
        if ($this->_category_id == 0) {
            /**
             * @var \modules\board\module $mBoard 게시판모듈
             */
            $mBoard = \Modules::get('board');
            return $mBoard->getBoard($this->_board_id)->getUrl('view', $this->_id);
        } else {
            /**
             * 현재 컨텍스트가 해당 게시판의 컨텍스트인 경우 컨텍스트 URL을 활용한다.
             */
            $context = \Contexts::get();
            if ($context->is('MODULE', 'board', $this->_id) == true) {
                $url = $context->getUrl();
            } else {
                // 게시판이 포함된 컨텍스트를 검색한다.
                $context = \Contexts::findOne(
                    'MODULE',
                    'board',
                    $this->_board_id,
                    [],
                    ['category' => $this->_category_id],
                    false
                );
                $url = $context == null ? '/' : $context->getUrl();
            }

            return $url . '/view/' . $this->_id;
        }
    }
}
