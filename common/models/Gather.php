<?php
namespace common\models;

use Yii;
use common\helps\globals;
use common\modelsCLS\HttpCLS;
use common\modelsCLS\ChineseCLS;
use common\modelsCLS\CharsetFUN;
use common\modelsDB\BooksourceDB;
use common\modelsDB\CollectRuleDB;
use common\modelsDB\CollectFieldDB;
use common\modelsDB\InfobookDB;
use yii\helpers\VarDumper;
use common\modelsDB\MongoChapterDB;
use common\modelsDB\CollectSourceDB;
/**
 * 采集类
 * @author yihang
 *
 */
class Gather {
	
	public $sourceid = 0;
	public $sourcetype = 1;
	public $booklistRule = array();//书籍列表页规则
	public $booklistFields = array();//书籍列表页字段
	public $bookinfoRule = array();//书籍介绍页规则
	public $bookinfoFields = array();//书籍介绍页字段
	public $chapterListRule = array();//书籍章节列表页规则
	public $chapterListFields = array();//书籍章节列表页字段
	public $chapterRule = array();//书籍章节详情页规则
	public $chapterFields = array();//书籍章节详情页字段
	
	function __construct($sourceid) {
		$this->sourceid = $sourceid;
		$source = CollectSourceDB::getSourceInfo($sourceid);
		$this->sourcetype = $source->type;
		$rules = CollectRuleDB::getRuleInfo($sourceid);
		foreach ($rules as $rule) {
			$fields = CollectFieldDB::getFieldInfo($rule->ruleid);
			switch ($rule->typeid)
			{
				case 1: 
					$this->booklistRule = $rule;
					$this->booklistFields = $fields;
					//var_dump($this->booklistFields);
					break;
				case 2: 
					$this->bookinfoRule = $rule;
					$this->bookinfoFields = $fields;
					break;
				case 3: 
					$this->chapterListRule = $rule;
					$this->chapterListFields = $fields;
					break;
				case 4: 
					$this->chapterRule = $rule;
					$this->chapterFields = $fields;
					break;
			}
		}
	}
	
	/**
	 * 获取采集页面网址列表
	 */
	function fetch_surls() {
		$surls = array();
		if ($this->booklistRule && strpos( $this->booklistRule->uregular, '(*)') > 1) {
			for ($i = $this->booklistRule->ufromnum; $i <= $this->booklistRule->utonum; $i++) {
				$surls [] = str_replace ( "(*)", $i, $this->booklistRule->uregular );
			}
		}
		krsort ( $surls );//倒序采集
		return $surls;
	}
	
	/**
	 * 获取要采集的书籍的信息
	 * @param string $surl
	 * @return boolean
	 */
	function fetch_books_gurls($surl){
		//有书籍介绍页和没有书籍介绍页的走不同的方法进行采集
		if ($this->sourcetype == 1) {
			$this->fetch_info_books_gurls($surl);
		} else if ($this->sourcetype == 2) {
			$this->fetch_noinfo_books_gurls($surl);
		}
		return true;
	}
	
