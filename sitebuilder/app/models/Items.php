<?php

namespace app\models;

use meumobi\sitebuilder\services\GeocodeItemsService;

use Config;
use Inflector;
use Model;
use \Decoda\Decoda;
use GoogleGeocoding;
use GeocodingException;
use OverQueryLimitException;
use meumobi\sitebuilder\services\UpdateFeedsService;
use meumobi\sitebuilder\WorkerManager;
use lithium\util\Collection;

Collection::formats('lithium\net\http\Media');

class Items extends \lithium\data\Model {
	const EXPORT_LIMIT = 500;
	protected $getters = array();
	protected $setters = array();
	protected $_meta = array(
		'name' => null,
		'title' => null,
		'class' => null,
		'source' => 'items',
		'connection' => 'default',
		'initialized' => false,
		'key' => '_id',
		'locked' => false
	);

	protected $_schema = array(
		'_id' => array('type' => 'id'),
		'site_id' => array('type' => 'integer', 'null' => false),
		'parent_id' => array('type' => 'integer', 'null' => false),
		'order' => array('type' => 'integer', 'default' => 0),
		'created' => array('type' => 'date', 'default' => 0),
		'modified' => array('type' => 'date', 'default' => 0),
		'published' => array('type' => 'date', 'default' => 0),
		'is_published' => array('type' => 'boolean', 'default' => true),
		'type' => array('type' => 'string', 'null' => false),
		'title' => array('type' => 'string', 'null' => false),
		'thumbnails' => array('type' => 'array', 'default' => []),
		'groups' => array('type' => 'array', 'default' => [])
	);

	protected $privateFields = ['groups'];
	protected $fields = [];
	protected $parent;

	public function breadcrumbs($entity, $category_id) {
		return Model::load('Categories')->firstById($category_id)->bredcrumbs();
	}

	public function images($entity) {
		return Model::load('Images')->allByRecord('Items', $this->id($entity));
	}

	public function image($entity) {
		return Model::load('Images')->firstByRecord('Items', $this->id($entity));
	}

	public function id($entity) {
		if($entity->_id) {
			return $entity->_id->{'$id'};
		}
	}

	public function imageModel() {
		return 'Items';
	}

	public function parent($entity) {
		return $this->parent = Model::load('Categories')->firstById($entity->parent_id);
	}

	public function resizes() {
		$config = Config::read('BusinessItems.resizes');
		if(is_null($config)) {
			$config = array();
		}

		return $config;
	}

	public function fields($entity, $site = null) {
		$fields = array_keys($this->fields);

		if ($site && !$site->private) {
			$fields = array_diff($fields, $this->privateFields);
		}

		return $fields;
	}

	public function field($entity, $field) {
		if(array_key_exists($field, $this->fields)) {
			return (object) $this->fields[$field];
		}
		else {
			return null;
		}
	}

	public function type($entity) {
		return $this->type;
	}

	public function hasAttribute($entity, $attr) {
		return !is_null($entity->{$attr});
	}

	public function hasGetter($entity, $attr) {
		return in_array($attr, $this->getters);
	}

	public function hasSetter($entity, $attr) {
		return in_array($attr, $this->setters);
	}

	public function toJSON($entity, $visitor) {
		$self = $entity->to('array');

		foreach ($this->fields as $code => $field) {
			if ($field['type'] == 'richtext'
				&& !(isset($self['format']) && $self['format'] == 'html')) {
				$parser = new Decoda($self[$code], [
					'xhtmlOutput' => true,
					'lineBreaks' => true,
					'escapeHtml' => false
				]);
				$parser->defaults();
				$parser->setStrict(false);
				$parser->whitelist('b', 'i', 'color', 'url');
				$self[$code] = '<p>' . $parser->parse() . '</p>';
			}
		}

		if ($visitor) {
			$self['groups'] = array_intersect($self['groups'], $visitor->groups());
		} else {
			unset($self['groups']);
		}

		$self['images'] = [];
		$images = $this->images($entity);
		foreach($images as $image) {
			$self['images'] []= $image->toJSON();
		}

		return $self;
	}

	public function changed($entity, $field) {
		$export = $entity->export();

		if (!$export['exists']) return true;

		if (isset($export['update'][$field])) {
			return $export['data'][$field] != $export['update'][$field];
		} else {
			return false;
		}
	}

