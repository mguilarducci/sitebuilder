<?php

Config::write('Segment', array(
	'id' => 'ipanemax',
	'title' => 'Ipanemax',
	'items' => array('articles', 'events', 'products', 'links', 'business', 'stores'),
	'root' => 'Index',
	'email' => array('no-reply@ipanemax.com' => 'ipanemax'),
	'extensions' =>array('store-locator', 'rss'),
	'hideCategories' => false,
	'enableSignup' => true,
	'sitePreviewUrl' => 'http://placeholder.int-meumobi.com'
));
