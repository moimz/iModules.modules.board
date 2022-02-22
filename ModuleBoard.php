<?php
/**
 * 이 파일은 아이모듈 게시판모듈의 일부입니다. (https://www.imodules.io)
 *
 * 게시판모듈 클래스를 정의한다.
 *
 * @file /modules/board/ModuleBoard.php
 * @author Arzz <arzz@arzz.com>
 * @license MIT License
 * @modified 2022. 2. 9.
 */
class ModuleBoard extends Module {
	/**
	 * 게시판 정보를 저장한다.
	 */
	private static array $_boards = [];
	
	/**
	 * 게시물 정보를 저장한다.
	 */
	private static array $_posts = [];
	
	
	/**
	 * 게시판정보를 가져온다.
	 *
	 * @param string $board_id 게시판고유값
	 * @return ?object $board 게시판정보
	 */
	public function getBoard(string $board_id):?object {
		if (isset(self::$_boards[$board_id]) == true) return self::$_boards[$board_id];
		
		$board = $this->db()->select()->from($this->table('boards'))->where('board_id',$board_id)->getOne();
		if ($board === null) {
			self::$_boards[$board_id] = null;
			return null;
		}
		
		$board->templet_configs = json_decode($board->templet_configs);
		$board->use_prefix = $board->use_prefix == 'TRUE';
		
		self::$_boards[$board_id] = $board;
		return self::$_boards[$board_id];
	}
	
	/**
	 * 게시물정보를 가져온다.
	 *
	 * @param int|object $post_id 게시물고유값 또는 게시물정보
	 * @return ?object $post 게시물정보
	 */
	public function getPost(int|object $post_id,bool $is_link=false):?object {
		if (is_numeric($post_id) == true) {
			if (isset(self::$_posts[$post_id]) == true) {
				$post = self::$_posts[$post_id];
			} else {
				$post = $this->db()->select()->from($this->table('posts'))->where('post_id',$post_id)->getOne();
			}
		} else {
			$post = $post_id;
		}
		
		if ($post === null) return null;
		
		/**
		 * 이미 게시물정보가 처리되어 있다면, 해당 정보를 반환한다.
		 */
		if (isset($post->is_rendered) == true && $post->is_rendered == true) {
			if ($is_link == false || isset($post->link) == true) {
				return $post;
			} else {
				$url = iModules::getInstance()->getContextUrl('MODULE','board',$post->board_id);
				$post->link = $url === null ? '#' : $url.'/view/'.$post->post_id;
				return $post;
			}
		}
		
		$post->member = Modules::get('member')->getMember($post->member_id);
		$post->name = $post->member?->name ?? $post->name;
		$post->photo = Modules::get('member')->getMemberPhoto($post->member_id);
		
		$url = iModules::getInstance()->getContextUrl('MODULE','board',$post->board_id,null,['category'=>$post->category_id]);
		$post->link = $url === null ? '#' : $url.'/view/'.$post->post_id;
		
		$post->image = null;//$post->image > 0 ? $this->IM->getModule('attachment')->getFileInfo($post->image) : null;
		if (isset($post->content) == true) {
//			$post->content = $this->IM->getModule('wysiwyg')->decodeContent($post->content);
		}
		
		$post->is_html_title = $post->is_html_title === 'TRUE';
		$post->is_secret = $post->is_secret == 'TRUE';
		$post->is_anonymity = $post->is_anonymity == 'TRUE';
		$post->is_notice = $post->is_notice == 'TRUE';
		$post->is_file = $post->file > 0;
		$post->is_image = $post->image != null || $post->image_url;
		
		$post->category = $post->category_id == 0 ? null : null;
		
		if ($post->is_html_title === false) $post->title = Format::string($post->title,'replace');
		
		/*
		if ($post->is_anonymity == true) {
			$post->name = $post->nickname = '<span data-module="member" data-role="name">익명-'.strtoupper(substr(base_convert(ip2long($post->ip),10,32),0,6)).'</span>';
			$post->photo = '<i data-module="member" data-role="photo" style="background-image:url('.$this->getModule()->getDir().'/images/icon_'.(ip2long($post->ip) % 2 == 0 ? 'man' : 'woman').'.png);"></i>';
		}
		*/
		$post->is_rendered = true;

		self::$_posts[$post->post_id] = $post;
		return self::$_posts[$post->post_id];
	}
	
