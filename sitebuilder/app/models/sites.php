<?php
use meumobi\sitebuilder\entities\Skin;
use meumobi\sitebuilder\repositories\SkinsRepository;
use meumobi\sitebuilder\repositories\VisitorsRepository;
use app\models\Plugins;

require_once 'app/models/categories.php';
require_once 'lib/geocoding/GoogleGeocoding.php';

class Sites extends AppModel
{
	protected $getters = array(
		'feed_url',
		'feed_title',
		'domain',
		'domains'
	);

	protected $beforeSave = array(
		'getLatLng',
		'addSlugDomain',
		'trimFields',
		'cleanDomainLinks'
	);

	protected $afterSave = array(
		'saveLogoAndAppleTouchIcon',
		'updateFeed',
		'saveDomains',
		'createRelation'
	);

	protected $beforeDelete = array(
		'deleteImages',
		'deleteCategories',
		'deleteLogo',
		'removeUsers',
		'deleteCustomSkin'
	);

	protected $validates = array(
		'slug' => array(
			array(
				'rule' => array('unique', 'slug'),
				'message' => 'This domain is not available'
			),
			array(
				'rule' => 'asciiOnly',
				'message' => 'The domain can only contains lowercase, dashes and underscores'
			),
			array(
				'rule' => 'blacklist',
				'message' => 'This domain is not available'
			)
		),
		'title' => array(
			'rule' => 'notEmpty',
			'message' => 'A non empty title is required'
		),
		'logo' => array(
			array(
				'rule' => array('fileUpload', 1, array('jpg', 'jpeg', 'gif', 'png')),
				'message' => 'Only valid gif, jpg, jpeg or png are allowed'
			),
			array(
				'rule' => array('validImage'),
				'message' => 'Only valid gif, jpg, jpeg or png are allowed'
			)
		),
		'description' => array(
			array(
				'rule' => array('maxLength', 500),
				'message' => 'The description of the site could contain 500 chars max.'
			)
		),
	);

	protected $categories;
	protected $plugins;

	public function __construct($data = array())
	{
		parent::__construct($data);

		if (!isset($this->data['timezone']) or !$this->data['timezone']) {
			$this->timezone = 'America/Sao_Paulo';
		}
	}

	public function newsCategory()
	{
		return Model::load('Categories')->first(array(
			'conditions' => array('site_id' => $this->id, 'visibility' => -1)
		));
	}

	public function newsExtension()
	{
		$category = $this->newsCategory();
		if ($category)
		return \app\models\extensions\Rss::find('first', array(
			'conditions' => array('category_id' => $category->id)
		));
	}

	public function news()
	{
		$category = $this->newsCategory();
		if (!$category)
			return [];
		return \app\models\items\Articles::find('all', array(
			'conditions' => array(
				'site_id' => $this->id,
				'parent_id' => $category->id
			),
			'limit' => 10,
			'order' => array('published' => 'DESC')
		))->to('array');
	}

	public function feed_url()
	{
		if ($extension = $this->newsExtension()) {
			return $extension->url;
		}
	}

	public function feed_title()
	{
		if ($category = $this->newsCategory()) {
			return $category->title;
		}
	}

	public function domain()
	{
		try {
			$domain = Model::load('SitesDomains')->first(array(
				'conditions' => array(
					'site_id' => $this->id,
				),
				'order' => '`id` DESC',
			));
			return $domain ? $domain->domain : null;
		} catch (Exception $e) {
			return null;
		}
	}

	public function domains()
	{
		$domains = array();
		if ($siteDomains = Model::load('SitesDomains')->allBySiteId($this->id)) {
			foreach ($siteDomains as $item) {
				$domains[$item->id] = $item->domain;
			}
		}
		return $domains;
	}

	public function firstByDomain($domain)
	{
		$sql = 'SELECT s.* FROM sites s
			INNER JOIN sites_domains d ON s.id = d.site_id
			WHERE d.domain = ?';
		$query = $this->connection()->query($sql, array($domain));
		$site = $query->fetch(PDO::FETCH_ASSOC);

		if ($site) return new Sites($site);
	}

	public function photos()
	{
		return Model::load('Images')->allByRecord('SitePhotos', $this->id);
	}

	public function photo()
	{
		return Model::load('Images')->firstByRecord('SitePhotos', $this->id);
	}