	/**
	 * 没有书籍介绍页的书籍信息采集
	 * 获取要采集的书籍的信息
	 * @param string $surl
	 */
	function fetch_noinfo_books_gurls($surl){
		if (empty ( $surl ) || !($html = $this->onepage ( $surl ))) {
			die;return false;// 源网址不存在或无法读取该页面
		}
		//取出初始有效范围
		if (empty($this->booklistRule->uregion) || !($uhtml = $this->fetch_detail ( $this->booklistRule->uregion, $html ))) {
			globals::setErrorLog(array('html' => $html, 'url' => $surl), 'nobooklist');
			die;return false;
		}
		$urlregions = explode ( $this->booklistRule->uspilit, $uhtml ); // 划出url区域
		foreach ( $urlregions as $urlregion ) { // 遍历每个url内容区块
			$this->clean_blank ( $urlregion );
			$contents = $this->fetch_fields($this->booklistFields, $urlregion, $surl);
			if (!empty($contents)) {
				if (($row = BooksourceDB::getBookSourceByDB($contents['bookinfourl']))) {//判断该来源书籍是否已经存在
					//判断书籍是否需要更新，最新章节标题不一样时，更新来源和书籍的最新章节信息
					if (globals::filter_mark($contents['bcnewtitle']) != globals::filter_mark($row->bcnewtitle)) {
						//更新来源信息
						$row->bcnewtitle = $contents['bcnewtitle'];
						$row->updatetime = date('Y-m-d H:i:s', time());
						$row->save();
						$book = InfobookDB::getBook($contents['bookname'], $contents['author']);
						//更新书籍信息
						if (!empty($book)) {
							$book->bcnewtitle = $contents['bcnewtitle'];
							$book->updatetime = date('Y-m-d H:i:s', time());
							$book->save();
						} else {
							$book = new InfobookDB();
							$book->bookname = $contents['bookname'];
							$book->author = $contents['author'];
							$book->bcnewtitle = $contents['bcnewtitle'];
							$book->updatetime = date('Y-m-d H:i:s', time());
							$book->booksourceid = $this->sourceid;
							$book->createtime = time();
							$book->save();
						}
					}
				} else {//不存在则添加书籍
					$book = InfobookDB::getBook($contents['bookname'], $contents['author']);
					//判断书籍是否存在，不存在则添加书籍
					if (empty($book)) {//新增书籍
						$book = new InfobookDB();
						$book->bookname = $contents['bookname'];
						$book->author = $contents['author'];
						$book->bcnewtitle = $contents['bcnewtitle'];
						$book->updatetime = date('Y-m-d H:i:s', time());
						$book->booksourceid = $this->sourceid;
						$book->createtime = time();
						$book->save();
					}
					//判断该来源书籍是不存在，添加来源书籍
					if (empty($row)) {
						$booksource = new BooksourceDB();
						$booksource->bookid = $book->bookid;
						$booksource->bcnewtitle = $contents['bcnewtitle'];
						$booksource->updatetime = date('Y-m-d H:i:s', time());
						$booksource->sourceid = $this->sourceid;
						$booksource->infourl = $contents['bookinfourl'];
						$booksource->listurl = $contents['bookinfourl'];
						$booksource->createtime = time();
						$booksource->refreshtime = time();
						$booksource->save();
					}
				}
			} else {
				if (empty($urlregion)) {
					globals::setErrorLog(array('urlregion' => $urlregion, 'html' => $html, 'url' => $surl), 'nobooklist');
					die;return false;
				} else {
					globals::setErrorLog(array('html' => $urlregion, 'url' => $surl), 'book');
				}
			}
		}
		var_dump($surl);
		unset($html, $urlregions, $urlregion, $contents, $book, $booksource);
		return true;
	}
	
	/**
	 * 页面字段问题调试
	 */
	public function info_test(){
		$infohtml = $this->onepage ( 'http://www.ckxsw.com/chkbook/0/48687.html' );
		$infocontents = $this->fetch_fields($this->bookinfoFields, $infohtml, 'http://www.ckxsw.com/chkbook/0/48687.html');
		var_dump($infocontents);die;
		
		/* //测试更新获取数据
		$surl = 'http://www.baoliny.com/lastupdate_81.html';
		$gather = new Gather(3);
		$html = $gather->onepage ( $surl );
		var_dump($html);
		$uhtml = $gather->fetch_detail ( $gather->booklistRule->uregion, $html );
		var_dump($uhtml);
		$urlregions = explode ( $gather->booklistRule->uspilit, $uhtml );
		var_dump($urlregions);
		foreach ( $urlregions as $urlregion ) {
			$gather->clean_blank ( $urlregion );
			$contents = $gather->fetch_fields($gather->booklistFields, $urlregion, $surl);
			var_dump($contents);
		}
		die; */
	}
	