	public function getFirst($entity) {
		return self::find('first', array(
			'conditions' => array(
				'parent_id' => $entity->parent_id,
			),
			'order' => array('order' => 'ASC')
		));
	}

	public function getLast($entity) {
		return self::find('first', array(
				'conditions' => array(
						'parent_id' => $entity->parent_id,
				),
				'order' => array('order' => 'DESC')
		));
	}

	public function moveUp($entity, $steps = 1) {
		$oldOrder = $entity->order;
		$previus = $this->findByOrder($entity, $oldOrder + $steps);

		if (!$previus) {
			return false;
		}

		$entity->order = $previus->order;
		$previus->order = $oldOrder;
		if ($entity->save() && $previus->save()) {
			return $entity->order;
		}
	}

	public function moveDown($entity, $steps = 1) {
		$oldOrder = $entity->order;
		$previus = $this->findByOrder($entity, $oldOrder - $steps);

		if (!$previus) {
			return false;
		}

		$entity->order = $previus->order;
		$previus->order = $oldOrder;
		if ($entity->save() && $previus->save()) {
			return $entity->order;
		}
	}

	public function findByOrder($entity, $order) {
		if (!(int)$order) {
			return false;
		}
		//'order' => array('created' => 'DESC')
		$item = Items::find('first', array('conditions' => array(
				'order' => $order,
				'parent_id' => $entity->parent_id,
		)));

		if (!$item) {
			$conditions = array(
					'parent_id' => $entity->parent_id,
			);

			if ($order > 0) {
				$conditions['order'] = array('>' => $order);
			} else {
				$conditions['order'] = array('<' => $order);
			}

			$item = Items::find('first', compact('conditions'));
		}
		return $item;
	}

	public static function resetItemsOrdering($parentId)
	{
		$orderMethod = "
			function orderItems(parent) {
				try	{
					var order = 0;
					db.items.find({parent_id: parseInt(parent)}).forEach( function(item) {
						item.order = ++order;
						db.items.save(item);
					});
					return order;
				} catch(err) {
					return err;
				}
			}
		";

		$db = Items::connection()->connection;
		$response = $db->execute($orderMethod, (array)$parentId);

		if (isset($response['retval'])) {
			return $response['retval'];
		}
	}

	/**
	 * Update item ordering after remove item
	 */
	public static function updateOrdering($self, $params, $chain) {
		if(isset($params['conditions']['_id'])) {
			$id = $params['conditions']['_id'];
			$item = static::find('first', array(
				'conditions' => array(
					'_id' => $id
				),
				'fields' => array(
					'order',
					'parent_id',
					'site_id'
				)
			));

			if ($item) {
				$conditions = array(
					'order' => array('>' => $item->order),
					'parent_id' => $item->parent_id,
					'site_id' => $item->site_id,
				);
				$values = array(
						'$inc' => array('order' => -1),
						);

				static::update($values, $conditions);
			}

		}
		return $chain->next($self, $params, $chain);
	}

	public static function removeImages($self, $params, $chain)
	{
		if (isset($params['conditions']['_id'])) {
			$model = Model::load('Images');
			$images = $model->allByRecord('Items', $params['conditions']['_id']);
			foreach ($images as $item) {
				$model->delete($item->id);
			}
		}
		return $chain->next($self, $params, $chain);
	}

	public static function updateTimestamps($self, $params, $chain)
	{
		$id = $params['conditions']['_id'];
		$item = static::find('first', array('conditions' => array(
			'_id' => $id
		)));
		$date = date('Y-m-d H:i:s');
		$category = $item->parent();
		$category->modified = $date;
		$category->save();

		return $chain->next($self, $params, $chain);
	}

	public static function addTimestamps($self, $params, $chain)
	{
		$item = $params['entity'];
		$time = time();
		$date = date('Y-m-d H:i:s', $time);
		$category = $item->parent();


		if (!$item->id()) {
			if (!$item->created) {
				$item->created = $date;
			}

			if (!$item->published) {
				$item->published = $date;
				$item->is_published = true;
			}

			$item->is_published = $item->published->sec <= $time;
		}

		$item->modified = $date;
		$category->modified = $date;
		$category->save();

		return $chain->next($self, $params, $chain);
	}

