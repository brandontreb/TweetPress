<?php

	function tp_gallery() {
		global $wpdb,$_GET,$tp_settings;
		
		$mainImage = null;
		$table_name = $wpdb->prefix . "tweetpress";
		
		if(isset($_GET['delete_image_id']) && is_user_logged_in()) {
			$image = $wpdb->get_row($wpdb->prepare("SELECT * FROM $table_name WHERE id = %d",$_GET['delete_image_id']));
			if(file_exists(UPLOADDIR . 'tweetpress/thumbs/' . $image->name))
				unlink(UPLOADDIR . 'tweetpress/thumbs/' . $image->name);
			if(file_exists(UPLOADDIR . 'tweetpress/' . $image->name))
				unlink(UPLOADDIR . 'tweetpress/' . $image->name);
			$wpdb->query($wpdb->prepare("DELETE FROM $table_name WHERE id = %d",$image->id));
		}
		
		if(isset($_GET['image_id'])) {
			$mainImage = $wpdb->get_row($wpdb->prepare("SELECT * FROM $table_name WHERE id = %d",$_GET['image_id']));
		} else {
			$mainImage = $wpdb->get_row($wpdb->prepare("SELECT * FROM $table_name WHERE id IN (SELECT MAX(id) FROM $table_name)"));
		}
		
		$count = ((int)$tp_settings['thumbnail_count'] > 0) ? (int)$tp_settings['thumbnail_count'] : 5;
		
		$recentImages = $wpdb->get_results($wpdb->prepare("SELECT * FROM $table_name WHERE id != %d ORDER BY id DESC LIMIT %d",$mainImage->id,$count));
		
		$post_string = "?";
		if($tp_settings['page_id'] != 0) {
			$post_string = "?page_id=" . $tp_settings['page_id'] . "&";
		}

		/* Get Twitter info */
		if($mainImage->shortURL) {
			$message = "";
			$twitterUsername  = "";
			$twitterLink     = "";
			$twitterImageURL = "";
			if(!$mainImage->message || !$mainImage->twitterUsername) {
				$atom = file_get_contents('http://search.twitter.com/search.atom?q='. urlencode($mainImage->shortURL));
				if($atom) {
					$message = value_in("title",value_in("entry",$atom));
					$message = str_replace($mainImage->shortURL,"",$message);
					$twitterUsername  = value_in("name",value_in("author",value_in("entry",$atom)));
					$twitterLink = value_in("uri",value_in("author",value_in("entry",$atom)));
					
					$found = preg_match('<link type="image\/png" href="(.*?)'.
		            '" rel="image"\/>', $atom, $matches);
		            
		            $twitterImageURL = $matches[1];
		            
		            $wpdb->query($wpdb->prepare("UPDATE $table_name SET message = '%s', twitterUsername = '%s', twitterLink = '%s',twitterImageURL = '%s'
						WHERE id = %d",$message,$twitterUsername,$twitterLink,$twitterImageURL,$mainImage->id));
				
					$mainImage = $wpdb->get_row($wpdb->prepare("SELECT * FROM $table_name WHERE id = %d",$mainImage->id));
				    
				}
			}
		}


		$return = '<div id="tweetpress">';
		
			// Display the upload form 
			if(false && is_user_logged_in() && $tp_settings['twitter_username'] && $tp_settings['twitter_password']) {
				$return .= '<center><div id="tp-upload-form">';
					$return .= '<h3>Post a photo to Twitter</h3>';
					$return .= '<form method="post" action="'.get_bloginfo ( 'url' ).'" enctype="multipart/form-data">';
						$return .= '<input type="hidden" name="blog_upload" value="true">';
						$return .= '<p>
										<table cellspacing="5">
											<tr>
												<td width="75" align="right"><label for="message">Message:</label></td><td align="left"><input type="text" name="message" id="message"></td>
	  										</tr>
	  										<tr>
	  											<td width="75" align="right"><label for="file">Photo:</label></td><td align="left"><input id="tp-file-field" type="file" name="media" id="file"> </td>
	  										</tr>
	  										<tr>
	  											<td>&nbsp;</td>
	  											<td align="left"><button>Post</button></td>      											
	  										</tr>
	  									</table>
									<p>';
					$return .= '</form>';
				$return .= '</div></center>';
			}
		
			$return .= '<div id="tp-main">';
				$return .= '<img class="tp-main-image" src="'.get_option ( 'siteurl' ).GALLERYPATH.'/'.$mainImage->name.'">';
				$return .= '<div id="tp-main-twitter-info">';
					if($mainImage->twitterImageURL)
						$return .= '<img src="'.$mainImage->twitterImageURL.'">';
					if($mainImage->twitterUsername)
						$return .= '<span id="tp-author"><a href="'.$mainImage->twitterLink.'">'.$mainImage->twitterUsername.'</a></span><br />';			
	 				if($mainImage->message)
	 					$return .= '<span class="tp-message">'. stripslashes($mainImage->message) . '</span><br />';
	 				$return .= '<span id="tp-time">'.time_twit($mainImage->time).'</span>';			
				$return .= '</div>';			
			
			$return .= '</div>';
			$return .= '<div id="tp-recent-images">';
				$return .= '<ul id="tp-recent-images-list">';	
				foreach($recentImages as $image) {
					$return .= '<li><a href="'.get_option ( 'siteurl' ). $post_string .'image_id='.$image->id.'"><img class="tp-recent-image" src="'.get_option('siteurl').GALLERYPATH.'/thumbs/'.$image->name.'" width="'.THUMBSIZE.'" height="'.THUMBSIZE.'"></a>';
					if(is_user_logged_in()) 
						$return .= '<center><a href="'.get_option ( 'siteurl' ). $post_string .'delete_image_id='.$image->id.'">Delete</a></center>';
					$return .= '</li>';
				}
				$return .= '</ul>';
			$return .= '</div>';
			$return .= '<div class="clear">&nbsp;</div>';
		$return .= '</div>';

		return $return;
	}

?>