	/**
	 * 有书籍介绍页的书籍信息采集
	 * 获取要采集的书籍的信息
	 * @param string $surl
	 */
	function fetch_info_books_gurls($surl) {
		if (empty ( $surl ) || ! ($html = $this->onepage ( $surl ))) {
			die;return false; // 源网址不存在或无法读取该页面
		}
		//取出初始有效范围
		if (empty($this->booklistRule->uregion) || !($uhtml = $this->fetch_detail ( $this->booklistRule->uregion, $html ))) {
			globals::setErrorLog(array('html' => $html, 'url' => $surl), 'nobooklist');
			die;return false;
		}
		$urlregions = explode ( $this->booklistRule->uspilit, $uhtml ); // 划出url区域
		//var_dump($urlregions);die;
		foreach ( $urlregions as $urlregion ) { // 遍历每个url内容区块
			$this->clean_blank ( $urlregion );
			$contents = $this->fetch_fields($this->booklistFields, $urlregion, $surl);
			if (!empty($contents)) {
				//var_dump($contents);die;
				if (($row = BooksourceDB::getBookSourceByDB($contents['bookinfourl']))) {//判断该来源书籍是否已经存在
					//判断书籍是否需要更新，最新章节标题不一样时，更新来源和书籍的最新章节信息
					if (globals::filter_mark($contents['bcnewtitle']) != globals::filter_mark($row->bcnewtitle)) {
						//获取介绍信息
						$infohtml = $this->onepage ( $contents['bookinfourl'] );
						$infocontents = $this->fetch_fields($this->bookinfoFields, $infohtml, $contents['bookinfourl']);
						if (!empty($infocontents)) {
							//更新来源信息
							$row->bcnewtitle = $contents['bcnewtitle'];
							$row->bcnewurl = $contents['bcnewurl'];
							$row->updatetime = date('Y-m-d H:i:s', time());
							$row->category = $infocontents['category'];
							$row->bookstatus = $infocontents['bookstatus'];
							$row->info = $infocontents['info'];
							$row->wordcount = $infocontents['wordcount'];
							$row->save();
							$book = InfobookDB::getBook($contents['bookname'], $contents['author']);
							if (!empty($book)) {
								//更新书籍信息
								$book->bcnewtitle = $contents['bcnewtitle'];
								$book->bcnewurl = $contents['bcnewurl'];
								$book->updatetime = date('Y-m-d H:i:s', time());
								$book->category = $infocontents['category'];
								$book->bookstatus = $infocontents['bookstatus'];
								$book->info = $infocontents['info'];
								$book->wordcount = $infocontents['wordcount'];
								$book->booksourceid = $this->sourceid;
								if (!empty($row->imgurl)) {
									$book->imgurl = $row->imgurl;
								}
								$book->save();
							} else {
								$book = new InfobookDB();
								$book->bookname = $infocontents['bookname'];
								$book->author = $infocontents['author'];
								$book->bcnewtitle = $contents['bcnewtitle'];
								$book->bcnewurl = $contents['bcnewurl'];
								$book->updatetime = date('Y-m-d H:i:s', time());
								$book->category = $infocontents['category'];
								$book->bookstatus = $infocontents['bookstatus'];
								$book->wordcount = $infocontents['wordcount'];
								$book->info = $infocontents['info'];
								$book->booksourceid = $this->sourceid;
								$book->createtime = time();
								$book->save();
							}
						} else {
							globals::setErrorLog(array('html' => $infohtml, 'url' => $contents['bookinfourl']), 'bookinfo');
							//die;
						}
					}
				} else {//不存在则添加书籍
						//判断书籍是否存在，不存在则添加书籍
						$infohtml = $this->onepage ( $contents['bookinfourl'] );
						$infocontents = $this->fetch_fields($this->bookinfoFields, $infohtml, $contents['bookinfourl']);
// 						var_dump($this->bookinfoFields);
// 						var_dump($infocontents);die;
						if (!empty($infocontents)) {
							$book = InfobookDB::getBook($contents['bookname'], $contents['author']);
							if (empty($book)) {//新增书籍
								$book = new InfobookDB();
								$book->bookname = $infocontents['bookname'];
								$book->author = $infocontents['author'];
								$book->bcnewtitle = $contents['bcnewtitle'];
								$book->bcnewurl = $contents['bcnewurl'];
								$book->updatetime = date('Y-m-d H:i:s', time());
								$book->category = $infocontents['category'];
								$book->bookstatus = $infocontents['bookstatus'];
								$book->wordcount = $infocontents['wordcount'];
								$book->info = $infocontents['info'];
								$book->booksourceid = $this->sourceid;
								$book->createtime = time();
								$book->save();
							}
							//判断该来源书籍是不存在，添加来源书籍
							if (empty($row)) {
								$booksource = new BooksourceDB();
								$booksource->bookid = $book->bookid;
								$booksource->bcnewtitle = $contents['bcnewtitle'];
								$booksource->bcnewurl = $contents['bcnewurl'];
								$booksource->updatetime = date('Y-m-d H:i:s', time());
								$booksource->category = $infocontents['category'];
								$booksource->bookstatus = $infocontents['bookstatus'];
								$booksource->wordcount = $infocontents['wordcount'];
								$booksource->info = $infocontents['info'];
								$booksource->sourceid = $this->sourceid;
								$booksource->infourl = $contents['bookinfourl'];
								$booksource->listurl = $infocontents['chapterlisturl'];
								$booksource->imgsourceurl = $infocontents['imgurl'];
								//如果新增书籍保存书籍代表图
								if (!empty($infocontents['imgurl'])) {
									$imageNewUrl = \Yii::getAlias('@frontend/web/images/books/').$book->bookid.'/';
									$imginfo = globals::getImage($infocontents['imgurl'], $imageNewUrl, $book->bookid.'_'.$this->sourceid);//保存图片
									if ($imginfo['error'] == 0) {
										$booksource->imgurl = str_replace ( \Yii::getAlias('@frontend/web/'), "", $imginfo['save_path'] );
										$book->imgurl = $booksource->imgurl;
										$book->save();
									}
								}
								$booksource->createtime = time();
								$booksource->refreshtime = time();
								$booksource->save();
							}
						} else {
							globals::setErrorLog(array('html' => $infohtml, 'url' => $contents['bookinfourl']), 'bookinfo');
							//die;
						}
				}
					
			} else {
				globals::setErrorLog(array('urlregion' => $urlregion, 'url' => $surl), 'nobooklist');
			}
			unset($booksource, $book, $row, $infocontents, $imageNewUrl, $imginfo, $infohtml);
			unset($infocontents, $book, $contents);
		}
		var_dump($surl);
		unset($urlregions, $urlregion);
		return true;
	}
	
