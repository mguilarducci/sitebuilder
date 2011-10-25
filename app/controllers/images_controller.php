<?php

require_once 'app/models/sites.php';

use app\models\Items;

class ImagesController extends AppController {
    public function delete($id = null) {
        $this->Images->delete($id);
        if($this->isXhr()) {
            $this->respondToJSON(array('result' => 'ok'));
        }
        else {
            $this->redirect($_SERVER['HTTP_REFERER']);
        }
    }

    public function add(){
        $this->layout = false;

        if(!empty($this->data)) {
            $fk = $this->data['foreign_key'];

            if($this->data['model'] == 'Items') {
                $record = Items::find('first', array('conditions' => array(
                    '_id' => $fk
                )));
            }
            else {
                $record = Model::load($this->data['model'])->firstById($fk);
            }

            $image = $this->Images->upload($record, $this->data['photo'], array(
                'visible' => 1
            ));
            $this->set(array(
                'timestamp' => $this->data['timestamp'],
                'image' => $image
            ));
        }
    }
}
