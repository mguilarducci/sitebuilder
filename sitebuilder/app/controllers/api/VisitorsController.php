<?php

namespace app\controllers\api;

use lithium\storage\Session;
use meumobi\sitebuilder\repositories\VisitorsRepository;
use meumobi\sitebuilder\entities\Visitor;
use meumobi\sitebuilder\entities\VisitorDevice;
use meumobi\sitebuilder\presenters\api\VisitorPresenter;

class VisitorsController extends ApiController
{
	protected $skipBeforeFilter = ['requireVisitorAuth'];

	public function login()
	{
		$email = $this->request->get('data:email');
		$password = $this->request->get('data:password');

		$repository = new VisitorsRepository();
		$visitor = $repository->findForAuthentication($this->site()->id, $email, $password);

		if ($visitor) {
			$device = $this->request->get('data:device');
			$device = new VisitorDevice([
				'uuid' => $device['uuid'],
				'model' => $device['model'],
			]);
			$visitor->addDevice($device);
			$visitor->setLastLogin(date('Y-m-d H:i:s'));
			$repository->update($visitor);
			return [
				'token' => $visitor->authToken(),
				'visitor' => VisitorPresenter::present($visitor)
			];
		} else {
			throw new UnAuthorizedException('Invalid visitor');
		}
	}

	public function show()
	{
		$this->requireVisitorAuth();
		return VisitorPresenter::present($this->visitor());
	}

	public function update()
	{
		$this->requireVisitorAuth();
		$currentPassword = $this->request->get('data:current_password');
		$newPassword = $this->request->get('data:password');
		$repository = new VisitorsRepository();
		$visitor = $this->visitor();

		if ($visitor && $visitor->passwordMatch($currentPassword)) {
			$visitor->setPassword($newPassword);
			$repository->update($visitor);
			return [
				'success' => true,
				'visitor' => VisitorPresenter::present($visitor)
			];
		} else {
			throw new ForbiddenException('Invalid visitor');
		}
	}

	public function add_device()
	{
		$this->requireVisitorAuth();
		$repository = new VisitorsRepository();
		$visitor = $this->visitor();
		$device = new VisitorDevice([
			'uuid' => $this->request->get('data:uuid'),
			'pushId' => $this->request->get('data:push_id'),
			'model' => $this->request->get('data:model')
		]);
		$visitor->addDevice($device);
		$repository->update($visitor);
		return [ 'success' => true ];
	}

	public function update_device()
	{
		$this->requireVisitorAuth();
		$repository = new VisitorsRepository();
		$visitor = $this->visitor();
		$device = $visitor->findDevice($this->request->get('params:device_id'));
		$device->update($this->request->data);
		$repository->update($visitor);
		return [ 'success' => true ];
	}
}