	/**
	 * 컨텍스트 설정과 게시판고유값으로 게시판의 템플릿 정보를 가져온다.
	 *
	 * @param string $board_id 게시판고유값
	 * @param ?object $configs 컨텍스트 설정
	 * @return ?object $templet 템플릿정보
	 */
	public function getBoardTemplet(string $board_id,?object $configs=null):?object {
		$board = $this->getBoard($board_id);
		if ($board === null) return null;
		
		$templet = new stdClass();
		$templet->name = $configs?->templet ?? $board->templet;
		$templet->configs = $configs?->templet_configs ?? $board->templet_configs;
		
		return $templet;
	}
	
	/**
	 * 게시판의 특정 기능에 대한 수행권한을 가지고 있는지 확인한다.
	 *
	 * @param string $board_id 게시판고유값
	 * @param string $code 확인할 기능코드
	 * @param ?int $member_id 권한을 확인할 회원고유값 (NULL 인 경우 현재 로그인한 사용자)
	 * @return bool $has_permission 권한보유여부
	 */
	public function checkPermission(string $board_id,string $code,?int $member_id=null):bool {
		// todo: 권한처리
		return true;
		return false;
	}
	
	/**
	 * 모듈의 컨텍스트를 가져온다.
	 *
	 * @param string $board_id 게시판고유값
	 * @param ?object $configs 컨텍스트 설정
	 * @return string $html
	 */
	public function getContext(string $board_id,?object $configs=null):string {
		$board = $this->getBoard($board_id);
		if ($board === null) return ErrorHandler::get('NOT_FOUND_BOARD',$board_id);
		
		$html = '';
		
		$view = $this->getRouteAt(0) ?? 'list';
		switch ($view) {
			case 'list' :
				$html.= $this->getListContext($board_id,$configs);
				break;

			case 'view' :
//				$html.= $this->getViewContext($board_id,$configs);
				break;

			case 'write' :
				$html.= $this->getWriteContext($board_id,$configs);
				break;
		}
		
		return $html;
	}
	
