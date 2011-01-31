<?php

class SimplyhiredController extends SimplyhiredAppController {

	var $name = 'Simplyhired';
	var $uses = array('Simplyhired.Simplyhired');
	
	function index(){
		$params1 = array(
			'conditions' => array(
				'location' => 'San Francisco, California',
				'keywords' => 'php',
				'miles' => 'exact',
			)
		);
		$jobs1 = $this->Simplyhired->find('count', $params1);
		debug($jobs1);
		$params2 = array(
			'conditions' => array(
				'location' => '10018',
				'keywords' => array(
					'php', 'cakephp',
				),
				'miles' => 5,
			),
			'order' => array(
				'last_seen_date' => 'DESC',
			),
			'limit' => 3,
			'page' => 1
		);
		$jobs2 = $this->Simplyhired->find('all', $params2);
		debug($jobs2);
		exit;

	}
}
?>