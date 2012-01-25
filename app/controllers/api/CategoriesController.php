<?php

namespace app\controllers\api;

require_once 'app/models/categories.php';

use Model;
use Categories;

class CategoriesController extends ApiController {
	public function index() {
		$conditions = array('site_id' => $this->site()->id);

		$visibility = $this->param('visibility', 1);
		if($visibility != 'all') {
			$conditions['visibility'] = (boolean) $visibility;
		}

		$categories = Model::load('Categories')->all(array(
			'conditions' => $conditions
		));

		$etag = $this->etag($categories);
		$self = $this;

		return $this->whenStale($etag, function() use($categories, $self) {
			return $self->toJSON($categories);
		});
	}

	public function show() {
		$category = Model::load('Categories')->firstBySiteIdAndId($this->site()->id, $this->param('id'));
		$etag = $this->etag($category);
		$self = $this;

		return $this->whenStale($etag, function() use($category, $self) {
			return $self->toJSON($category);
		});
	}

	public function children() {
		$category_id = $this->param('id');

		if(!$category_id) {
			$category_id = $this->site()->rootCategory()->id;
		}

		$categories = Model::load('Categories')->recursiveByParentId($category_id, $this->param('depth', 0));
		$etag = $this->etag($categories);
		$self = $this;

		return $this->whenStale($etag, function() use($categories, $self) {
			return $self->toJSON($categories);
		});
	}

	public function create() {
		$category = new Categories($this->request->data);
		$category->site_id = $this->site->id;

		if($category->validate()) {
			$category->save();
			$this->response->status(201);
			return $this->toJSON($category);
		}
		else {
			$this->response->status(422);
		}
	}

	public function update() {
		$category = Model::load('Categories')->firstBySiteIdAndId($this->site()->id, $this->param('id'));
		$category->updateAttributes(array(
			'site_id' => $this->site()->id
		) + $this->request->data);

		if($category->validate()) {
			$category->save();
			$this->response->status(200);
			return $this->toJSON($category);
		}
		else {
			$this->response->status(422);
		}
	}

	public function destroy() {
		Model::load('Categories')->delete($this->param('id'));
		$this->response->status(200);
	}
}