	/**
	 * 게시물 목록 컨텍스트를 가져온다.
	 *
	 * @param string $board_id 게시판고유값
	 * @param ?object $configs 사이트맵 관리를 통해 설정된 페이지 컨텍스트 설정
	 * @return string $html 컨텍스트 HTML
	 */
	public function getListContext(string $board_id,?object $configs=null):string {
		$board = $this->getBoard($board_id);
		
		if ($this->checkPermission($board_id,'LIST') == false) {
			return ErrorHandler::get($this->error('FORBIDDEN','LIST'));
		}
		
		/**
		 * 로봇 메타 설정
		 */
		Html::robots('noindex, follow');
		
		/**
		 * 카테고리 설정에 따라, 게시판의 카테고리를 가져온다.
		 */
		if ($board->use_category != 'NONE') {
			$categories = $this->db()->select()->from($this->table('categories'))->where('board_id',$board_id)->get();
		} else {
			$categories = [];
		}
		
		$category_id = null;
		$p = null;
		$post_id = null;
		
		$check = $this->getRouteAt(1);
		if ($check === 'category') {
			
		} else {
			$p = is_numeric($check) === true && $check > 0 ? intval($check) : 1;
		}
		
		$board_id = 'notice';
		
		/**
		 * 게시판의 공지사항 출력설정에 따라 공지사항 게시물을 가져온다.
		 *
		 * INCLUDE : 공지사항의 게시물을 페이지당 게시물수에 포함시켜 페이징처리한다.
		 * FIRST : 공지사항의 게시물을 페이지당 게시물수에 포함시키지 않고 페이징처리 없이 첫페이지에만 표시한다.
		 * ALL : 공지사항의 게시물을 페이지당 게시물수에 포함시키지 않고 페이징처리 없이 모든 페이지에서 표시한다.
		 */
		if ($board->view_notice == 'INCLUDE') {
			/**
			 * 현재 페이지에 표시할 공지사항이 있는 경우, 공지사항 게시물을 현재 페이지에 맞게 가져온다.
			 */
			if (($board->post_limit * ($p - 1)) <= $board->notices) {
				/**
				 * 공지사항 게시물 범위
				 */
				$start = $board->post_limit * ($p - 1);
				$limit = min($board->post_limit,$board->notices - $board->post_limit * ($p - 1));
				$notices = $this->db()->select()->from($this->table('posts'))->where('board_id',$board_id)->where('is_notice','TRUE')->orderBy('reg_date','desc')->limit($start,$limit)->get();
				
				/**
				 * 일반 게시물을 가져올 범위
				 */
				$start = 0;
				$limit = $board->post_limit - $limit;
			} else {
				$notices = [];
				
				/**
				 * 일반 게시물을 가져올 범위
				 * 원래 가져와야하는 게시물의 범위에서 공지사항 게시물 갯수만큼 범위를 조절한다.
				 */
				$start = $board->post_limit * ($p - 1) - $board->notices;
				$limit = $board->post_limit;
			}
		} else {
			/**
			 * 첫페이지거나, 모든 페이지에 공지사항을 가져오도록 설정된 경우 공지사항 게시물을 가져온다.
			 */
			if ($board->view_notice == 'ALL' || $p === 1) {
				$notices = $this->db()->select()->from($this->table('posts'))->where('board_id',$board_id)->where('is_notice','TRUE')->orderBy('reg_date','desc')->get();
			} else {
				$notices = [];
			}
			
			/**
			 * 일반 게시물을 가져올 범위
			 */
			$start = ($p - 1) * $board->post_limit;
			$limit = $board->post_limit;
		}
		
		/**
		 * 일반 게시물을 가져온다.
		 */
		$key = Request::get('key') ?? 'title';
		$keyword = Request::get('keyword') ?? '';
		$posts = $this->db()->select()->from($this->table('posts'))->where('board_id',$board_id)->where('is_notice','FALSE');
		if ($category_id !== null) $posts->where('category_id',$category_id);
		$total = $posts->copy()->count();
		$posts = $posts->limit($start,$limit)->orderBy('reg_date','desc')->get();
		
		foreach ($notices as &$notice) {
			$notice = $this->getPost($notice);
			$notice->link = $this->getRouteUrl('view',$notice->post_id);
		}

		$loopnum = $total - $start;
		foreach ($posts as $idx=>&$post) {
			$post = $this->getPost($post);
			$post->loopnum = $loopnum - $idx;
			$post->link = $this->getRouteUrl('view',$post->post_id);
		}
		
		/**
		 * 페이지 네비게이션을 가져온다.
		 * 공지사항 출력방식이 INCLUDE 인 경우, 게시물 갯수에 공지사항 갯수를 더한다.
		 */
		if ($board->view_notice == 'INCLUDE') {
			$total = $total + $board->notices;
		}
		// todo: 페이징 네비게이션 구현
		
		$link = new stdClass();
		$link->list = $this->getRouteUrl('list',$p);
		$link->write = $this->getRouteUrl('write');
		
		/**
		 * 게시판 목록페이지에서 사용될 수 있는 게시판 권한여부을 가져온다.
		 */
		$permission = new stdClass();
		$permission->POST_WRITE = $this->checkPermission($board->board_id,'POST_WRITE');
		
		/**
		 * 템플릿파일을 호출한다.
		 */
		$header = Html::tag(
			'<form id="ModuleBoardListForm">'
		);
		$footer = Html::tag(
			'</form>',
			'<script>Board.list.init("ModuleBoardListForm");</script>'
		);
		
		$templet = $this->getBoardTemplet($board_id,$configs);
		return $this->getTemplet($templet->name,$templet->configs)->getContext('list',get_defined_vars(),$header,$footer);
	}
	
