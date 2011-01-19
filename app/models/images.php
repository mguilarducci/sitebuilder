<?php

class Images extends AppModel {
    protected $beforeDelete = array('deleteFile');
    
    public function upload($model, $image) {
        $this->saveImage('uploadFile', $model, $image);
    }
    
    public function download($model, $image) {
        $this->saveImage('downloadFile', $model, $image);
    }
    
    
    public function allByRecord($model, $fk) {
        return $this->all(array(
            'conditions' => array(
                'model' => $model,
                'foreign_key' => $fk
            )
        ));
    }
    
    public function firstByRecord($model, $fk) {
        return $this->first(array(
            'conditions' => array(
                'model' => $model,
                'foreign_key' => $fk
            )
        ));
    }
    
    public function link() {
        $path = String::insert('/images/:model/:filename', array(
            'model' => Inflector::underscore($this->model),
            'filename' => $this->path
        ));
        return Mapper::url($path, true);
    }
    
    protected function saveImage($method, $model, $image) {
        if(!$this->transactionStarted()) {
            $transaction = true;
            $this->begin();
        }
        
        try {
            $this->id = null;
            $this->save(array(
                'model' => get_class($model),
                'foreign_key' => $model->id
            ));
            
            $path = $this->getPath($model);
            $filename = $this->{$method}($model, $image);
            
            $this->resizeImage($model, $path, $filename);
            
            $info = $this->getImageInfo($path, $filename);
            $this->save($info);
            
            if($transaction) {
                $this->commit();
            }
        }
        catch(Exception $e) {
            if($transaction) {
                $this->rollback();
            }
        }
    }
    
    protected function uploadFile($model, $image) {
        require_once 'lib/utils/FileUpload.php';

        $uploader = new FileUpload();
        $uploader->path = $this->getPath($model);

        return $uploader->upload($image, String::insert(':id.:extension', array(
            'id' => $this->id
        )));
    }

    protected function downloadFile($model, $image) {
        require_once 'lib/utils/FileDownload.php';

        $downloader = new FileDownload();
        $downloader->path = $this->getPath();

        return $downloader->download($image, String::insert(':id.:extension', array(
            'id' => $this->id
        )));
    }

    protected function resizeImage($model, $path, $filename) {
        require_once 'lib/phpthumb/ThumbLib.inc.php';
        $fullpath = Filesystem::path('public/' . $path . '/' . $filename);
        $resizes = $model->resizes();
        $modes = array(
            '' => 'resize',
            '#' => 'adaptiveResize',
            '!' => 'cropFromCenter'
        );
        
        foreach($resizes as $resize) {
            preg_match('/^(\d+)x(\d+)(#|!|>)$/', $resize, $options);
            list($resize, $w, $h, $mode) = $options;

            $image = PhpThumbFactory::create($fullpath);

            $method = $modes[$mode];
            $image->{$method}($w, $h);

            $image->save(String::insert(':path/:wx:h_:filename', array(
                'path' => Filesystem::path('public/' . $path),
                'filename' => $filename,
                'w' => $w,
                'h' => $h,
            )));
        }
    }
    
    protected function deleteFile($id) {
        $self = $this->firstById($id);
        
        Filesystem::delete(String::insert('public/:path/:filename', array(
            'path' => $this->getPath($this->model),
            'filename' => $this->path
        )));
        
        return $id;
    }
    
    protected function getImageInfo($path, $filename) {
        $image = new Imagick($path . '/' . $filename);
        $size = $image->getImageLength();
        return array(
            'path' => $filename,
            'type' => $image->getImageMimeType(),
            'filesize' => $size,
            'filesize_octal' => decoct($size)
        );
    }
    
    protected function getPath($model) {
        if(!is_string($model)) {
            $model = get_class($model);
        }
        
        return String::insert('images/:model', array(
            'model' => Inflector::underscore($model)
        ));
    }
}

class ImageNotFoundException extends Exception {}