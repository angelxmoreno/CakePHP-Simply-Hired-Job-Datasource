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
 * @version 0.1
 * @link http://www.phpmine.com
 * @package datasources
 * @author Angel S. Moreno <angelxmoreno@gmail.com>
 * @license MIT License (http://www.opensource.org/licenses/mit-license.php)
 */


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
		'url' => 'http://api.simplyhired.com/a/jobs-api/xml-v2',
		'required' => array(
			'pshid'	=> '',
			'jbd'	=> '',
			'ssty'	=> '2',
			'cflg'	=> 'r',
			'clip'	=> ''
		),
		
		'default' 	=> array(
			'l'	=> 'New+York+City%2c+New+York',
			'mi' => 25,
			'sb' => 'dd',
			'ws' => 25,
			'pn' => 1
		)
	);

/**
 * Valid conditions keys
 *
 * @var array
 * @access private
 */
	private $_validConditionKeys = array(
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
 * @access private
 */
	private $_validFieldKeys = array(
		'job_key',
		'title',
		'type',
		'company_name',
		'company_url',
		'body',
		'source',
		'job_url',
		'city',
		'state',
		'postal',
		'county',
		'region',
		'country',
		'date_last_seen',
		'date_posted'
	);

/**
 * Valid fields to order by
 *
 * @var array
 * @access private
 */
	private $_validOrderKeys = array(
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
 * @access private
 */
	private $_mapOrder = array(
		'rd' => 'relevance descending',
		'ra' => 'relevance ascending',
		'dd' => 'last_seen_date descending',
		'da' => 'last_seen_date ascending',
		'td' => 'title descending',
		'ta' => 'title ascending',
		'cd' => 'company descending',
		'ca' => 'company ascending',
		'ld' => 'location descending',
		'la' => 'location ascending',
	);
	
/**
 * HttpSocket object
 *
 * @var HttpSocket
 * @access private
 */
	private $Http = null;
	
/**
 * Holds the description of this datasource
 *
 * @var string
 * @access private
 **/
	private $description = "SimplyHired Data Source";
		
/**
 * Constructor
 *
 * @param string $config Configuration array
 */
	public function __construct($config = null) {
		parent::__construct($config);
		$this->connected = $this->connect($config);
		$this->name = 'SimplyHired';
	}

/**
 * Connecting to datasource
 *
 * @param string $config Configuration array
 * @return bool
 * @access public
 */
	public function connect($config) {
		if (empty($config['required']['pshid']) || empty($config['required']['jbd'])) {
			$this->error = "Please provide your PublisherID and JobAMaticDomain in the config";
			$this->showError();
			return false;
		}
		App::import('HttpSocket');
		$this->Http = new HttpSocket();
		//$this->Http->quirksMode = true;
		return true;
	}

/**
 * Close connection to datasource
 *
 * @return bool
 * @access public
 **/
	public function close() {
		$c = $this->Http->disconnect();
		$this->Http = null;
		return $c;
	} 

/**
 * Read Data
 *
 * @param Model $model
 * @param array $queryData
 * @return mixed
 * @access public
 */
	public function read(&$model, $queryData = array()) {
		$cacheKey = 'SimplyHiredCall_'.md5(serialize($queryData));
		if($this->config['cached']){
			$resultSet = Cache::read($cacheKey);
			if ($resultSet !== false) {
				return $resultSet;
			}
		}
		$queryData = $this->__scrubQueryData($queryData);
		$request = $this->__buildRequest($queryData);
		$response = $this->Http->get($request['url'], $request['params']);
		if ($response) {
			App::import('Core', 'Xml');
			$xml = new Xml($response);
			$results = Set::reverse($xml);
			unset($xml);
		} else {
			$this->showError($this->Http->lastError(), 'HTTP');
			return false;
		}
		
		
		if(isset($results['Sherror']) and $results['Sherror']['Error']['type'] == 'noresults'){
			$resultSet = array();
		} elseif(isset($results['Sherror'])){
			$this->showError($results['Sherror']['Error']['type'], 'Response');
			return false;
		} else {
			$resultSet = $results['Shrs']['Rs']['R'];
		}
		
		if ($model->findQueryType === 'count' and count($resultSet) > 0) {
			$resultSet =  array(array(array('count' => $results['Shrs']['Rq']['tv'])));
		} elseif ($model->findQueryType === 'count' and count($resultSet) == 0) {
			$resultSet =  array(array(array('count' => 0)));
		} else {
			$resultSet = $this->__relabel($resultSet, $queryData['fields']);
		}
		Cache::write($cacheKey, $resultSet);
		return $resultSet;
	}

/**
 * Private helper method to remove query metadata in given data array.
 *
 * @param array $dirtyQuery Data
 * @return array Cleaned Data
 * @access private
 */
	private function __scrubQueryData($dirtyQuery) {
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
		$cleanQuery['order'] = $this->__cleanQueryOrder($cleanQuery['order']);
		$cleanQuery['limit'] = $this->__cleanQueryLimit($cleanQuery['limit']);
		$cleanQuery['page'] = $this->__cleanQueryPage($cleanQuery['page']);
		
		return $cleanQuery;
	}

/**
 * Private helper method to sanitize and normalize query conditions
 *
 * @param array $dirtyConditions Data
 * @return array Conditions Data
 * @access private
 */
	private function __cleanQueryConditions($diryConditions){
		$cleanConditions = array();
		$needsQuotePattern = '/(.+\s+.+)/';
		if(!is_array($diryConditions))
			return $cleanConditions;
			
		//check OR. API is not compatible with OR
		if(array_key_exists('OR', $diryConditions)){
			//we do nothing.
		}
		
		//check NOT, only works on keywords
		if(array_key_exists('NOT', $diryConditions) and array_key_exists('keywords', $diryConditions['NOT'])){
			if(is_string($diryConditions['NOT']['keywords'])){
				$cleanConditions['keywords'][] = 'NOT '.preg_replace($needsQuotePattern, '"$1"', $diryConditions['NOT']['keywords']);
			} elseif(is_array($diryConditions['NOT']['keywords'])){
				foreach($diryConditions['NOT']['keywords'] as $val){
					$cleanConditions['keywords'][] = 'NOT '.preg_replace($needsQuotePattern, '"$1"', $val);
				}
			}
		}
		
		//check valid condition keys
		foreach($this->_validConditionKeys as $validConditionKey){
			if(array_key_exists($validConditionKey, $diryConditions)){
				if(is_string($diryConditions[$validConditionKey])){
					$cleanConditions[$validConditionKey][] = preg_replace($needsQuotePattern, '"$1"', $diryConditions[$validConditionKey]);
				} elseif(is_array($diryConditions[$validConditionKey])){
					foreach($diryConditions[$validConditionKey] as $val){
						$cleanConditions[$validConditionKey][] = preg_replace($needsQuotePattern, '"$1"', $val);
					}
				}
			}
		}
		
		//check location
		if(array_key_exists('location', $cleanConditions)){
			$cleanConditions['location'] = array_pop($cleanConditions['location']);
		}
		
		//check miles
		if(array_key_exists('miles', $cleanConditions)){
			$cleanConditions['miles'] = array_pop($cleanConditions['miles']);
			if($cleanConditions['miles'] != 'exact' and (int)$cleanConditions['miles'] > 100){
				$cleanConditions['miles'] = 100;
			} elseif($cleanConditions['miles'] != 'exact' and (int)$cleanConditions['miles'] < 1){
				$cleanConditions['miles'] = 1;
			}
		}
		return $cleanConditions;
	}

/**
 * Private helper method to sanitize and normalize query fields
 *
 * @param array $dirtyFields Data
 * @return array Fields Data
 * @access private
 */
	private function __cleanQueryFields($dirtyFields){
		if(is_string($dirtyFields) and strtolower($dirtyFields) == 'count')
			return array('COUNT');
		
		if(
			(is_string($dirtyFields) and $dirtyFields == '*') or
			(is_array($dirtyFields) and count($dirtyFields) == 0)
		)
			return $this->_validFieldKeys;
			
		if(!is_array($dirtyFields))
			$dirtyFields = (array)$dirtyFields;
		
		return array_values(array_intersect($this->_validFieldKeys, $dirtyFields));
	}

/**
 * Private helper method to sanitize and normalize query order
 *
 * @param array $dirtyOrder Data
 * @return string Order Data
 * @access private
 */
	private function __cleanQueryOrder($dirtyOrder, $direction = 'ASC') {
		$keys = (is_array($dirtyOrder) and count($dirtyOrder) == 1 and array_key_exists(0, $dirtyOrder) and is_array($dirtyOrder[0])) ? $dirtyOrder[0] : $dirtyOrder;
		if (!is_array($keys)) {
			$keys = array($keys);
		}
		$keys = array_reverse(array_filter($keys));
		while (!empty($keys)) {
			list($key, $dir) = each($keys);
			array_shift($keys);
			if (is_numeric($key)) {
				$key = $dir;
				$dir = $direction;
			}

			if (preg_match('/\\x20(ASC|DESC).*/i', $key, $_dir)) {
				$dir = $_dir[0];
				$key = preg_replace('/\\x20(ASC|DESC).*/i', '', $key);
			}

			$key = trim($key);
			if(in_array($key, $this->_validOrderKeys)){
				$dir = (trim($dir) == 'DESC') ? 'descending' : 'ascending';
				$key .= ' ' . trim($dir);
				return $key;
			}
		}
	}

/**
 * Private helper method to sanitize and normalize query limit
 *
 * @param array $dirtyLimit Data
 * @return int Limit Data
 * @access private
 */
	private function __cleanQueryLimit($dirtyLimit){
		if(empty($dirtyLimit))
			return $this->config['default']['ws'];
			
		if((int)$dirtyLimit < 1)
			return 1;
		
		if((int)$dirtyLimit > 100)
			return 100;
		
		return (int)$dirtyLimit;
	}

/**
 * Private helper method to sanitize and normalize query page
 *
 * @param array $dirtyPage Data
 * @return int Page Data
 * @access private
 */
	private function __cleanQueryPage($dirtyPage){
		if(empty($dirtyPage) or (int)$dirtyPage < 1)
			return 1;
		
		if((int)$dirtyPage > 100)
			return 100;
		
		return (int)$dirtyPage;
	}

/**
 * Private helper method to construct the URL and GET Params based on $queryData
 *
 * @param array $queryData Data
 * @return array Containing URL and Params
 * @access private
 */
	private function __buildRequest($queryData){
		//build the API url to call
		
		//build query
		$request['base']['q'] = $this->__buildQuery($queryData);
		
		//build location
		$request['base']['l'] = (isset($queryData['conditions']['location'])) ? urlencode($queryData['conditions']['location']) : $this->config['default']['l'];
		
		//build radius (miles)
		$request['base']['mi'] = (isset($queryData['conditions']['miles'])) ? $queryData['conditions']['miles'] : $this->config['default']['mi'];;
		
		//build sort by
		$request['base']['sb'] = array_search($queryData['order'], $this->_mapOrder);
		
		//build limit (window size)
		$request['base']['ws'] = $queryData['limit'];
		
		//build page
		$request['base']['pn'] = $queryData['page'];
		
		//build remote IP (clip)
		$request['suffix']['clip'] = getRealIpAddr();
		
		//required and default parameters
		$request['base'] = array_merge($this->config['default'],$request['base']);
		$request['suffix'] = array_merge($this->config['required'],$request['suffix']); 
		
		$return = array(
			'url' => $this->__buildApiUrl($request),
			'params' => $request['suffix']
		);
		
		return $return;
	}

/**
 * Private helper method to construct the query part of the Request
 *
 * @param array $queryData Data
 * @return string query part of the Request
 * @access private
 */
	private function __buildQuery($queryData){
		//grab the keywords, title and company keeys from conditions
		$query = '';
		$colon = urlencode(':');
		//keywords
		if(array_key_exists('keywords', $queryData['conditions'])){
			foreach($queryData['conditions']['keywords'] as $keyword){
				if(preg_match("/^NOT /", $keyword))
					$query .= ' '.$keyword;
				else
					$query = 'AND '.$keyword.' '.$query;

			}
			//clean trailing AND or NOT	
			$query = preg_replace('/^(NOT|AND)?(.+)/', '$2',$query);
		}
		
		
		//title
		if(array_key_exists('title', $queryData['conditions'])){
			$query = trim($query.' title'.$colon.'(' . implode(' OR ',$queryData['conditions']['title']) . ')');
		}
		
		//company
		if(array_key_exists('company', $queryData['conditions'])){
			$query = trim($query.' company'.$colon.'(' . implode(' OR ',$queryData['conditions']['company']) . ')');
		}
		
		//some characters need to be encoded and some do not
		$query = preg_replace('/\s+/', '+', $query);
		$query = preg_replace('/"/', urlencode('"'), $query);
		
		return $query;
	}

/**
 * Private helper method to construct the url part of the Request
 *
 * @param array $request Data
 * @return string API URL of the Request
 * @access private
 */
	private function __buildApiUrl($request){
		//add suffix (url)
		$url = trim($this->config['url'], '/ ');
		
		//add base
		//first attach q url encoded
		$url .= '/q-'.$request['base']['q'];
		
		//then remove q since we already have it and attach the rest of base
		unset($request['base']['q']);
		foreach($request['base'] as $key => $val){
			$url .= '/' . $key . '-' . $val;	
		}

		return $url;
	}

/**
 * Private helper method to relabel boring API keys with meaningful one and filters keys based on $queryData['fields']
 *
 * @param array $resultSet Raw result set from the API
 * @param array $fields array of fields to return
 * @return array relabled and filtered result set
 * @access private
 */
	private function __relabel($resultSet, $fields){
		//fix array mapping for find first ( returns a row of the result set )
		$resultSet = (is_string(key($resultSet))) ? array($resultSet) : $resultSet;
		$rows = array(); 
		foreach($resultSet as $row){
			$_row = array(
				'job_key' => $this->__extractJobKeyFromUrl($row['src']['url']),
				'title' => $row['jt'],
				'type' => $row['ty'],
				'company_name' => (isset($row['cn']) and isset($row['cn']['value'])) ? $row['cn']['value'] : '',
				'company_url' =>  (isset($row['cn']) and isset($row['cn']['url'])) ? $row['cn']['url'] : '',
				'body' => $row['e'],
				'source' => $row['src']['value'],
				'job_url' => $row['src']['url'],
				'city' => $row['loc']['cty'],
				'state' => $row['loc']['st'],
				'postal' => $row['loc']['postal'],
				'county' => $row['loc']['county'],
				'region' => $row['loc']['region'],
				'country' => $row['loc']['country'],
				'date_last_seen' => date('Y-m-d H:i:s T', strtotime($row['ls'])),
				'date_posted' => date('Y-m-d H:i:s T', strtotime($row['dp'])),
			);
			
			$rows[] = array_intersect_key($_row, array_flip($fields));
		}
		
		return $rows;
	}
	
/**
 * Private helper to extract the job key from the job source url
 *
 * @param string $url 
 * @return string
 */
	private function __extractJobKeyFromUrl($url){
		preg_match('/jobkey\-([^\/]+)\//', $url, $matches);
		return $matches[1];
	}

/**
 * Calculate
 *
 * @param Model $model 
 * @param mixed $func 
 * @param array $params 
 * @return array
 */
	public function calculate(&$model, $func = 'count', $params = array()) {
		return 'count';
	}
	
/**
 * Shows an error message and outputs result if passed
 *
 * @param String $error 
 * @param mixed $type 
 */
	private function showError($error = null, $type = null) {
		if(!$error)
			 $error = $this->error;
		else
			$this->error = $error;
		$type = ($type) ? ' with '.$type : '';
		
		if (Configure::read('debug') > 0) {
			trigger_error('<span style = "color:Red;text-align:left"><b>'.$this->description.' ERROR'.$type.':</b> ' . $error . '</span>', E_USER_WARNING);
		}
	}
}

if(!function_exists('getRealIpAddr')){
	function getRealIpAddr(){
		if(isset($_SERVER))
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