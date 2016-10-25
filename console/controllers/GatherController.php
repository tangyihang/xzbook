<?php
namespace console\controllers;

use Yii;
use common\config\CController;
use common\helps\globals;
use common\models\Gather;
use common\modelsDB\BooksourceDB;
use common\modelsDB\MongoChapterDB;
/**
 * 采集控制器
 * @author yihang
 *
 */
class GatherController extends CController {
	
	public function actionIndex(){
		echo 111;die;
	}
	
	/**
	 * 采集更新全部该规则下全部小说信息
	 * @return boolean
	 */
	public function actionGetbooks($sourceid){
		$gather = new Gather($sourceid);
		$surls = $gather->fetch_surls();//获取采集地址列表
		foreach($surls as $surl) $gather->fetch_books_gurls($surl);//进行书籍采集
		return true;
	}
	
	/**
	 * 采集小说全部章节信息
	 * @return boolean
	 */
	public function actionUpdatechapter(){
		//查询这一次要进行更新的小说信息
		$booksources = BooksourceDB::getOldBooks();
		//根据查询结果更新小说的最近刷新书籍时间
		if (!empty($booksources)) {
			foreach ($booksources as $booksource) {
				$ids[] = $booksource->id;
			}
			BooksourceDB::updateBookRefreshtime($ids);
			//循环查询每本书籍中是否有更新
			foreach ($booksources as $booksource) {
				//查询资源所有的章节信息,如果不存在则更新章节
				$gather = new Gather($booksource->sourceid);
				$schapters = $gather->fetch_book_chapters($booksource);
				if ($schapters) {
					BooksourceDB::updateBookIsgather($booksource->id);
				} else {
					BooksourceDB::updateBookIsNotgather($booksource->id);
				}
			}
			echo 'success';
		}
		return true;
	}
	
}