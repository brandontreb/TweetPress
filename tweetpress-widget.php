<?php

/**
 * TweetPressWidget Class
 */

class TweetPressWidget extends WP_Widget {
    /** constructor */
    function TweetPressWidget() {
        parent::WP_Widget(false, $name = 'TweetPressWidget');	
    }

    /** @see WP_Widget::widget */
    function widget($args, $instance) {		
        extract( $args );
		global $wpdb,$_GET,$tp_settings;
		
        $title = apply_filters('widget_title', $instance['title']);
		$table_name = $wpdb->prefix . "tweetpress";
		$count = ((int)$instance['image_count'] > 0) ? (int)$instance['image_count'] : 6;
		$recentImages = $wpdb->get_results($wpdb->prepare("SELECT * FROM $table_name ORDER BY id DESC LIMIT %d",$count));
		
		$post_string = "?";
		if($tp_settings['page_id'] != 0) {
			$post_string = "?page_id=" . $tp_settings['page_id'] . "&";
		}
		
        ?>
              <?php echo $before_widget; ?>
                  <?php if ( $title )
                        echo $before_title . $title . $after_title; ?>
                  		<div id="tp-recent-images-widget">
							<ul id="tp-recent-images-list">
							<?php foreach($recentImages as $image): ?>								
								<li>
									<a href="<?php echo get_option ( 'siteurl' ). $post_string .'image_id='.$image->id; ?>">
									<img class="tp-recent-image-widget" src="<?php echo get_option('siteurl').GALLERYPATH.'/thumbs/'.$image->name.'" width="'.THUMBSIZE.'" height="'.THUMBSIZE; ?>">
									</a>
								</li>
							<?php endforeach; ?>
							</ul>
						</div>
						<div class="clear">&nbsp;</div>
              <?php echo $after_widget; ?>
        <?php
    }

    /** @see WP_Widget::update */
    function update($new_instance, $old_instance) {				
	$instance = $old_instance;
	$instance['title'] = strip_tags($new_instance['title']);
	$instance['image_count'] = strip_tags($new_instance['image_count']);
        return $instance;
    }

    /** @see WP_Widget::form */
    function form($instance) {				
        $title = esc_attr($instance['title']);
		$image_count = esc_attr($instance['image_count']);
        ?>
         <p>
          <label for="<?php echo $this->get_field_id('title'); ?>"><?php _e('Title:'); ?></label> 
          <input class="widefat" id="<?php echo $this->get_field_id('title'); ?>" name="<?php echo $this->get_field_name('title'); ?>" type="text" value="<?php echo $title; ?>" />		  
        </p>
		<p>
			<label for="image_count"><?php _e('Image Count:'); ?></label> 
			<input class="widefat" id="<?php echo $this->get_field_id('image_count'); ?>" name="<?php echo $this->get_field_name('image_count'); ?>" type="text" value="<?php echo $image_count; ?>" />
		</p>
        <?php 
    }

} // class TweetPressWidget

// register TweetPressWidget widget
add_action('widgets_init', create_function('', 'return register_widget("TweetPressWidget");'));

?>