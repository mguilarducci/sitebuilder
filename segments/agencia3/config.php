<?php

Config::write('Segment', array(
  'id' => 'agencia3',
  'title' => 'Agencia3',
  'items' => array('articles', 'events', 'products', 'links', 'business', 'stores'),
  'extensions' => array('store-locator', 'rss'),
  'root' => 'Index',
  'email' => array('no-reply@agencia3.com' => 'Agencia3'),
  'hideCategories' => false,
  'enableSignup' => false,
  'primaryColor' => '#000'
));