	public static function addThumbnails($self, $params, $chain)
	{
		$item = $params['entity'];
		$domain = 'http://'. \MeuMobi::domain();
		$images = $item->images();
		$item->thumbnails = [];//clear previous thumbs
		if ($images) {
			$item->thumbnails = array_map(function($sizeStr) use ($images, $domain) {
				$sizeStr = str_replace('#','', $sizeStr);//remove # from size
				$sizeArr = explode('x', $sizeStr);

				$size['width'] = $sizeArr[0];
				$size['height'] = $sizeArr[1];
				$size['url'] = $domain . $images[0]->link($sizeStr);

				return $size;
			}, Config::read('BusinessItems.resizes'));
		}	else if ($item->medias) {//the item don't have images, try media thumbnails
			foreach ($item->medias as $media) {
				if ($media['thumbnails']) {//tthis media have a thumb
					$item->thumbnails = $media['thumbnails'];
					break;
				}
			}
		}
		return $chain->next($self, $params, $chain);
	}

	public static function getNotGeocoded($self, $collection, $params)
	{
		$limit = $params['limit'];
		$page = $params['page'];
		$conditions = $params['conditions'];

		if (!$limit || !$page || $collection->count() >= $limit) {
			return $collection;
		}

		$conditions['geo'] = array('$size' => 2);
		//total of items successfully geocoded
		$count = $self::find('count', array(
			'conditions' => $conditions
		));

		//calculate last page with geocoded items and prevent division by 0
		if ($count > $limit) {
			$lastPage = ($count / $limit) + 1;
			$lastPage = floor($lastPage);
		} else {
			$lastPage = 1;
		}

		//current page of not geocoded items
		$currentPage = $page - $lastPage;
		$rest = ($limit * $lastPage) - $count;

		if ($currentPage) {
			$offset = ($limit * ($currentPage - 1)) + $rest;
		} else {
			$offset = 0;
			$limit = $rest;
		}

		$conditions['geo'] = 0;
		$itemsLost = $self::find('all', array(
			'conditions' => $conditions,
			'limit' => $limit,
			'offset' => $offset
		));

		if (!$collection->count()) {
			return $itemsLost;
		}

		//add items to existing collection
		while ($item = $itemsLost->next()) {
			$collection->append($item);
		}

		return $collection;
	}

	public static function addGeocode($self, $params, $chain)
	{
		$item = $params['entity'];

		if (isset($item->latitude) && isset($item->longitude)) {
			$item->geo = array((float) $item->longitude, (float) $item->latitude);
			unset($item->latitude);
			unset($item->longitude);
		} elseif ($item->changed('address') && !empty($item->address)) {
			try {
				$geocode = GoogleGeocoding::geocode($item->address, GoogleGeocoding::REGION, false);
				$location = $geocode->results[0]->geometry->location;
				$item->geo = array((float) $location->lng, (float) $location->lat);
			} catch (OverQueryLimitException $e) {
				$return = $chain->next($self, $params, $chain);
				if ($item->id()) {
					$data = array(
						'type' => 'geocode',
						'params' => array(
							'item_id' => $item->id(),
						),
					);
					$job = \app\models\Jobs::create($data);
					$job->save();
				}
				return $return;
			} catch (GeocodingException $e) {
				$item->geo = 0;
			}
		} elseif (empty($item->address)) {
			$item->geo = 0;
		}

		return $chain->next($self, $params, $chain);
	}

	public function addMediaFileSize($entity)
	{
		$hasMedias = count($entity->medias->to('array'));
		if ($entity->id() && $hasMedias) {
			WorkerManager::enqueue('media_filesize',  ['item_id' => $entity->id()]);
		}
	}

	public static function typeFinder($self, $params, $chain)
	{
		$result = $chain->next($self, $params, $chain)->rewind();

		if ($result) {
			$classname = '\app\models\items\\' . Inflector::camelize($result->type);
			return $classname::find('first', $params['options']);
		} else {
			throw new \app\models\RecordNotFoundException('item not found');
		}
	}

