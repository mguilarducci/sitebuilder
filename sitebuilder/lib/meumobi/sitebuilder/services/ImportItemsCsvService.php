<?php

namespace meumobi\sitebuilder\services;

use lithium\data\Connections;
use app\models\Jobs;

class ImportItemsCsvService extends ImportCsvService {
	protected $category;

	public function call()
	{
		$startTime = microtime(true);
		while ($job = $this->getNextJob()) {
			$log['job_id'] = (string) $job->_id;
			try {
				$category = \Model::load('categories')
					->firstById($job->params->category_id);
				$this->setFile(APP_ROOT . $job->params->file);
				$this->setMethod($job->params->method);
				$this->setCategory($category);
				$log['total_items'] = $this->import();
				$this->logger()->info('csv imported ', $log);
			} catch (\Exception $e) {
				$this->logger()->error('csv import error', [
					'exception' => get_class($e),
					'message' => $e->getMessage(),
					'trace' => $e->getTraceAsString()
				] + $log);
			}
			$job->delete();
		}
		$stats['elapsed_time'] = microtime(true) - $startTime;
		$this->logger()->info('csv import finished ', $stats);
	}

	public function import()
	{
		$startTime = time();
		$imported = 0;
		$classname = '';
		while ($item = $this->getNextItem()) {
			$classname = '\app\models\items\\' .
				\Inflector::camelize($this->getCategory()->type);
			if (isset($item['id'])) {
				$record = $classname::find('first', array(
					'conditions' => array(
						'_id' => $item['id']
					),
				));
			}
			if (!$record) {
				$record = $classname::create();
			}

			$item['parent_id'] = $this->getCategory()->id;
			$item['site_id'] = $this->getCategory()->site_id;
			$item['type'] = $this->getCategory()->type;
			$record->set($item);
			$record->save();
			$imported++;
		}

		if (self::EXCLUSIVE == $this->method && $imported) {
			//remove all items creates before import start
			$classname::remove(
				array(
					'parent_id' => $this->getCategory()->id,
					'created' => array(
						'$lt' => new \MongoDate($startTime),
					),
				)
			);
		}
		fclose($this->getFile());
		unlink($this->filePath);
		return $imported;
	}

	public function setCategory(\Categories $category)
	{
		$this->category = $category;
	}

	public function getCategory()
	{
		if (!$this->category) {
			throw new \Exception("category not set");
		}
		return $this->category;
	}
}