	public function appleTouchIcon()
	{
		return Model::load('Images')->firstByRecord('SiteAppleTouchIcon', $this->id);
	}

	public function availableVisitorsGroups()
	{
		$repository = new VisitorsRepository();
		return $repository->findAvailableGroupsBySite($this->id);
	}

	public function splashScreen()
	{
		return Model::load('Images')->firstByRecord('SiteSplashScreens', $this->id);
	}

	public function logo()
	{
		return Model::load('Images')->firstByRecord('SiteLogos', $this->id);
	}

	public function link()
	{
		return 'http://' . $this->domain();
	}

	public function skin()
	{
		if ($this->skin) {
			$skinRepo = new SkinsRepository();
			return $skinRepo->find($this->skin);
		}
	}

	public function plugins()
	{
		if ($this->plugins) return $this->plugins;

		return $this->plugins = Plugins::find('all', array('conditions' => array(
			'site_id' => $this->id,
		))); 
	}

	public function categories()
	{
		if ($this->categories) return $this->categories;

		return $this->categories = Model::load('Categories')->all(array(
			'conditions' => array('site_id' => $this->id, 'visibility >' => -1),
			'order' => '`order`'
		));
	}

	public function visibleCategories()
	{
		return Model::load('Categories')->all(array(
			'conditions' => array('site_id' => $this->id, 'visibility' => 1),
			'order' => '`order`'
		));
	}

	public function userRole()
	{
		try {
			if ($this->role) {
				return $this->role;
			}
		} catch(Exception $e) {

		}

		$model = Model::load('UsersSites')->first(array(
			'user_id' => Auth::user()->id(),
			'site_id' => $this->id,
			'segment' => MeuMobi::segment(),
		));

		if ($model) {
			$this->role = $model->role;
		}
		return $this->role;
	}


	public function users($removeCurrent = false)
	{
		$usersIds = Model::load('UsersSites')->getAllUsers($this);
		$users = Model::load('Users')->allById($usersIds);

		if ($removeCurrent) {
			$current = Auth::user();
			foreach ($users as $key => $user) {
				if ($current->id == $user->id) {
					unset($users[$key]);
				}
			}
		}

		return $users;
	}

	public function removeUser($userId)
	{
		if ($user = Model::load('Users')->firstById($userId)) {
			return Model::load('UsersSites')->remove($user, $this);
		}
	}

	public function itemTypes()
	{
		return MeuMobi::currentSegment()->items;
	}

	public function hasManyTypes()
	{
		return is_array($this->itemTypes());
	}

	public function dateFormats()
	{
		return array('d/m/Y' => 'DD/MM/YYYY', 'm/d/Y' => 'MM/DD/YYYY',
			'Y-m-d' => 'YYYY-MM-DD');
	}

	public function timezones()
	{
		$timezones = DateTimeZone::listIdentifiers();
		$options = array();

		foreach ($timezones as $tz) {
			$options [$tz] = str_replace('_', ' ', $tz);
		}

		return $options;
	}

	public function timezoneId() {
		if (!empty($this->timezone)) {
			return $this->timezone;
		} else {
			return 'America/Sao_Paulo';
		}
	}

	public function timezone()
	{
		$tz_site = new DateTimeZone($this->timezone);
		$tz_server = new DateTimeZone(date_default_timezone_get());
		$time_site = new DateTime('now', $tz_site);
		$time_server = new DateTime('now', $tz_server);

		return $tz_server->getOffset($time_site) / 3600;
	}

	public function validateTheme()
	{
		return $this->theme && $this->skin;
	}