	/**
	 * 根据来源书籍列表页地址获取来源书籍章节列表信息
	 */
	function fetch_book_chapterList($listurl){
		if (empty ( $listurl ) || ! ($html = $this->onepage ( $listurl ))) {
			return ''; // 源网址不存在或无法读取该页面
		}
		$this->chapterListRule->uregion && $uhtml = $this->fetch_detail ( $this->chapterListRule->uregion, $html );
		$urlregions = explode ( $this->chapterListRule->uspilit, $uhtml ); // 划出url区域
		$chapters = '';
		$num = 0;
		if (!empty($urlregions)) {
			foreach ( $urlregions as $urlregion ) { // 遍历每个url内容区块
				$this->clean_blank ( $urlregion );
				$contents = $this->fetch_fields($this->chapterListFields, $urlregion, $listurl);
				if (!empty($contents)) {
					$chapters[$num] = $contents;
					$chapters[$num]['sourceid'] = $this->sourceid;
					$chapters[$num]['cid'] = $num+1;
					$num++;
				}
				unset($contents);
			}
		}
		unset($urlregions, $urlregion, $html);
		return $chapters;
	}
	
	/**
	 * 根据章节url获取章节内容
	 * @param string $chapterurl
	 */
	function fetch_chapter($chapterurl){
		//获取章节信息和内容
		$chapterHtml = $this->onepage ( $chapterurl );
		$chapterContents = $this->fetch_fields($this->chapterFields, $chapterHtml, $chapterurl);
		return $chapterContents;
	}
	