	public static function nearestFinder($self, $params, $chain)
	{
		$lat = (float) array_unset($params['options']['conditions'], 'lat');
		$lng = (float) array_unset($params['options']['conditions'], 'lng');

		$params['options']['conditions']['geo'] = array(
			'$near' => array($lng, $lat),
		);

		$result = $chain->next($self, $params, $chain);
		return static::getNotGeocoded($self, $result, $params['options']);
	}

	public static function withinFinder($self, $params, $chain)
	{
		$ne_lat = (float) array_unset($params['options']['conditions'], 'ne_lat');
		$ne_lng = (float) array_unset($params['options']['conditions'], 'ne_lng');
		$sw_lat = (float) array_unset($params['options']['conditions'], 'sw_lat');
		$sw_lng = (float) array_unset($params['options']['conditions'], 'sw_lng');

		$lower_left = array($sw_lng, $sw_lat);
		$upper_right = array($ne_lng, $ne_lat);

		$params['options']['conditions']['geo'] = array(
			'$within' => array('$box' => array($lower_left, $upper_right))
		);

		return $chain->next($self, $params, $chain);
	}

	public static function paginate($params = array()) {
		$defaults = array(
			'limit' => 10,
			'page'	=> 1,
			'conditions' => array(),
		);

		$params = array_merge($defaults, $params);
		$total = static::count(array('conditions' => $params['conditions']));
		$pages = $params['limit'] ? ceil($total / $params['limit']) : 1;

		if(($params['limit'] * $params['page']) > $total){
			$params['page'] = $pages;
		}

		$items = static::find('all', $params);

		if(!$items){
			return array();
		}

		$paginate = (object)$params;
		$paginate->total = $total;
		$paginate->pages = $pages;
		$paginate->class = get_called_class();
		return compact('items', 'paginate');
	}

	public static function exportTo($format = 'csv', $conditions = array()) {
		$total = static::count( array('conditions' => $conditions) );
		$pages = ceil($total / static::EXPORT_LIMIT);
		/** get fields that can be exported */
		$fields = array_filter(static::create()->fields(), function($field) {
			return !is_array($field['type']);
		});

		$toCsv = function($item) {
			echo '"' . implode('","', $item) . '"' . "\n";
			flush();
		};

		$toCsv(array_merge(array('id','type'), $fields));

		/** parse all items by chunks */
		for($page = 1; $page <= $pages; $page++) {
			$items = static::all( array(
					'conditions' => $conditions,
					'limit' => static::EXPORT_LIMIT,
					'page' => $page,
			) );

			if(!$items) {
				continue;
			}

			foreach ($items as $item) {
				$itemArray = array(
						'id' => $item->id(),
						'type' => $item->type
				);

				foreach($fields as $field) {
					$value = $item[$field];
					if ($value) {
						if ($value instanceof \lithium\core\Object) {
							$itemArray[$field] = implode(',', $value->to('array'));
						} else {
							$itemArray[$field] = $value;
						}
					} else {
						$itemArray[$field] = '';
					}
				}
				if($format == 'csv') {
					$toCsv($itemArray);
				}
			}
		}
	}
}

Items::applyFilter('remove', function($self, $params, $chain) {
	if(isset($params['conditions']['_id'])) {
		$id = $params['conditions']['_id'];
		$items = Items::find('all', array(
			'conditions' => array('related' => $id)
		));
		foreach($items as $item) {
			$index = array_search($id, $item->related->to('array'));
			unset($item->related[$index]);
			$item->related = $item->related->to('array');
			$item->save();
		}
	}
	return $chain->next($self, $params, $chain);
});

Items::applyFilter('remove', function($self, $params, $chain) {
	return Items::updateOrdering($self, $params, $chain);
});

Items::applyFilter('remove', function($self, $params, $chain) {
	return Items::removeImages($self, $params, $chain);
});

Items::applyFilter('remove', function($self, $params, $chain) {
	return Items::updateTimestamps($self, $params, $chain);
});

Items::applyFilter('save', function($self, $params, $chain) {
	return Items::addTimestamps($self, $params, $chain);
});

Items::applyFilter('save', function($self, $params, $chain) {
	return Items::addThumbnails($self, $params, $chain);
});

Items::finder('type', function($self, $params, $chain) {
	return Items::typeFinder($self, $params, $chain);
});
