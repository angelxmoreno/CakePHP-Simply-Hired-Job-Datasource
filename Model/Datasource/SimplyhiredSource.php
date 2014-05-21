<?php

/**
 * Simply Hired Jobs Datasource
 *
 * PHP Version 5
 *
 * PHPMine : make PHP yours (http://www.phpmine.com)
 *
 * Licensed under The MIT License
 * Redistributions of files must retain the above copyright notice.
 *
 * @copyright Copyright 2011, PHPMine (http://www.phpmine.com)
 * @version 1.0
 * @link http://www.phpmine.com
 * @package datasources
 * @author Angel S. Moreno <angelxmoreno@gmail.com>
 * @license MIT License (http://www.opensource.org/licenses/mit-license.php)
 */
App::uses('CakeHttp', 'Simplyhired.Lib');

/**
 * Simplyhired Datasource
 *
 * @package datasources
 * @subpackage plugin.simplyhired.datasources.models.datasources
 */
class SimplyhiredSource extends DataSource {

    /**
     * Default configuration.
     *
     * @var array
     * @access public
     */
    public $_baseConfig = array(
        'pshid' => null,
        'jbd' => null,
        'ssty' => '2',
        'cflg' => 'r',
        'clip' => '0',
        'l' => 'New+York+City%2c+New+York',
        'mi' => 25,
        'sb' => 'dd',
        'ws' => 25,
        'pn' => 1
    );

    /**
     * Valid conditions keys
     *
     * @var array
     * @access protected
     */
    protected $_validConditionKeys = array(
        'title',
        'company',
        'location',
        'miles',
        'keywords'
    );

    /**
     * Valid field columns
     *
     * @var array
     * @access protected
     */
    protected $_validFieldKeys = array(
        'job_key',
        'title',
        'type',
        'company_name',
        'company_url',
        'description',
        'source',
        'url',
        'location',
        'city',
        'state',
        'postcode',
        'county',
        'region',
        'country',
        'date_last_seen',
        'date_that_posted'
    );

    /**
     * Valid fields to order by
     *
     * @var array
     * @access protected
     */
    protected $_validOrderKeys = array(
        'relevance',
        'last_seen_date',
        'title',
        'company',
        'location',
    );

    /**
     * Array mapping sorting keys from API to data source 
     *
     * @var array
     * @access protected
     */
    protected $_mapOrder = array(
        'rd' => 'relevance desc',
        'ra' => 'relevance asc',
        'dd' => 'last_seen_date desc',
        'da' => 'last_seen_date asc',
        'td' => 'title desc',
        'ta' => 'title asc',
        'cd' => 'company desc',
        'ca' => 'company asc',
        'ld' => 'location desc',
        'la' => 'location asc',
    );

    /**
     * CakeHttp object
     *
     * @var CakeHttp
     * @access protected
     */
    protected $CakeHttp;

    /**
     * SimplyHiredJobAMaticApi Object
     * @var SimplyHiredJobAMaticApi 
     */
    protected $SimplyhiredApi;

    /**
     * Holds the description of this datasource
     *
     * @var string
     * @access protected
     * */
    protected $description = "SimplyHired Data Source";

    /**
     * Constructor
     *
     * @param string $config Configuration array
     */
    public function __construct($config = null) {
        parent::__construct($config);
        $this->connected = $this->connect();
    }

    /**
     * Connecting to datasource
     *
     * @param string $config Configuration array
     * @return bool
     * @access public
     */
    public function connect() {
        if (empty($this->config['pshid']) || empty($this->config['jbd'])) {
            throw CakeException('Please provide your PublisherID and JobAMaticDomain in the config');
        }
        $this->CakeHttp = new CakeHttp();
        $this->SimplyhiredApi = new SimplyHiredJobAMaticApi(
                $this->config['pshid'], $this->config['jbd'], $this->CakeHttp
        );
        return true;
    }

