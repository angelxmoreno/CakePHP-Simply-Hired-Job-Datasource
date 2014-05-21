<?php

class SimplyhiredController extends SimplyhiredAppController {

    public $uses = array('Simplyhired.Simplyhired');

    public function index() {
        /*
          $params1 = array(
          'conditions' => array(
          'location' => 'San Francisco, California',
          'keywords' => 'php',
          'miles' => 'exact',
          )
          );

          $jobs1 = $this->Simplyhired->find('count', $params1);
          debug($jobs1);
         */
        $params2 = array(
            'conditions' => array(
                'title' => 'Senior Developer',
                'location' => '10018',
                'query' => "Wordpress",
                'miles' => 5,
            ),
            'order' => array(
                'last_seen_date' => 'DESC',
            ),
            'limit' => 3,
            'page' => 1,
            //'fields' => array('job_key', 'title')
        );
        $jobs2 = $this->Simplyhired->find('count', $params2);
        debug($jobs2);

        $jobs2 = $this->Simplyhired->find('first', $params2);
        debug($jobs2);

        $jobs2 = $this->Simplyhired->find('all', $params2);
        debug($jobs2);
        exit;
    }

}