	/**
	 * 根据来源书籍列表页地址获取来源书籍详情页列表信息
	 * @param string $surl
	 * @return boolean
	 */
	function fetch_book_chapters($booksource) {
		if (empty ( $booksource->listurl ) || ! ($html = $this->onepage ( $booksource->listurl ))) {
			return false; // 源网址不存在或无法读取该页面
		}
		$this->chapterListRule->uregion && $html = $this->fetch_detail ( $this->chapterListRule->uregion, $html );
		$urlregions = explode ( $this->chapterListRule->uspilit, $html ); // 划出url区域
		//var_dump($urlregions);die;
		//声明mongo链接
		$urlcoll = Yii::$app->mongodb->getCollection('chapter_url');
		//查询书籍中该源的全部连接信息
		$sources = MongoChapterDB::getChapterSource($booksource->bookid, $booksource->sourceid);
		//var_dump($sources);die;
		if (!empty($urlregions)) {
			foreach ( $urlregions as $urlregion ) { // 遍历每个url内容区块
				$this->clean_blank ( $urlregion );
				$contents = $this->fetch_fields($this->chapterListFields, $urlregion, $booksource->listurl);
				if (!empty($contents)) {
					//章节源信息是否存在，如果不存在保存源信息
					if (!in_array($contents['chapterurl'], $sources)) {
						//保存章节标题链接
						$urlcoll->insert([
								'bookid' => $booksource->bookid,
								'sourceid' => $this->sourceid,
								'booksourceid' => $booksource->id,
								'title' => $contents['chaptertitle'],
								'csurl' => $contents['chapterurl'],
								'updatetime' => time(),
						]);
					}
				}
				unset($contents);
			}
		} else {
			return false;
		}
		var_dump('success_'.$booksource->bookid.'_'.$this->sourceid);
		unset($urlregions, $urlregion, $html, $urlcoll, $sources);
		//die;
		return true;
	}
	
	/**
	 * 
	 * @param unknown $surl
	 * @return string|multitype:Ambigous <string, unknown>
	 */
	function chapter_field($surl){
		$contents = array();
		if (empty ( $surl ))
			return '';
		$html = $this->onepage ( $surl );
		if($html == '') return '';
		
		unset ( $html, $content, $field, $fname );
		return $contents;
	}
	
	/**
	 * 获取采集字段的值
	 * @param string $fname
	 * @param string $html
	 * @param string $reflink
	 */
	function fetch_fields($fields, $urlregion, $reflink){//当前任务，当前url情况下
		$contents = array();
		foreach ($fields as $field) {
			if (! $fieldvalue = $this->fetch_detail($field->uregular,$urlregion)) {
				if ($field->isitnull) {
					$contents[$field->fieldname] = $fieldvalue;
				} else {
					$contents = array();
					break;
				}
			} else {
				if ($field->type == 1) {
					$fieldvalue = $this->fillurl ( $fieldvalue, $reflink );//进行网址补全
				}
				if ($field->fieldname == '') {
					$this->clean_title($fieldvalue);
				}
				if (!empty($field->cleartext)) {
					$cleartexts = array_filter(explode ( '|', $field->cleartext ));
					if (!empty($cleartexts)) {
						foreach ($cleartexts as $value){
							$fieldvalue = preg_replace ( "/".$value."/i", "", $fieldvalue );
						}
					}
				}
				if (!empty($field->clearhtml)) {
					$this->clearhtml($field->clearhtml,$fieldvalue);//清除指定html信息
				}
				if (!empty($field->cleardefalt)) {
					$fieldvalue = str_replace ( $field->cleardefalt, "", $fieldvalue );//清除指定html信息
				}
				$contents[$field->fieldname] = trim($fieldvalue);
			}
		}
		unset($fieldvalue, $fields, $urlregion, $reflink);
		return $contents;
	}
	
	/**
	 * 根据规则字符串正则匹配出匹配的信息
	 * @param string $tagstr
	 * @param string $html
	 * @return string
	 */
	function fetch_detail($tagstring, $html) {
		if (! $tagstring)
			return '';
		$tagstrs = array_filter ( explode ( '##', $tagstring ) );
		$fetchstr = '';
		foreach ($tagstrs as $tagstr){
			$this->clean_blank ( $tagstr );
			$pos = strpos ( $tagstr, '(*)' );
			if (! $pos || $pos + 3 == strlen ( $pos ))
				return '';
			if (! preg_match ( '/' . $this->regencode ( $tagstr ) . '/is', $html, $matches ))
				continue;
			$fetchstr = &$matches [1];
			$this->clean_blank ( $fetchstr );
			unset ( $html, $tagstr, $matches );
			break;
		}
		
		return $fetchstr;
	}
	
