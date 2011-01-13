<?php

require 'lib/SimplePie.php';

class Feeds extends AppModel {
    protected $defaultScope = array(
        'recursion' => 0,
        'orm' => true
    );
    
    public function updateArticles() {
        $articles = Model::load('Articles');
        $feed = new SimplePie();
        $feed->set_cache_location(FileSystem::path('tmp/cache/simplepie'));
        $feed->set_feed_url($this->link);
        $feed->init();
        
        $items = $feed->get_items();
        foreach($items as $item) {
            if(!$articles->articleExists($item->get_id())) {
                $articles->addToFeed($this, $item);
            }
        }
    }
}