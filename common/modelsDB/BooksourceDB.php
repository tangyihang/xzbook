<?php
namespace common\modelsDB;

use Yii;
use yii\db\ActiveRecord;
use common\models\Gather;

/**
 * 书籍来源对应关系表
 * @author yihang
 *
 */
class BooksourceDB extends ActiveRecord {
	
	public static function tableName() {
		return 'collect_booksource';
	}
	
	/**
	 * 查询来源对应的书籍是否存在
	 * @param unknown $bookid
	 * @param unknown $sourceid
	 * @return unknown
	 */
	public static function getBookSourceByDB($infourl){
		$booksource = self::find()->where(['infourl'=>$infourl])->one();
		return $booksource;
	}
	
	/**
	 * 获取要刷新时间最小的几本书籍信息
	 * @param int $num
	 */
	public static function getOldBooks($num = ONCEUPDATECHAPTER){
		$booksources = self::find()->where(['isgather' => 1, 'status' => 1])->orderBy(['refreshtime'=>SORT_ASC])->limit($num)->all();
		return $booksources;
	}
	
	/**
	 * 根据对应关系id更新书籍刷新时间
	 * @param array $ids
	 * @return boolean
	 */
	public static function updateBookRefreshtime($ids){
		self::updateAll(['isgather' => 2, 'refreshtime' => time()], array('id' => $ids));
		return true;
	}
	
	/**
	 * 更新书籍状态为待采集
	 * @param array $ids
	 * @return boolean
	 */
	public static function updateBookIsNotgather($ids){
		self::updateAll(['isgather' => 1], array('id' => $ids));
		return true;
	}
	
	/**
	 * 更新书籍状态为采集完成
	 * @param array $ids
	 * @return boolean
	 */
	public static function updateBookIsgather($ids){
		self::updateAll(['isgather' => 0], array('id' => $ids));
		return true;
	}
	
}