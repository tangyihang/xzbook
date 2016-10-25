<?php
namespace frontend\controllers;

use Yii;
use yii\web\Controller;
use common\models\Gather;
use common\modelsDB\CollectFieldDB;
use common\modelsDB\BooksourceDB;
/**
 * Site controller
 */
class SiteController extends Controller {
   
    /**
     * Displays homepage.
     *
     * @return mixed
     */
    public function actionIndex()
    {
    	echo 11;
    }

}
