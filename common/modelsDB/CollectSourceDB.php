<?php
namespace common\modelsDB;

use Yii;
use yii\db\ActiveRecord;

/**
 * 小说采集来源网站信息
 * @author yihang
 *
 */
class CollectSourceDB extends ActiveRecord {
	
	public static function tableName() {
		return 'collect_source';
	}
	
	public static function getSourceInfo($id){
		$key = 'CollectSourceDB_getSourceInfo_gsid_'.$id;
		$source = \Yii::$app->cache->get($key);
		if (empty($source)) {
			$source = self::find()->where(['sourceid'=>$id])->one();
			\Yii::$app->cache->set($key, $source, ONE_MONTH_TIME);
		}
		return $source;
	}
}