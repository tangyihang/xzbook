<?php
namespace common\widgets\soap;
/*
 * To change this template, choose Tools | Templates
 * and open the template in the editor.
 */
class CSoapClient extends \SoapClient {

        public function __doRequest($request, $location, $action, $version, $one_way = 0) {
                $response = parent::__doRequest($request, $location, $action, $version, $one_way);

                //根据实际情况做处理。。。，如果是<?xml开头，改成<?xml
                $start = strpos($response, '<soap');
                $end = strrpos($response, '>');
                $response_string = substr($response, $start, $end - $start + 1);
                return($response_string);
        }

}