	/**
	 * 采集页面
	 * @param string $url
	 */
	function onepage($url) {
		//如果未请求到数据，则重新请求，不超过5次
		for ($i=1;$i<=5;$i++) {
			$m_http = new HttpCLS ();
			if ($this->booklistRule->timeout)
				$m_http->timeout = $this->booklistRule->timeout;
			$html = $m_http->fetchtext ( $url );
			unset ( $m_http );
			$html = CharsetFUN::convert_encoding ( $this->booklistRule->mcharset, 'utf-8', $html );
			$this->clean_blank ( $html );
			if (!empty($html)) {
				break;
			} else {
				sleep(15);
			}
			globals::setErrorLog(array('url' => $url.'_'.$i), 'reload');
		}
		return $html;
	}
	
	/**
	 * 获取追溯网址
	 * @param string $surl
	 * @param string $pattern
	 * @param string $reflink
	 * @return string
	 */
	function fetch_addurl($surl, $pattern, $reflink) {
		if (empty ( $surl ) || empty ( $pattern ))
			return '';
		$html = $this->onepage ( $surl );
		$addurl = $this->fetch_detail ( $pattern, $html );
		$addurl = $this->fillurl ( $addurl, $reflink );
		unset ( $html );
		return $addurl;
	}
	
	/**
	 * 清理空格和回车字符
	 * @param string $str
	 */
	function clean_blank(&$str) {
		$str = preg_replace ( "/([\r\n|\r|\n]*)/is", "", $str );
		$str = preg_replace ( "/>([\s]*)</is", "><", $str );
		$str = preg_replace ( "/^([ ]*)/is", "", $str );
		$str = preg_replace ( "/([ ]*)$/is", "", $str );
	}
	
	/**
	 * 清理标题数据
	 */
	function clean_title(&$str){
		$str = preg_replace ( "/\d*\./i", "", $str );
		$str = preg_replace ( "/^正文/i", "", $str );
	}
	
	/**
	 * 对正则中需要转译的符号进行转译
	 * @param string $str
	 * @return string
	 */
	function regencode($str) {
		$search = array (
				"\\",
				'"',
				".",
				"[",
				"]",
				"(",
				")",
				"?",
				"+",
				"*",
				"^",
				"{",
				"}",
				"$",
				"|",
				"/",
				"\(\?\)",
				"\(\*\)"
		);
		$replace = array (
				"\\\\",
				'\"',
				"\.",
				"\[",
				"\]",
				"\(",
				"\)",
				"\?",
				"\+",
				"\*",
				"\^",
				"\{",
				"\}",
				"\$",
				"\|",
				"\/",
				".*?",
				"(.*?)"
		);
		return str_replace ( $search, $replace, $str );
	}
	
	/**
	 * 清除指定的html标签信息
	 * @param string $serial
	 */
	function clearhtml($serial, &$str) {
		if (! $serial || ! $str)
			return;
		$ids = array_filter ( explode ( ',', $serial ) );
		$search = array (
				"/<a[^>]*?>(.*?)<\/a>/is",//1
				"/<br[^>]*?>/i",//2
				"/<table[^>]*?>([\s\S]*?)<\/table>/i",//3
				"/<tr[^>]*?>([\s\S]*?)<\/tr>/i",//4
				"/<td[^>]*?>([\s\S]*?)<\/td>/i",//5
				"/<p[^>]*?>([\s\S]*?)<\/p>/i",//6
				"/<font[^>]*?>([\s\S]*?)<\/font>/i",//7
				"/<div[^>]*?>([\s\S]*?)<\/div>/i",//8
				"/<span[^>]*?>([\s\S]*?)<\/span>/i",//9
				"/<tbody[^>]*?>([\s\S]*?)<\/tbody>/i",//10
				"/<([\/]?)b>/i",//11
				"/<img[^>]*?>/i",//12
				"/[&nbsp;]{2,}/i",//13
				"/<script[^>]*?>([\w\W]*?)<\/script>/i",//14
				"/\d*\./i"//15去掉标题前的章节序号
		);
		$replace = array (
				"\\1",
				"",
				"\\1",
				"\\1",
				"\\1",
				"\\1",
				"\\1",
				"\\1",
				"\\1",
				"\\1",
				"",
				"",
				"&nbsp;",
				"\\1",
				"\\1"
		);
		foreach ( $ids as $id )
			$str = preg_replace ( $search [$id - 1], $replace [$id - 1], $str );
	}
	
