<?php
App::import(
    'Vendor',
    'Simplyhired.InterfaceSimplyHiredJobAMaticApiHttp',
    array('file' => 'SimplyHired-JobAMatic-API' . DS . 'src' . DS . 'abstract' . DS . 'InterfaceSimplyHiredJobAMaticApiHttp.php')
);
App::import(
    'Vendor',
    'Simplyhired.AbstractSimplyHiredJobAMaticApi',
    array('file' => 'SimplyHired-JobAMatic-API' . DS . 'src' . DS . 'abstract' . DS . 'AbstractSimplyHiredJobAMaticApi.php')
);
App::import(
    'Vendor',
    'Simplyhired.SimplyHiredJobAMaticApi',
    array('file' => 'SimplyHired-JobAMatic-API' . DS . 'src' . DS . 'SimplyHiredJobAMaticApi.php')
);
App::import(
    'Vendor',
    'Simplyhired.SimplyHiredJobAMaticApi_Results',
    array('file' => 'SimplyHired-JobAMatic-API' . DS . 'src' . DS . 'SimplyHiredJobAMaticApi' . DS . 'SimplyHiredJobAMaticApi_Results.php')
);
App::import(
    'Vendor',
    'Simplyhired.SimplyHiredJobAMaticApi_JobsCollection',
    array('file' => 'SimplyHired-JobAMatic-API' . DS . 'src' . DS . 'SimplyHiredJobAMaticApi' . DS . 'SimplyHiredJobAMaticApi_JobsCollection.php')
);
App::import(
    'Vendor',
    'Simplyhired.SimplyHiredJobAMaticApi_Job',
    array('file' => 'SimplyHired-JobAMatic-API' . DS . 'src' . DS . 'SimplyHiredJobAMaticApi' . DS . 'SimplyHiredJobAMaticApi_Job.php')
);
App::uses('HttpSocket', 'Network/Http');

/**
 * @property HttpSocket $_http
 */
class CakeHttp implements InterfaceSimplyHiredJobAMaticApiHttp {
    /**
     *
     * @var HttpSocket 
     */
    protected $_http;
    
    public function __construct() {
        $this->_http = new HttpSocket();
    }
    
    /**
     * Fetch the xml string
     * @param string $url
     * @return string $response
     */
    public function get($url) {
        $response = $this->_http->get($url);
        return $response;
    }
}
