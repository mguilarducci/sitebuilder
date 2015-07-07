<?php

namespace meumobi\sitebuilder\services;

use meumobi\sitebuilder\Logger;

class Service
{
	const PRIORITY_LOW = 0;
	const PRIORITY_HIGH = 1;

	protected $options;

	public function __construct(array $options = []) {
		$this->options = $options;
	}

	// TODO this does not belong on a generic Service class. move it somewhere else
	protected function priorityCriteria()
	{
		$priorities = [
		self::PRIORITY_HIGH => ['$gte' => 1],
		self::PRIORITY_LOW => ['$exists' => false],
		];

		return $priorities[$this->options['priority']];
	}

	protected function logger()
	{
		return Logger::logger();
	}
}