	/**
	 * 게시물 작성 컨텍스트를 가져온다.
	 *
	 * @param string $board_id 게시판고유값
	 * @param ?object $configs 사이트맵 관리를 통해 설정된 페이지 컨텍스트 설정
	 * @return string $html 컨텍스트 HTML
	 */
	public function getWriteContext(string $board_id,?object $configs=null):string {
		$board = $this->getBoard($board_id);
		
		/**
		 * 회원모듈
		 *
		 * @var ModuleMember $member
		 */
		$member = Modules::get('member');
		
		/**
		 * 게시물 고유값이 있는 경우 게시물 수정
		 */
		$post_id = $this->getRouteAt(1);
		if ($post_id === null) {
			$post = null;
			
			/**
			 * 게시물 작성권한을 확인한다.
			 */
			if ($this->checkPermission($board_id,'POST_WRITE') == false) {
				return ErrorHandler::get($this->error('FORBIDDEN','POST_WRITE'));
			}
		} else {
			$post = $this->db()->select()->from($this->table('posts'))->where('board_id',$board_id)->where('post_id',$post_id)->getOne();
			
			/**
			 * 게시물 수정권한 확인한다.
			 */
			if ($this->checkPermission($board_id,'POST_MODIFY') == false) {
				if ($post->member_id != 0 && $post->member_id != $member->getLogged()) {
					return ErrorHandler::get($this->error('FORBIDDEN','POST_MODIFY'));
				} elseif ($post->member_id == 0) {
					$password = Request::post('password');
					if (Password::verify($password,$post->password) == false) {
						$context = ErrorHandler::get('INCORRECT_PASSWORD');
//						$context.= PHP_EOL.'<script>Board.view.modify('.$post_id.');</script>'.PHP_EOL;
						
						return $context;
					}
				}
			}
		}
		
		/**
		 * 로봇 메타 설정
		 */
		Html::robots('noidex, nofollow');
		
		if ($board->use_category != 'NONE') {
			$categories = $this->db()->select()->from($this->table('categories'))->where('board_id',$board_id)->orderBy('sort','asc')->get();
		} else {
			$categories = [];
		}
		
		if ($board->use_prefix == true) {
			$prefixes = $this->db()->select()->from($this->table('prefixes'))->where('board_id',$board_id)->orderBy('sort','asc')->get();
		} else {
			$prefixes = [];
		}
		
		/**
		 * 위지윅모듈 설정
		 *
		 * @var ModuleWysiwyg $wysiwyg
		 */
		$wysiwyg = Modules::get('wysiwyg');
		$wysiwyg->setContext('MODULE','board')->setName('content');
		/*
		

		$header = PHP_EOL.'<form id="ModuleBoardWriteForm" data-autosave="'.$bid.'-new">'.PHP_EOL;
		$header.= '<input type="hidden" name="templet" value="'.$this->getTemplet($configs)->getName().'">'.PHP_EOL;
		$header.= '<input type="hidden" name="bid" value="'.$bid.'">'.PHP_EOL;
		if ($post !== null) $header.= '<input type="hidden" name="idx" value="'.$post->idx.'">'.PHP_EOL;
		if ($configs != null && isset($configs->category) == true && $configs->category != 0) {
			$categories = array();
			$header.= '<input type="hidden" name="category" value="'.$configs->category.'">'.PHP_EOL;
		}
		$footer = PHP_EOL.'</form>'.PHP_EOL.'<script>Board.write.init("ModuleBoardWriteForm");</script>'.PHP_EOL;

		$wysiwyg = $this->IM->getModule('wysiwyg')->setModule('board')->setName('content')->setRequired(true)->setContent($post == null ? '' : $post->content);
		$uploader = $this->IM->getModule('attachment');
		if ($board->use_attachment == true) {
			if ($configs == null || isset($configs->attachment) == null || $configs->attachment == '#') {
				$attachment_templet_name = $board->attachment->templet;
				$attachment_templet_configs = $board->attachment->templet_configs;
			} else {
				$attachment_templet_name = $configs->attachment;
				$attachment_templet_configs = isset($configs->attachment_configs) == true ? $configs->attachment_configs : null;
			}

			if ($attachment_templet_name != '#') {
				$attachment_templet = new stdClass();
				$attachment_templet->templet = $attachment_templet_name;
				$attachment_templet->templet_configs = $attachment_templet_configs;
			} else {
				$attachment_templet = '#';
			}

			$uploader = $uploader->setTemplet($attachment_templet)->setModule('board')->setWysiwyg('content')->setDeleteMode('MANUAL');
			if ($post != null) {
				$uploader->setLoader($this->IM->getProcessUrl('board','getFiles',array('idx'=>Encoder(json_encode(array('type'=>'POST','idx'=>$post->idx))))));
			}
		} else {
			$uploader = $uploader->disable();
		}
		*/
		$header = $footer = '';
		$templet = $this->getBoardTemplet($board_id,$configs);
		return $this->getTemplet($templet->name,$templet->configs)->getContext('write',get_defined_vars(),$header,$footer);
	}
	
	/**
	 * 특수한 에러코드의 경우 에러데이터를 현재 클래스에서 처리하여 에러클래스로 전달한다.
	 *
	 * @param string $code 에러코드
	 * @param ?string $message 에러메시지
	 * @param ?object $details 에러와 관련된 추가정보
	 * @return object $error
	 */
	public function error(string $code,?string $message=null,?object $details=null):object {
		$error = ErrorHandler::data();
		
		switch ($code) {
			/**
			 * 권한이 부족한 경우, 로그인이 되어 있지 않을 경우, 로그인관련 에러메시지를 표시하고
			 * 그렇지 않은 경우 권한이 부족하다는 에러메시지를 표시한다.
			 */
			case 'FORBIDDEN' :
				if (Modules::get('member')->isLogged() == true) {
					$error->prefix = $this->getErrorText('FORBIDDEN');
					$error->message = $this->getErrorText('FORBIDDEN_DETAIL',['code'=>$this->getErrorText('FORBIDDEN_CODE/'.$message)]);
				} else {
					$error->prefix = $this->getErrorText('REQUIRED_LOGIN');
				}
				break;
				
			default :
				$error = parent::error($code,$message,$details);
		}
		
		return $error;
	}
}
?>