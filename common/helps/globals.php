<?php
namespace common\helps;

use yii;
use yii\log\FileTarget;
/**
 * 全局globals类
 */
class globals{

    /**
     * 获取客户端IP
     */
    public static function getClientIP() {
        if(isset($_SERVER['HTTP_CDN_SRC_IP'])) {
            return $_SERVER['HTTP_CDN_SRC_IP'];
        } else if (isset($_SERVER['HTTP_X_FORWARDED_FOR'])) {
            return $_SERVER['HTTP_X_FORWARDED_FOR'];
        } else {
            return \Yii::$app->request->userIP;
        }
    }

    /**
     * 用户登陆后存放到session中的key
     */
    public static function getSessionUserId() {
        $userIP = self::getClientIP();
        $userIP = str_replace('.', '_', $userIP);
        $time = strtotime(date('Y-m-d H', time()).':00:00');
        $key = $userIP . '_'. $time;
        return $key;
    }

    /**
     * 获取session
     */
    public static function getSession($key) {
        $sessions = \Yii::$app->session;
		return $sessions->get($key);
    }

    /**
     * 设置session
     */
    public static function setSession($key, $value) {
        $sessions = \Yii::$app->session;
        if($sessions->set($key, $value))
            return true;
        else
            return false;
    }

    /**
     * 销毁session
     */
    public static function removeSession($key) {
        $sessions = \Yii::$app->session;
        $sessions->remove($key);
        return true;
    }


    /**
     * 获取cookie
     */
    public static function getCookies($key, $size = '') {
        $cookies = \Yii::$app->request->cookies;
        if(($cookie = $cookies->get($key)) !== null) {
            if($size === '') 
                return $cookie->value;
            else
                return $cookie->value;
        }

        return null; 
    }

    /**
     * 设置cookie
     */
    public static function setCookies($key, $value, $time = 2592000) {
        $cookies = \Yii::$app->response->cookies;
        $cookies->add(new \yii\web\Cookie([
            'name' => $key,
            'value' => $value,
            'expire' => time()+$time
        ]));
        return true;
    }

    /**
     * 设置入栈出栈Cookies
     */
    public static function setCookiesList($key, $history, $new, $size = 10) {
        if($history) {
            if(count($history) >= $size) {
                array_pop($history);
            }
            array_unshift($history, $new);
        } else {
            $history = [];
            array_push($history, $new);
        }
        return self::setCookies($key, $history);
    }

    /**
     * 删除cookie
     */
    public static function removeCookies($key) {
        $cookies = \Yii::$app->response->cookies;
        //$cookies = \Yii::$app->request->cookies;
        $cookies->remove($key);
        return true;
	}
	
	/**
	 *功能：php完美实现下载远程图片保存到本地
	 *参数：文件url,保存文件目录,保存文件名称，使用的下载方式
	 *当保存文件名称为空时则使用远程文件原来的名称
	 */
	public static function getImage($url,$save_dir='',$filename='',$type=0){
		if(trim($url)==''){
			return array('file_name'=>'','save_path'=>'','error'=>1);
		}
		if(trim($save_dir)==''){
			$save_dir='./';
		}
		$ext=strrchr($url,'.');
		if($ext!='.jpg'){
			return array('file_name'=>'','save_path'=>'','error'=>3);
		}
		if(trim($filename)==''){//保存文件名
			$filename=time().$ext;
		} else {
			$filename=$filename.$ext;
		}
		if('/'!==strrchr($save_dir,'/')){
			$save_dir.='/';
		}
		//创建保存目录
		self::mkDirs($save_dir);
		
		//获取远程文件所采用的方法
		ob_start();
		if(@readfile($url)){
			$img=ob_get_contents();
			ob_end_clean();
			$fp2=@fopen($save_dir.$filename,'a');
			fwrite($fp2,$img);
			fclose($fp2);
			//文件大小
			unset($img,$url);
			return array('file_name'=>$filename,'save_path'=>$save_dir.$filename,'error'=>0);
		} else {
			return array('error'=>1,'info'=>'文件读取失败');
		}
		
	}
	
	//递归建立多层目录
	public static function mkDirs($dir, $mode = 0777) {
		if (!is_dir($dir)) {
			if (!self::mkDirs(dirname($dir), $mode)) {
				return false;
			}
	
			if (!mkdir($dir, $mode)) {
				return false;
			}
		}
		return true;
	}
	
	public static function imgName() {
		$randTime = explode('.', microtime(true));
		return $randTime[1];
	}
	
	/**
	 * 获取文章字数
	 * @param string $str
	 * @return int
	 */
	public static function getStringWordCount($str){
		$str = preg_replace ( "/&nbsp;/is", "", $str );
		$wordcount = mb_strlen(preg_replace('/\s/','',html_entity_decode(strip_tags($str))),'UTF-8');
		return $wordcount;
	}
	
	/**
	 * 生成章节信息获取不到log
	 */
	public static function setErrorLog($messages, $logname = 'chapter'){
		$log = new FileTarget();
		$log->logFile = Yii::$app->getRuntimePath() . '/logs/'.$logname.'.log';
		$log->messages[] = [$messages, '1', $logname, microtime(true)];
		$log->export();
	}
	
	/**
	 * 去掉字符串中全部的标点符号和空格
	 * @param string $text
	 * @return string
	 */
	public static function filter_mark($text){
		if(trim($text)=='')return '';
		//去掉英文字符
		$text=preg_replace("/[[:punct:]\s]/",'',$text);
		//去掉中文字符
		$text = preg_replace('/[\x{2018}-\x{2026}\x{3000}-\x{301e}\x{fe50}-\x{ff1f}]/u','',$text);
		return trim($text);
	}
}
