<?php
class SimplyhiredableBehavior extends ModelBehavior {
	public $datasourceName = 'Simplyhired.Simplyhired';
	public $configFile = 'Simplyhired.simplyhired_config';
	
	function setup(&$Model, $settings) {
		//attach $Model to datasource
		$sources = ConnectionManager::sourceList();
		if (!in_array($this->datasourceName, $sources)) {
			Configure::load($this->configFile);
			$config = am(array('datasource' => $this->datasourceName), Configure::read($this->configFile));
			ConnectionManager::create($this->datasourceName, $config);
		}
		$Model->useDbConfig = $this->datasourceName;
	}
}