	public function toJSONPerformance()
	{
		$exportFields = array('id', 'segment', 'skin', 'date_format',
			'title', 'description', 'timezone');
		$data = array_intersect_key($this->data, array_flip($exportFields));

		$data['created_at'] = $this->created;
		$data['updated_at'] = $this->modified;
		$data['description'] = nl2br($data['description']);
		$data['webputty_token'] = $this->css_token;
		$data['analytics_token'] = $this->google_analytics;
		$data['android_app_id'] = $this->android_app_id;
		$data['ios_app_id'] = $this->ios_app_id;
		$data['latest_app_version'] = $this->latest_app_version;
		$data['landing_page'] = $this->landing_page;
		$data['stock_symbols'] = $this->stock_symbols;
		$data['language'] = $this->language;

		if (strpos($this->google_analytics,',') !== false) {
			$data['analytics_token'] =	explode(",", $this->google_analytics);
		}

		if ($logo = $this->logo()) {
			$data['logo'] = $logo->link();
		} else {
			$data['logo'] = null;
		}

		$data['photos'] = array();
		$photos = $this->photos();
		foreach ($photos as $photo) {
			$data['photos'] []= $photo->toJSON();
		}

		$data['apple_touch_icon'] = '';
		if ($appleTouchIcon = $this->appleTouchIcon()) {
			$data['apple_touch_icon'] = $appleTouchIcon->link();
		}

		$data['splash_screen'] = '';
		if ($splashScreen = $this->splashScreen()) {
			$data['splash_screen'] = $splashScreen->link();
		}
		return $data;
	}

	public function toJSON()
	{
		$data = array_merge($this->data, array(
			'logo' => null,
			'apple_touch_icon' => null,
			'photos' => array(),
			'timezone' => $this->timezone()
		));

		unset($data['private']); //remove private attr

		if ($logo = $this->logo()) {
			$data['logo'] = $logo->link();
		}

		if ($appleTouchIcon = $this->appleTouchIcon()) {
			$data['apple_touch_icon'] = $appleTouchIcon->link();
		}

		$photos = $this->photos();
		foreach ($photos as $photo) {
			$data['photos'] []= $photo->toJSON();
		}

		$data['description'] = nl2br($data['description']);
		$data['address'] = nl2br($data['address']);
		$data['timetable'] = nl2br($data['timetable']);

		return $data;
	}

	public function addDefaultPhotos()
	{
		$imagesDir = APP_ROOT . '/sitebuilder/assets/images/site_placeholders/';
		$images = glob($imagesDir . '{*.jpg,*.gif,*.png}', GLOB_BRACE);
		foreach ($images as $img) {
			$img = Mapper::url('/images/shared/site_placeholders/' . basename($img), true);
			$image = Model::load('Images')->download(new SitePhotos($this->id), $img, array(
				'visible' => 1,
				'description' => 'edit legend',
			));
		}
	}

	protected function removeUsers($id)
	{
		Model::load('UsersSites')->removeSite($id);

		return $id;
	}

	protected function trimFields($data)
	{
		$fieldsToTrim = array('description', 'timetable', 'address', 'email',
			'phone', 'website', 'google_analytics', 'css_token', 'facebook',
			'twitter');

		foreach ($fieldsToTrim as $field) {
			if (isset($data[$field])) {
				$data[$field] = trim($data[$field]);
			}
		}

		return $data;
	}

	protected function cleanDomainLinks($data)
	{
		$fieldsToClean = array('facebook', 'twitter', 'website',
			'android_app_id', 'ios_app_id');

		foreach ($fieldsToClean as $field) {
			if (isset($data[$field]) && $data[$field] == 'http://') {
				$data[$field] = '';
			}
		}
		return $data;
	}

	protected function addSlugDomain($data)
	{
		$new = !isset($data['id']);
		//add the slug domain if new site
		if ($new) {
			$data['domains'][] = $data['slug'] . '.' . MeuMobi::domain();
		}
		return $data;
	}

	protected function saveDomains($created)
	{
		//handle default error if domains not setted
		try {
			$domains = $this->domains;
		} catch (Exception $e) {
			return $created;
		}
		foreach ($domains as $id => $domain) {
			//load domain if alredy exists or create a new one
			if (!$siteDomain = Model::load('SitesDomains')->firstByIdAndSiteId($id, $this->id)) {
				$siteDomain = new SitesDomains();
			}
			$siteDomain->domain = $domain;
			$siteDomain->site_id = $this->id;
			if ($siteDomain->validate()) {
				$siteDomain->save();
			} else {
				Session::writeFlash('error', s('The domain %s is not available', $domain));
			}
		}
		return $created;
	}

	protected function getLatLng($data)
	{
		if (array_key_exists('address', $data)) {
			if ($this->id) {
				$original = $this->firstById($this->id);

				if ($original->address == $data['address']) {
					return $data;
				}
			}

			if (empty($data['address'])) {
				$data['latitude'] = $data['longitude'] = null;
			} else {
				try {
					$geocode = GoogleGeocoding::geocode($data['address']);
					$location = $geocode->results[0]->geometry->location;
					$data['latitude'] = $location->lat;
					$data['longitude'] = $location->lng;
				} catch (GeocodingException $e) {
					$data['latitude'] = $data['longitude'] = null;
				}
			}
		}

		return $data;
	}

