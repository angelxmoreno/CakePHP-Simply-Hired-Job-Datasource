<?php
/*

The api array is comprised of the following required parameters:

pshid 	Publisher ID. A required value, which identifies your publisher account.
jbd 	Job-a-matic domain. A required value, which is used to identify publishers that choose to continue accepting Job-a-matic job postings.
ssty 	Search Style. A required search configuration value.
cflg 	Configuration Flag. A required search configuration value.
clip 	Client IP. A required value, which contains the IP address of the visitor.
*/

$PublisherID = '28086';
$JobAMaticDomain = 'cakephp-jobs.jobamatic.com';

$config = array(
	'Simplyhired.simplyhired_config' => array(
		'cached' => true,
		'url' => 'http://api.simplyhired.com/a/jobs-api/xml-v2',
		'required' => array(
			'pshid'	=> $PublisherID,
			'jbd'	=> $JobAMaticDomain,
			'ssty'	=> '2',
			'cflg'	=> 'r',
			'clip'	=> ''
		),
		
		'default' 	=> array(
			/*
				Location. A URL-encoded collection of terms indicating the geographic filter for the results.
				Location can be a zipcode, state, or city-state combination. Currently, there is no support for multiple location search (by the API)
			*/
			'l'	=> urlencode('New York City, New York'),
			
			/*
				Miles (Optional). A parameter indicating the number of miles from the location specified by the "l" parameter below. Miles value should
				be a number from "1" to "100". Miles represents the radius from the zip code, if specified in Location, or an approximate geographical
				"city center" if only city and state are present. If Miles is not specified, search will default to a radius of 25 miles. For jobs only
				within a specific city use "mi-exact". 
			*/
			'mi' => 5,
			
			/*
				Sort By (Optional). A parameter indicating the sort order of organic jobs (sponsored jobs have a fixed sort order).
				Valid values include:
					* rd = relevance descending (default)
					* ra = relevance ascending
					* dd = last seen date descending
					* da = last seen date ascending
					* td = title descending
					* ta = title ascending
					* cd = company descending
					* ca = company ascending
					* ld = location descending
					* la = location ascending
			*/
			'sb' => 'dd',
			
			/*
				Window Size (Optional). An integer representing the number of results returned. When available, the XML Results API will return 10 jobs by
				default. The API is limited to a maximum of 100 results per request.
			*/
			'ws' => 25,
			
			/*
				Page Number. An integer representing the page number of the results returned.
			*/
			'pn' => 1,
  		)
	)
);