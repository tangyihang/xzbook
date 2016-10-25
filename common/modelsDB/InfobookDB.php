<?php
namespace common\modelsDB;

use Yii;
use yii\db\ActiveRecord;

/**
 * 书籍信息
 */
class InfobookDB extends ActiveRecord {
	
	public static function tableName() {
		return 'info_book';
	}
	
	/**
	 * 获取书籍信息
	 * @param string $bookname
	 * @param string $author
	 */
	public static function getBook($bookname, $author){
		$book = self::find()->where(['bookname'=>$bookname, 'author'=>$author])->one();
		return $book;
	}
	
	/**
	 * 根据书籍id获取书籍信息
	 * @param int $bookid
	 */
	public static function getBookById($bookid){
		$book = self::find()->where(['bookid'=>$bookid])->one();
		return $book;
	}
	
	/**
	 * 添加书籍并返回书籍id
	 * @param string $bookname
	 * @param string $author
	 */
	public static function saveBook($bookname, $author){
		$book = new InfobookDB();
		$book->bookname = $bookname;
		$book->author = $author;
		$book->createtime = time();
		if ($book->save()){
			return $book->bookid;
		} else {
			return 0;
		}
		return $bookid;
	}
	
	/**
	 * 查看书籍是否存在，如果不存在新添加书籍，返回书籍id
	 * @param string $bookname
	 * @param string $author
	 */
	public static function getBrokerId($bookname, $author){
		$book = self::getBook($bookname, $author);
		if (!empty($book)) {
			$bookid = $book->bookid;
		} else {
			$bookid = self::saveBook($bookname, $author);
		}
		return $bookid;
	}
	
}