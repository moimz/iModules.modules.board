<?php
/**
 * 이 파일은 아이모듈 게시판모듈의 일부입니다. (https://www.imodules.io)
 *
 * 게시판 카테고리 구조체를 정의한다.
 *
 * @file /modules/board/dtos/Category.php
 * @author Arzz <arzz@arzz.com>
 * @license MIT License
 * @modified 2024. 2. 14.
 */
namespace modules\board\dtos;
class Category
{
    /**
     * @var object $_category 카테고리정보
     */
    private object $_category;

    /**
     * @var int $_id 카테고리고유값
     */
    private int $_id;

    /**
     * @var string $_board_id 게시판 고유값
     */
    private string $_board_id;

    /**
     * @var string $_title 카테고리명
     */
    private string $_title;

    /**
     * @var int $_post 카테고리에 속한 게시물수
     */
    private int $_post_count;

    /**
     * @var int $_latest_post_at 마지막 게시물 등록일시
     */
    private int $_latest_post_at;

    /**
     * 카테고리 구조체를 정의한다.
     *
     * @param object $category 카테고리정보
     */
    public function __construct(object $category)
    {
        $this->_category = $category;

        $this->_id = intval($category->category_id);
        $this->_board_id = $category->board_id;
        $this->_title = $category->title;
        $this->_post_count = intval($category->post_count);
        $this->_latest_post_at = intval($category->latest_post_at);
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
     * 카테고리명을 가져온다.
     *
     * @return string 카테고리명
     */
    public function getTitle(): string
    {
        return $this->_title;
    }
}