    /**
     * Read Data
     *
     * @param Model $Model
     * @param array $queryData
     * @return mixed
     * @access public
     */
    public function read(Model $Model, $queryData = array()) {
        /*
          $cacheKey = 'SimplyHiredCall_' . md5(serialize($queryData));
          if ($this->config['cached']) {
          $resultSet = Cache::read($cacheKey);
          if ($resultSet !== false) {
          return $resultSet;
          }
          }
         */

        $queryData = $this->__scrubQueryData($queryData, $Model);
        $this->__buildRequest($queryData);
        $response = $this->SimplyhiredApi->request();
        $results = $response->toArray();
        $resultSet = $results['jobs_collection'];
        if ($Model->findQueryType === 'count' && count($resultSet) > 0) {
            $resultSet = array(array(array('count' => $results['total_results'])));
        } elseif ($Model->findQueryType === 'count' && count($resultSet) == 0) {
            $resultSet = array(array(array('count' => 0)));
        } else {
            $resultSet = $this->__relabel($resultSet, $queryData['fields'], $Model);
        }
        //Cache::write($cacheKey, $resultSet);
        return $resultSet;
    }

    /**
     * Protected helper method to remove query metadata in given data array.
     *
     * @param array $dirtyQuery Data
     * @param Model $Model the model this dataqsource is attached to
     * @return array Cleaned Data
     * @access protected
     */
    protected function __scrubQueryData($dirtyQuery, Model $Model) {
        //$queryKeys = array('conditions', 'fields', 'joins', 'order', 'limit', 'offset', 'group');
        $queryKeys = array('conditions', 'fields', 'order', 'limit', 'page');
        foreach ($queryKeys as $key) {
            if (!isset($dirtyQuery[$key]) || empty($dirtyQuery[$key])) {
                $cleanQuery[$key] = array();
            } else {
                $cleanQuery[$key] = $dirtyQuery[$key];
            }
        }

        $cleanQuery['conditions'] = $this->__cleanQueryConditions($cleanQuery['conditions']);
        $cleanQuery['fields'] = $this->__cleanQueryFields($cleanQuery['fields']);
        if($Model->findQueryType != 'count'){
            $cleanQuery['order'] = $this->__cleanQueryOrder($cleanQuery['order']);
        }
        $cleanQuery['limit'] = $this->__cleanQueryLimit($cleanQuery['limit']);
        $cleanQuery['page'] = $this->__cleanQueryPage($cleanQuery['page']);

        return $cleanQuery;
    }

    /**
     * Protected helper method to sanitize and normalize query conditions
     *
     * @param array $dirtyConditions Data
     * @return array Conditions Data
     * @access protected
     */
    protected function __cleanQueryConditions($dirtyConditions) {
        //check location
        if (array_key_exists('location', $dirtyConditions)) {
            $cleanConditions['location'] = $dirtyConditions['location'];
        }

        //check miles
        if (array_key_exists('miles', $dirtyConditions)) {
            $cleanConditions['miles'] = $dirtyConditions['miles'];
            if (is_string($cleanConditions['miles']) && $cleanConditions['miles'] != 'exact') {
                throw CakeException('Invalid value for miles: ' . $cleanConditions['miles']);
            } elseif ($cleanConditions['miles'] != 'exact' && (int) $cleanConditions['miles'] > 100) {
                $cleanConditions['miles'] = 100;
            } elseif ($cleanConditions['miles'] != 'exact' && (int) $cleanConditions['miles'] < 1) {
                $cleanConditions['miles'] = 1;
            }
        }
        $query = '';

        //check query   
        if (array_key_exists('query', $dirtyConditions)) {
            $query .= "{$dirtyConditions['query']}";
        }

        //check title
        if (array_key_exists('title', $dirtyConditions)) {
            $query .= " title:({$dirtyConditions['title']})";
        }

        //check company
        if (array_key_exists('company', $dirtyConditions)) {
            $query .= " company:({$dirtyConditions['company']})";
        }

        $cleanConditions['query'] = $query;
        return $cleanConditions;
    }

    /**
     * Protected helper method to sanitize and normalize query fields
     *
     * @param array $dirtyFields Data
     * @return array Fields Data
     * @access protected
     */
    protected function __cleanQueryFields($dirtyFields) {
        if (is_string($dirtyFields) && strtolower($dirtyFields) == 'count') {
            return array('COUNT');
        }
        if (
                (is_string($dirtyFields) && $dirtyFields == '*') ||
                (is_array($dirtyFields) && count($dirtyFields) == 0)
        ) {
            return $this->_validFieldKeys;
        }
        if (!is_array($dirtyFields)) {
            $dirtyFields = (array) $dirtyFields;
        }
        return array_values(array_intersect($this->_validFieldKeys, $dirtyFields));
    }

