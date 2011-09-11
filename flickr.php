<?php

/*
Plugin Name: Flickr for Twitter
Version: 1.0
Plugin URI: http://www.flickr.com/jrcryer
Description: Display a user's latest photos on their word press blog
Author: James Cryer
Author URI: http://www.flickr.com/jrcryer
*/
define('MAGPIE_CACHE_ON', 1); //2.7 Cache Bug
define('MAGPIE_CACHE_AGE', 180);
define('MAGPIE_INPUT_ENCODING', 'UTF-8');
define('MAGPIE_OUTPUT_ENCODING', 'UTF-8');

class FlickrWidget extends WP_Widget {

    public function FlickrWidget() {
        parent::WP_Widget(false, $name = 'FlickrWidget');

        add_action('wp_print_footer_scripts', array($this, 'generateScripts'));
    }

    public function form($instance) {
        $title       = esc_attr($instance['title']);
        $linkedTitle = esc_attr($instance['link-title']);
        $username    = esc_attr($instance['user_id']);
        $numImages   = esc_attr($instance['num']);
        $height      = esc_attr($instance['height']);
        $width       = esc_attr($instance['width']);
        $useLightbox = esc_attr($instance['useLightbox']);
        
        ?>
            <p>
                <label for="<?php echo $this->get_field_id('title'); ?>"><?php _e('Title:'); ?>
                    <input class="widefat" id="<?php echo $this->get_field_id('title'); ?>" name="<?php echo $this->get_field_name('title'); ?>" type="text" value="<?php echo $title; ?>" />
                </label>
            </p>
            <p>
                <label for="<?php echo $this->get_field_id('link-title'); ?>"><?php _e('Link title to profile?:'); ?>
                    <input class="widefat" id="<?php echo $this->get_field_id('link-title'); ?>" name="<?php echo $this->get_field_name('link-title'); ?>" type="checkbox" <?php echo $linkedTitle ? 'checked="checked"' : '' ?> />
                </label>
            </p>
            <p>
                <label for="<?php echo $this->get_field_id('user_id'); ?>"><?php _e('User ID:'); ?>
                    <input class="widefat" id="<?php echo $this->get_field_id('user_id'); ?>" name="<?php echo $this->get_field_name('user_id'); ?>" type="text" value="<?php echo $username; ?>" />
                </label>
            </p>
            <p>
                <label for="<?php echo $this->get_field_id('num'); ?>"><?php _e('Number of images:'); ?>
                    <input class="widefat" id="<?php echo $this->get_field_id('num'); ?>" name="<?php echo $this->get_field_name('num'); ?>" type="text" value="<?php echo $numImages; ?>" />
                </label>
            </p>
            <p>
                <label for="<?php echo $this->get_field_id('height'); ?>"><?php _e('Thumbnail height:'); ?>
                    <input id="<?php echo $this->get_field_id('height'); ?>" name="<?php echo $this->get_field_name('height'); ?>" type="text" value="<?php echo $height ? $height : '60' ?>" />
                </label>
            </p>
            <p>
                <label for="<?php echo $this->get_field_id('width'); ?>"><?php _e('Thumabnail width:'); ?>
                    <input id="<?php echo $this->get_field_id('width'); ?>" name="<?php echo $this->get_field_name('width'); ?>" type="text" value="<?php echo $width ? $width : '60'; ?>" />
                </label>
            </p>
            <p>
                <label for="<?php echo $this->get_field_id('useLightbox'); ?>"><?php _e('Use lightbox:'); ?>
                    <input id="<?php echo $this->get_field_id('useLightbox'); ?>" name="<?php echo $this->get_field_name('useLightbox'); ?>" type="checkbox" <?php echo $useLightbox == 'on' ? 'checked="checked"' : ''; ?> />
                </label>
            </p>
        <?php
    }

    public function update($new_instance, $old_instance) {
        $instance = $old_instance;
	$instance['title']        = strip_tags($new_instance['title']);
        $instance['link-title']   = strip_tags($new_instance['link-title']);
        $instance['user_id']      = strip_tags($new_instance['user_id']);
        $instance['num']          = strip_tags($new_instance['num']);
        $instance['height']       = strip_tags($new_instance['height']);
        $instance['width']        = strip_tags($new_instance['width']);
        $instance['useLightbox']  = strip_tags($new_instance['useLightbox']);
        return $instance;
    }

    public function widget($args, $instance) {
        extract( $args );

        $response = $this->getFlickrFeed($instance);
        if(empty($response->items)) {
            return;
        }
        $numImages = (int)$instance['num'] ? $instance['num'] : 3;
        $title     = $this->getWidgetTitle($instance, $response->items[0]['author_uri']);
        $aImage    = array_slice($response->items, 0, $numImages);
        $content   = $this->getContent($aImage, $instance);
        
        echo sprintf(
            "%s%s%s%s%s%s",
            $before_widget,
                $before_title,
                    $title,
                $after_title,
                $content,
                '<div class="clear"></div>',
            $after_widget
        );
    }

    /**
     * Generates URL for widget
     *
     * @param array $instance
     * @param string $profileUrl
     * @return string
     */
    protected function getWidgetTitle($instance, $profileUrl) {
        $title = apply_filters('widget_title', $instance['title']);

        if($instance['link-title']) {
            $title = sprintf(
                '<a href="%s">%s</a>',
                $profileUrl, $title
            );
        }
        return $title;
    }

    /**
     * Returns the flickr feed
     * 
     * @param array $instance
     * @return string
     */
    protected function getFlickrFeed($instance) {
        include_once(ABSPATH . WPINC . '/rss.php');

        $response = fetch_rss('http://api.flickr.com/services/feeds/photos_public.gne?id='.$instance['user_id']);
        return $response;
    }

    /**
     * Returns the content of the widget
     * 
     * @param array $aImage
     * @param array $instance
     * @return string
     */
    protected function getContent($aImage, $instance) {

        $output = '<ul class="gallery">';
        foreach($aImage as $image) {
            $output.= sprintf(
                '<li><a href="%s" class="photo" title="%s"><img src="%s" width="%d" height="%d" alt="%s" /></a></li>',
                $image['link'], $image['title'], $image['link_enclosure'],
                $instance['width'], $instance['height'], $image['title']
            );
        }
        $output .= '</ul>';
        return $output;
    }

    /**
     * Checks whether the widget currently being used 
     */
    public function generateScripts() {
        $aSettings = $this->get_settings();
        $aSettings = $aSettings[$this->number];
        $incScript = isset($aSettings['useLightbox']) && $aSettings['useLightbox'] == 'on';

        if($incScript) {
            ?>
            <script type="text/javascript">
                $('#<?php echo $this->id ?> a').each(function() {
                    $(this).attr('href', $('img', $(this)).attr('src'));
                });
                $('#<?php echo $this->id ?> .gallery a').lightBox();
            </script>
            <?php
        }
    }
}
add_action('widgets_init', create_function('', 'return register_widget("FlickrWidget");'));
