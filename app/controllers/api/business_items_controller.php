<?php

require 'app/controllers/api/api_controller.php';

class BusinessItemsController extends ApiController {
    public function api_view($domain, $id = null) {
        $this->respondToJSON(
            $this->BusinessItems->firstById($id)
        );
    }
}