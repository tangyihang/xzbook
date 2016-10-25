<?php
namespace common\modelsDB;

use Yii;

class MongoChapterDB{
	
	/**
	 * 获取该来源下章节连接列表
	 * @param int $bookid
	 * @return array
	 */
	public static function getChapterSource($bookid, $sourceid){
		$sources = array();
		$collection = Yii::$app->mongodb->getCollection('chapter_url');
		$coll = $collection->find(['bookid' => $bookid, 'sourceid' => $sourceid], array('csurl'));
		while($coll->hasNext()){
			$r = $coll->getNext();
			$sources[] = $r['csurl'];
		}
		return $sources;
	}
	
	/**
	 * 
	 * @param unknown $chapterid
	 */
	public static function getChapterContent($chapterid){
		
		
	}
}