    /**
     * Protected helper method to sanitize and normalize query order
     *
     * @param array $dirtyOrder Data
     * @return string Order Data
     * @access protected
     */
    protected function __cleanQueryOrder($dirtyOrder) {
        if (!is_array($dirtyOrder)) {
            list($field, $dir) = explode(' ', $dirtyOrder);
        } else {
            $dirtyOrder = current($dirtyOrder);
            $field = key($dirtyOrder);
            $dir = $dirtyOrder[$field];
        }

        $field = strtolower($field);
        $dir = strtolower($dir);

        if (!in_array($field, $this->_validOrderKeys)) {
            throw CakeException('Invalid order key: ' . $field);
        }

        if (!in_array($dir, array('asc', 'desc'))) {
            throw CakeException('Invalid order direction: ' . $dir);
        }
        $order = $field . ' ' . $dir;
        return array_search($order, $this->_mapOrder);
    }

    /**
     * Protected helper method to sanitize and normalize query limit
     *
     * @param array $dirtyLimit Data
     * @return int Limit Data
     * @access protected
     */
    protected function __cleanQueryLimit($dirtyLimit) {
        if (empty($dirtyLimit)) {
            return $this->config['ws'];
        } elseif ((int) $dirtyLimit < 1) {
            return 1;
        } elseif ((int) $dirtyLimit > 100) {
            return 100;
        } else {
            return (int) $dirtyLimit;
        }
    }

    /**
     * Protected helper method to sanitize and normalize query page
     *
     * @param array $dirtyPage Data
     * @return int Page Data
     * @access protected
     */
    protected function __cleanQueryPage($dirtyPage) {
        if (empty($dirtyPage) || (int) $dirtyPage < 1) {
            return 1;
        } else {
            return (int) $dirtyPage;
        }
    }

    /**
     * Protected helper method to construct the URL and GET Params based on $queryData
     *
     * @param array $queryData Data
     * @return array Containing URL and Params
     * @access protected
     */
    protected function __buildRequest($queryData) {
        if (isset($queryData['conditions']['query'])) {
            $this->SimplyhiredApi->setQuery($queryData['conditions']['query']);
        }

        if (isset($queryData['conditions']['location'])) {
            $this->SimplyhiredApi->setLocation($queryData['conditions']['location']);
        }

        if (isset($queryData['conditions']['miles']) && $queryData['conditions']['miles'] == 'exact') {
            $this->SimplyhiredApi->setLocationExact();
        } elseif (isset($queryData['conditions']['miles'])) {
            $this->SimplyhiredApi->setMiles($queryData['conditions']['miles']);
        }

        if (isset($queryData['order'])) {
            $this->SimplyhiredApi->setSort($queryData['order']);
        }

        if (isset($queryData['limit'])) {
            $this->SimplyhiredApi->setWindowSize($queryData['limit']);
        }

        if (isset($queryData['page'])) {
            $this->SimplyhiredApi->setPageNumber($queryData['page']);
        }
    }

    /**
     * Protected helper method to relabel boring API keys with meaningful one and filters keys based on $queryData['fields']
     *
     * @param array $resultSet Raw result set from the API
     * @param array $fields array of fields to return
     * @param Model $Model the model attached to this datasource
     * @return array relabled and filtered result set
     * @access protected
     */
    protected function __relabel($resultSet, $fields, Model $Model) {
        //fix array mapping for find first ( returns a row of the result set )
        $resultSet = (is_string(key($resultSet))) ? array($resultSet) : $resultSet;
        $rows = array();

        foreach ($resultSet as $row) {
            $rows[] = array($Model->alias => array_intersect_key($row, array_flip($fields)));
        }
        return $rows;
    }

    /**
     * Calculate
     *
     * @param Model $Model 
     * @param mixed $func 
     * @param array $params 
     * @return array
     */
    public function calculate(&$Model, $func = 'count', $params = array()) {
        return 'count';
    }

}

if (!function_exists('getRealIpAddr')) {

    function getRealIpAddr() {
        if (isset($_SERVER))
            $ip = '0.0.0.0';
        elseif (!empty($_SERVER['HTTP_CLIENT_IP']))   //check ip from share internet
            $ip = $_SERVER['HTTP_CLIENT_IP'];
        elseif (!empty($_SERVER['HTTP_X_FORWARDED_FOR']))   //to check ip is pass from proxy
            $ip = $_SERVER['HTTP_X_FORWARDED_FOR'];
        else
            $ip = $_SERVER['REMOTE_ADDR'];
        return $ip;
    }

}