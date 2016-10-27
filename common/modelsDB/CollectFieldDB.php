<?php
namespace common\modelsDB;

use Yii;
use yii\db\ActiveRecord;
use yii\helpers\VarDumper;

/**
 * 小说采集详细规则
 * @author yihang
 *
 */
class CollectFieldDB extends ActiveRecord {
	
	public static function tableName() {
		return 'collect_rulefield';
	}
	
	/**
	 * 获取来源下规则获取的字段信息
	 * @param int $id
	 * @return \yii\caching\mixed
	 */
	public static function getFieldInfo($ruleid){
		$key = 'CollectFieldDB_getFieldInfo_ruleid_'.$ruleid;
		$fields = \Yii::$app->cache->get($key);
		if (empty($fields)) {
			$fields = self::find()->where(['ruleid' => $ruleid])->all();
			\Yii::$app->cache->set($key, $fields, ONE_MONTH_TIME);
		}
		return $fields;
	}
	
	/* 字段类型
		$allfields = array (
				'bookname' => '',//书籍名称
				'author' => '',//书籍作者
				'bcnewtitle' => '',//最新章节标题
				'bookinfourl' => '',//书籍介绍页连接
				'chapterlisturl' => '',//章节列表连接
				'category' => '',//书籍分类
				'bookstatus' => '',//书籍状态：连载中，已完成
				'wordcount' => '',//总字数
				'imgurl' => '',//书籍图片地址
				'info' => '',//书籍简介
				'chapterurl' => '',//章节详情连接
				'chaptertitle' => '',//文章标题
				'content' => '',//文章内容
		);
	*/
	
	/**
	 * //CollectFieldDB::setfield();//生成获取字段的规则
	 * @return boolean
	 */
	public static function setfield(){
		$field = new CollectFieldDB();
		$field->sourceid = '2';//字段名
		$field->fieldname = 'bcnewurl';//字段名
		$field->ruleid = '1';//所属规则id
		$field->uregular = '<li>最新更新：<a href="(*)" target="_blank">(?)</a></li>';//规则
		$field->type = '1';//1、连接，2、文字
		$field->clearhtml = '';//清除指定的标签
		$field->cleardefalt = '';//清除的默认值
		$field->updatetime = time();//更新时间
		$field->save();
		var_dump($field);
		return true;
	}
	
}