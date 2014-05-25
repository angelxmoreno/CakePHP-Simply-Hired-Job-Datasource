<?php

App::uses('AppHelper', 'View/Helper');

/**
 * Simplyhired Helper
 * 
 * @property HtmlHelper $Html
 */
class SimplyhiredHelper extends AppHelper {

    public $helpers = array('Html');

    public function beforeLayout($layoutFile) {
        parent::beforeLayout($layoutFile);
        $this->Html->script('http://api.simplyhired.com/c/jobs-api/js/xml-v2.js', array('inline' => false));
    }

    public function attribution() {
        return $this->_View->element('Simplyhired.attribution');
    }

    public function joblink($title, $url = null, $options = array()) {
        $options['onMouseDown'] = 'xml_sclk(this);';
        return $this->Html->link($title, $url, $options);
    }

}
