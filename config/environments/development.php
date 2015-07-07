<?php

ini_set('error_reporting', E_ALL ^E_DEPRECATED);
ini_set('display_errors', 'On');

Config::write('Log.level', Psr\Log\LogLevel::DEBUG);

Config::write('Mail.preventSending', true);

Config::write('Api.ignoreAuth', true);
Config::write('Preview.url', 'http://placeholder.int-meumobi.com');
Config::write('Sites.domain', 'int-meumobi.com');

Config::write('Themes.url', 'http://meu-template-engine.int-meumobi.com/themes.json');

Config::write('PushWoosh.debug', true);
