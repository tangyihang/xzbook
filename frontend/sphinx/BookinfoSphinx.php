<?php
namespace frontend\sphinx;

use common\widgets\sphinx\SphinxClient;

/**
 * 二手房聚合房源sphinx
 */
class BookinfoSphinx extends SphinxClient {

    var $server;
    var $port;
    var $indexName;

    public function __construct($server = '') {
        parent::__construct();
        $config = !empty($server) ? $server : \Yii::$app->params['sphinxServer']['bookinfo'];
        if (is_array($config) and isset ($config['server'])) {
            $this->server = $config['server'];
            $this->port = intval($config['port']);
            $this->indexName = $config['indexName'];
        } else {
            throw new CException('exchange group sphinx server config not connect', 0);
        }
        $this->SetServer($this->server, $this->port);
        $this->setConnectTimeout(1); //连接超时时间
        $this->setMaxQueryTime(3000); //查询超时时间
        //可选，为每一个全文检索字段设置权重，主要根据你在sql_query中定义的字段的顺序，
        //Sphinx系统以后会调整，可以按字段名称来设定权重
        $this->SetMatchMode(0);
        //设置字段权重
        $this->SetFieldWeights(array(
            'bookname'=>5, 
        	'author' => 2,
        ));
    }

    /**
     * 设定指定的字段
     * @param selectString $selectString
     */
    public function setSelectString($selectString) {
        $this->SetSelect($selectString);
    }
    
    /**
     * 根据书籍id搜索
     * @param int $bookid
     */
    public function setBookid($bookid){
    	$this->SetFilter("bid", array($bookid));
    }

    /**
     * 设定分页
     * @param int $page 页数
     * @param int $size 每一页的个数
     */
    public function setPage($page, $size=PAGE_SIZE_TWELVE) {
        $offset = ($page - 1) * $size; //偏移量
        $num = $offset + PAGE_SIZE_HUNDRED; //缓存空间
        if($num < PAGE_SIZE_THOUSAND)
            $num = PAGE_SIZE_THOUSAND;

        $this->SetLimits($offset, $size, $num);
    }

    /**
     * 设定搜索结果排序条件
     * @param String $attribute
     * @param String $order
     */
    public function setOrder($attribute, $order) {
        switch ($order) {
        case ASC:
            $orderType = SPH_SORT_ATTR_ASC;
            break;
        case DESC:
            $orderType = SPH_SORT_ATTR_DESC;
            break;
        default:
            $orderType = SPH_SORT_ATTR_ASC;
            break;
        }
        $this->SetSortMode($orderType, $attribute);
    }

    /**
     * 设定多重排序条件
     * @param Array $orders 排序条件数组
     * @example $order = array (
     *     '0' => array ('attribute' => 'columnname', 'order' => DESC),
     *     '1' => array (//...),
     * );
     */
    public function multiOrder($orders, $orderType = SPH_SORT_EXTENDED) {
        $orderString = '';
        foreach ($orders as $order) {
            switch ($order['order']) {
            case ASC :
                $orderString .= $order['attribute'] . ' ASC, ';
                break;
            case DESC : 
                $orderString .= $order['attribute'] . ' DESC, ';
                break;
            default :
                $orderString .= $order['attribute'] . ' DESC, ';
                break;
            }
        }
        $orderString = substr($orderString, 0, strlen($orderString)-2);
        $this->SetSortMode($orderType, $orderString);
    }

    /**
     * 获取搜索结果
     * @param String $keyword
     */
    public function search($keyword) {
        if ($this->GetError() != "") {
            throw new CException('Exchange_Group_Sphinx unable to query the data');
        } else {
            return $this->Query($keyword, $this->indexName);
        }
    }

    public function getError() {
        return $this->GetLastError();
    }

}