	protected function createNewsCategory()
	{
		$category = new Categories(array(
			'site_id' => $this->id,
			'parent_id' => null,
			'type' => 'articles',
			'title' => s('NEWS'),
			'visibility' => -1,
			'populate' => 'auto',
		));
		$category->save();

		$extension = \app\models\extensions\Rss::create();
		$extension->set(array(
			'site_id' => $this->id,
			'category_id' => $category->id,
			'enabled' => 0
		));
		$extension->save();
		return $category;
	}

	protected function updateFeed($created)
	{
		if (!isset($this->data['feed_url'])) return;
		if (!$category = $this->newsCategory()) {
			$category = $this->createNewsCategory();
		}
		$category->title = $this->data['feed_title'];
		$category->save();

		$extension = $this->newsExtension();
		$extension->url = $this->data['feed_url'];
		$extension->enabled = (int) !empty($this->data['feed_url']);
		$extension->save();
	}

	protected function deleteLogo($id)
	{
		$model = Model::load('Images');
		$images = $model->allByRecord('SiteLogos', $id);
		$this->deleteSet($model, $images);

		return $id;
	}

	protected function deleteCategories($id)
	{
		$model = Model::load('Categories');
		$this->deleteSet($model, $model->all(array(
			'conditions' => array('site_id' => $this->id, 'parent_id' => null)
		)));

		return $id;
	}

	protected function deleteCustomSkin($id)
	{
		$skin = $this->skin();
		if ($skin && $skin->parentId()) {
			$skinRepo = new SkinsRepository();
			$skinRepo->destroy($skin);
		}
		return $id;
	}

	protected function createRelation($created)
	{
		if ($created) {
			Model::load('UsersSites')->add(Auth::user(), $this);
			Auth::user()->site($this->id);
		}
	}

	protected function saveLogoAndAppleTouchIcon()
	{
		$upload = function ($image, $imageModel, $debug = false) {
			if (isset($this->data[$image]) && !$this->data[$image]['error']) {
				if ($item = $this->$image()) {
					Model::load('Images')->delete($item->id);
				}
				Model::load('Images')->upload(new $imageModel($this->id), $this->data[$image], array('visible' => 1));
			}
		};
		$upload('logo', 'SiteLogos');
		$upload('appleTouchIcon', 'SiteAppleTouchIcon');
		$upload('splashScreen', 'SiteSplashScreens', 1);
	}

	protected function savePhoto()
	{
		if (array_key_exists('photo', $this->data)) {
			foreach ($this->data['photo'] as $photo) {
				if ($photo['error'] == 0) {
					Model::load('Images')->upload(new SitePhotos($this->id), $photo);
				}
			}
		}
	}

	protected function blacklist($value)
	{
		$blacklist = Config::read('Sites.blacklist');
		return !in_array($value, $blacklist);
	}
}

class SiteImages
{
	public $id;

	public function __construct($id = null)
	{
		$this->id = $id;
	}

	public function id()
	{
		return $this->id;
	}
}

class SiteLogos extends SiteImages
{
	public function resizes()
	{
		$config = Config::read('SiteLogos.resizes');
		if (is_null($config)) {
			$config = array();
		}

		return $config;
	}

	public function imageModel()
	{
		return 'SiteLogos';
	}
}

class SiteAppleTouchIcon extends SiteImages
{
	public function resizes()
	{
		$config = Config::read('SiteAppleTouchIcon.resizes');
		if (is_null($config)) {
			$config = array();
		}

		return $config;
	}

	public function imageModel()
	{
		return 'SiteAppleTouchIcon';
	}
}

class SiteSplashScreens extends SiteImages
{
	public function resizes()
	{
		return array();
	}

	public function imageModel()
	{
		return 'SiteSplashScreens';
	}
}

class SitePhotos extends SiteImages
{
	public function resizes()
	{
		$config = Config::read('SitePhotos.resizes');
		if (is_null($config)) {
			$config = array();
		}

		return $config;
	}

	public function imageModel()
	{
		return 'SitePhotos';
	}

	public function firstById($id)
	{
		return new self($id);
	}

}
