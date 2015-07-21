<?php

namespace meumobi\sitebuilder\entities;

use meumobi\sitebuilder\entities\VisitorDevice;
use meumobi\sitebuilder\repositories\RecordNotFoundException;
use meumobi\sitebuilder\Site;
use MongoId;
use Security;

class Visitor extends Entity
{
	protected $siteId;
	protected $email;
	protected $firstName;
	protected $lastName;
	protected $hashedPassword;
	protected $authToken;
	protected $shouldRenewPassword = false;
	protected $devices = [];
	protected $groups = [];
	protected $lastLogin;
	protected $created;
	protected $modified;

	public function siteId()
	{
		return (int) $this->siteId;
	}

	public function email()
	{
		return $this->email;
	}

	public function firstName()
	{
		return $this->firstName;
	}

	public function lastName()
	{
		return $this->lastName;
	}

	public function fullName()
	{
		return join(' ', [ $this->firstName, $this->lastName ]);
	}

	public function setPassword($password, $shouldRenewPassword = false)
	{
		if (!empty($password)) {
			$this->shouldRenewPassword = $shouldRenewPassword;
			$this->generateAuthToken();
			return $this->hashedPassword = $this->hashPassword($password);
		}
	}

	public function passwordMatch($password)
	{
		return $this->hashPassword($password) == $this->hashedPassword;
	}

	protected function hashPassword($password)
	{
		return Security::hash($password, 'sha1');
	}

	public function hashedPassword()
	{
		return $this->hashedPassword;
	}

	public function authToken()
	{
		return $this->authToken ?: $this->generateAuthToken();
	}

	protected function generateAuthToken()
	{
		return $this->authToken = Security::hash(time() . $this->email(), 'sha1');
	}

	public function lastLogin()
	{
		return $this->lastLogin;
	}

	public function setLastLogin($lastLogin)
	{
		$this->lastLogin = $lastLogin;
	}

	public function created()
	{
		return $this->created;
	}

	public function setCreated($created)
	{
		$this->created = $created;
	}

	public function modified()
	{
		return $this->modified;
	}

	public function setModified($modified)
	{
		$this->modified = $modified;
	}

	public function shouldRenewPassword()
	{
		return $this->shouldRenewPassword;
	}

	public function isPasswordValid()
	{
		return !$this->shouldRenewPassword;
	}

	public function devices()
	{
		return array_unique($this->devices);
	}

	public function addDevice(VisitorDevice $device)
	{
		if (!in_array($device, $this->devices)) $this->devices []= $device;
	}

	public function findDevice($uuid) {
		return current(array_filter($this->devices, function($device) use($uuid) {
			return $device->uuid() == $uuid;
		}));
	}

	public function groups()
	{
		return array_unique($this->groups);
	}

	public function setGroups($groups)
	{
		if (is_string($groups)) {
			$groups = $groups ? array_map('trim', explode(',', $groups)) : [];
		}
		$this->groups = $groups;
	}

	public function addGroup($group)
	{
		if (!in_array($group, $this->groups)) $this->groups []= $group;
	}

	public function site()
	{
		return Site::find($this->siteId);
	}
}
