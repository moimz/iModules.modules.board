<?php
/**
 * 이 파일은 아이모듈 게시판모듈의 일부입니다. (https://www.imodules.io)
 *
 * 작성자 구조체를 정의한다.
 *
 * @file /modules/board/dtos/Author.php
 * @author Arzz <arzz@arzz.com>
 * @license MIT License
 * @modified 2024. 2. 19.
 */
namespace modules\board\dtos;
class Author
{
    /**
     * @var int $_member_id 회원고유값
     */
    private int $_member_id;

    /**
     * @var string $_ip 작성자IP
     */
    private string $_ip;

    /**
     * @var ?string $_name 작성자명
     */
    private ?string $_name;

    /**
     * @var ?string $_email 이메일
     */
    private ?string $_email;

    /**
     * @var bool $_is_anonymity 익명여부
     */
    private bool $_is_anonymity;

    /**
     * 작성자 구조체를 정의한다.
     *
     * @param int $member_id 작성자회원고유값
     * @param string $ip 작성자IP
     * @param ?string $name 작성자명
     * @param ?string $email 이메일
     * @param bool $is_anonymity 익명여부
     */
    public function __construct(int $member_id, string $ip, ?string $name, ?string $email, bool $is_anonymity)
    {
        $this->_member_id = $member_id;
        $this->_ip = $ip;
        $this->_name = $name;
        $this->_email = $email;
        $this->_is_anonymity = $is_anonymity;
    }

    /**
     * 작성자명을 가져온다.
     *
     * @return string $name
     */
    public function getName(): string
    {
        if ($this->_member_id == 0) {
            return $this->_name ?? 'NONAME';
        } else {
            /**
             * @var \modules\member\Member $mMember
             */
            $mMember = \Modules::get('member');

            return $mMember->getMember($this->_member_id)->getDisplayName(false);
        }
    }

    /**
     * 작성자명을 가져온다.
     *
     * @return string $name
     */
    public function getEmail(): string
    {
        return $this->_email ?? '';
    }

    /**
     * 회원여부를 확인한다.
     *
     * @return bool $is_member
     */
    public function isMember(): bool
    {
        return $this->_member_id > 0;
    }

    /**
     * 작성자 네임태그를 가져온다.
     *
     * @return string $nameTag
     */
    public function getNameTag(): string
    {
        /**
         * @var \modules\member\Member $mMember
         */
        $mMember = \Modules::get('member');

        if ($this->_member_id > 0 && $this->_is_anonymity == false) {
            return $mMember->getMember($this->_member_id)->getNameTag();
        } else {
            $photo = $mMember->getMemberPhoto(0);

            /**
             * 익명인 경우 익명아이콘을 가져온다.
             */
            if ($this->_is_anonymity == true) {
                $icons = ['icon_man.png', 'icon_woman.png'];
                $photo = \Modules::get('board')->getDir() . '/images/' . $icons[ip2long($this->_ip) % 2];
            }

            $photo = \Html::element(
                'i',
                ['data-role' => 'photo', 'style' => 'background-image:url(' . $photo . ')'],
                '<b data-anonymity="' . ($this->_is_anonymity == true ? 'true' : 'false') . '"></b>'
            );

            $nametag = \Html::element(
                'span',
                [
                    'data-module' => 'member',
                    'data-role' => 'name',
                    'data-member-id' => 0,
                    'data-menu' => 'false',
                ],
                $photo . '<b class="guest">' . ($this->_name ?? 'NONAME') . '</b>'
            );

            return $nametag;
        }
    }
}
