<?php

namespace App\Controller;

use SimplePie\SimplePie;

class RssController {
    public function getRssFeed($url) {
        $feed = new SimplePie();
        $feed->set_feed_url($url);
        $feed->enable_cache(false);
        $feed->init();
        
        $items = [];
        foreach ($feed->get_items() as $item) {
            $items[] = [
                'title' => $item->get_title(),
                'link' => $item->get_link(),
                'description' => $item->get_description(),
                'date' => $item->get_date('Y-m-d H:i:s'),
                'author' => $item->get_author() ? $item->get_author()->get_name() : null
            ];
        }
        
        return json_encode($items);
    }
} 