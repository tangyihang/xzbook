<?php
namespace common\config;
use Yii;
use yii\web\Controller;
use yii\web\Application;
use common\helps\globals;

class CController extends Controller {

    public $user = [];
    public $subDomain;

    public function beforeAction($action) {

//         $this->checkDomain(); //验证域名
//         $this->getCoordinates(); //获取用户坐标
//         $this->checkLoginUser(); //验证用户是否登陆

        return true;
    }

}