	/**
	 * 对网址信息进行补全
	 * @param string $surl
	 * @param string $refhref
	 * @param string $basehref
	 * @return string
	 */
	function fillurl($surl, $refhref, $basehref = '') { // $refhref用以参照的完全网址
		$surl = trim ( $surl );
		$refhref = trim ( $refhref );
		$basehref = trim ( $basehref );
		if ($surl == '')
			return '';
	
		if ($basehref) {
			$preurl = strtolower ( substr ( $surl, 0, 6 ) );
			if (in_array ( $preurl, array (
					'http:/',
					'ftp://',
					'mms://',
					'rtsp:/',
					'thunde',
					'emule:',
					'ed2k:/'
			) )) {
				return $surl;
			} else {
				return $basehref . '/' . $surl;
			}
		}
	
		$urlparses = @parse_url ( $refhref );
		$homeurl = $urlparses ['host'];
		$baseurlpath = $homeurl . $urlparses ['path'];
		$baseurlpath = preg_replace ( "/\/([^\/]*)\.(.*)$/", "/", $baseurlpath );
		$baseurlpath = preg_replace ( "/\/$/", "", $baseurlpath );
	
		$i = $pathstep = 0;
		$dstr = $pstr = $okurl = '';
		$surl = (strpos ( $surl, "#" ) > 0) ? substr ( $surl, 0, strpos ( $surl, "#" ) ) : $surl;
		if ($surl [0] == "/") { // 不含http的绝对网址
			$okurl = "http://" . $homeurl . $surl;
		} elseif ($surl [0] == ".") { // 相对网址
			if (strlen ( $surl ) <= 1) {
				return "";
			} elseif ($surl [1] == "/") {
				$okurl = "http://" . $baseurlpath . "/" . substr ( $surl, 2, strlen ( $surl ) - 2 );
			} else {
				$urls = explode ( "/", $surl );
				foreach ( $urls as $u ) {
					if ($u == "..") {
						$pathstep ++;
					} elseif ($i < count ( $urls ) - 1) {
						$dstr .= $urls [$i] . "/";
					} else {
						$dstr .= $urls [$i];
					}
					$i ++;
				}
				$urls = explode ( "/", $baseurlpath );
				if (count ( $urls ) <= $pathstep) {
					return "http://" . $baseurlpath . '/' . $dstr;
				} else {
					$pstr = "http://";
					for($i = 0; $i < count ( $urls ) - $pathstep; $i ++) {
						$pstr .= $urls [$i] . "/";
					}
					$okurl = $pstr . $dstr;
				}
			}
		} else {
			$preurl = strtolower ( substr ( $surl, 0, 6 ) );
			if (strlen ( $surl ) < 7) {
				$okurl = "http://" . $baseurlpath . "/" . $surl;
			} elseif (in_array ( $preurl, array (
					'http:/',
					'ftp://',
					'mms://',
					'rtsp:/',
					'thunde',
					'emule:',
					'ed2k:/'
			) )) {
				$okurl = $surl;
			} else
				$okurl = "http://" . $baseurlpath . "/" . $surl;
		}
	
		$preurl = strtolower ( substr ( $okurl, 0, 6 ) );
		if (in_array ( $preurl, array (
				'ftp://',
				'mms://',
				'rtsp:/',
				'thunde',
				'emule:',
				'ed2k:/'
		) )) {
			return $okurl;
		} else {
			$okurl = preg_replace('/^(http:\/\/)/', "", $okurl);
			$okurl = preg_replace('/\/{1,}/', "/", $okurl);
			return "http://" . $okurl;
		}
	}
}