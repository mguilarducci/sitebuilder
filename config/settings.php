<?php

Config::write('App.environment', 'development');
Config::write('App.encoding', 'utf-8');
Config::write('Security.salt', '37b1ffe6afe7577a90f1ac2098605d5711fdc59f');
Config::write('Debug.level', 3);

require 'config/environments/' . Config::read('App.environment') . '.php';
require 'config/app/segments.php';
require 'config/app/business_items.php';

Debug::reportErrors(Config::read('Debug.level'));

Config::write('Articles.limit', 20);

Config::write('SiteLogos.resizes', array('100x100#'));
Config::write('Articles.resizes', array('100x100'));