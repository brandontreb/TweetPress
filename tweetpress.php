<?php
	/*
	Plugin Name: TweetPress
	Plugin URI: http://brandontreb.com/tweetpress
	Description: TweetPress is the Wordpress Plug-In that gives you total control and ownership of the photos you post to Twitter, sending traffic back to your own blog, instead of a third party site.
	Version: 3.2
	Author: Brandon Trebitowski
	Author URI: http://brandontreb.com/tweetpress
	
	
	Copyright 2009  Brandon Trebitowski  (email : brandontreb@gmail.com)
	
	This program is free software; you can redistribute it and/or modify
	it under the terms of the GNU General Public License as published by
	the Free Software Foundation; either version 2 of the License, or
	(at your option) any later version.
	
	This program is distributed in the hope that it will be useful,
	but WITHOUT ANY WARRANTY; without even the implied warranty of
	MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
	GNU General Public License for more details.
	
	You should have received a copy of the GNU General Public License
	along with this program; if not, write to the Free Software
	Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA  02110-1301  USA
	
	Todo: Filter Comments by image 
	 
	*/

	define('THUMBSIZE',100);
	
	define('AUTH_TYPE_ANON',0);
	define('AUTH_TYPE_TWITTER',1);
	define('AUTH_TYPE_WP',2);
	
	if (!defined('UPLOADDIR')) {
		define('UPLOADDIR',ABSPATH . 'wp-content/');
	}
	
	if (!defined('GALLERYPATH')) {
		define('GALLERYPATH','/wp-content/tweetpress');
	}
	
	/*
	 * Global Settings
	 */
	
	$data = array(
		'page_id'				 => 0,
		'allow_anon'	     => 1,
		'thumbs_count'			 => 5,
		'configured'			 => 0,
		'thumbnail_count'		 => 5,
		'twitter_username'		 => '',
		'twitter_password'		 => '',
		'url_shortening_service' => '',
		'shortener_login'   => '',
		'shortener_api_key'  => '',
		'url_shortening_custom_endpoint'  => '',
		'custom_css' => '',
		'log' => '',
		'auth_type' => 2,
		'use_wp_credentials' => 1,
		'use_twitter_credentials' => 0
		);
	
	$url_shorteners = array(
		'j.mp (bit.ly)' => 'http://api.j.mp/shorten?format=xml&version=2.0.1&longUrl=%s&login=%s&apiKey=%s',
		'TinyURL'		=> 'http://tinyurl.com/api-create.php?url=%s',
		'is.gd'			=> 'http://is.gd/api.php?longurl=%s',
		'u.nu'			=> 'http://u.nu/unu-api-simple?url=%s',
		'Linkyy'		=> 'http://linkyy.com/create_api?url=%s',
		'Custom'		=> ''
	);
	
	
	$sources_to_urls = array(
		'tweetie' => 'http://www.atebits.com/tweetie-iphone/',
		'twittelator' => 'http://www.stone.com/Twittelator/'
	);
	
	add_option('tp_settings',$data,'TweetPress Replacement Options');
	
	$tp_settings = get_option('tp_settings');

	include_once('tweetpress-widget.php');

	function tp_css() {
		global $tp_settings;
		$stylesheet_url = get_option ( 'siteurl' ) . '/wp-content/plugins/tweetpress/tweetpress.css';
		echo '<link rel="stylesheet" href="' . $stylesheet_url . '" type="text/css" />';
		if($tp_settings['custom_css']) {
			echo '<style type="text/css">'. $tp_settings['custom_css'] . '</style>';
		}
	}
	add_action( 'wp_head', 'tp_css' );
	
	/*
	 * Admin menu
	 */
	add_action('admin_menu', 'tp_menu');
	
	function tp_menu() {
	  add_options_page('TweetPress Options', 'TweetPress', 8, __FILE__, 'tp_options');
	}
	
	function tp_options() {
		global $_POST,$tp_settings, $url_shorteners,$wpdb;
		
		if($tp_settings['configured'] == 0) {
			$tp_settings['configured'] = 1;
			update_option('tp_settings',$tp_settings);
		}
		
		if(isset($_POST['save_settings'])) {
			$tp_settings['page_id'] = $_POST['page_id'];
			$tp_settings['allow_anon'] = (isset($_POST['allow_anon'])) ? 1 : 0;
			$tp_settings['twitter_username'] = $_POST['twitter_username'];
			$tp_settings['thumbnail_count'] = $_POST['thumbnail_count'];
			$tp_settings['twitter_password'] = $_POST['twitter_password'];
			$tp_settings['shortener_login'] = $_POST['shortener_login'];
			$tp_settings['shortener_api_key'] = $_POST['shortener_api_key'];
			$tp_settings['url_shortening_service'] = $_POST['url_shortening_service'];
			$tp_settings['custom_css'] = $_POST['custom_css'];
			$tp_settings['auth_type'] = $_POST['auth_type'];
			
			if(isset($_POST['url_shortening_custom_endpoint'])) {
				$tp_settings['url_shortening_custom_endpoint'] = $_POST['url_shortening_custom_endpoint'];
			}
			
			update_option('tp_settings',$tp_settings);
		}
		
		if(is_writable(UPLOADDIR)) {
			if(!is_dir(UPLOADDIR . 'tweetpress')) {
				$success = mkdir(UPLOADDIR . 'tweetpress');
			}
			
			if(!is_dir(UPLOADDIR . 'tweetpress/thumbs')) {
				mkdir(UPLOADDIR . 'tweetpress/thumbs');
			}
		}
		$pages = get_pages(); 

		echo '<div class="wrap" id="tp-options">';
	  	echo '<h2>TweetPress Options</h2>';
	  	echo '<form action="" method="post">
	  			<table cellpadding="10" cellspacing="10">
	  				<tr>
	  					
  						<td align="right" width="200">
			  				<label for="page_id">Gallery Page:</label>
			  			</td>
			  			<td>
	  						<select name="page_id">
	  							<option value="0">Homepage</option>';
	  							foreach($pages as $page) {
	  								echo '<option value="'.$page->ID.'"';
	  								if($page->ID == $tp_settings['page_id']) {
	  									echo ' selected="selected" ';
	  								}
	  								echo '>'.$page->post_title.'</option>';
	  							}	
	  					
	  	echo '				</select>
	  					</td>
	  				</tr>
	  				<tr>
	  					
  						<td align="right" width="200">
			  				<label for="thumb_count">Thumbnail count:</label>
			  			</td>
			  			<td>
			  				<input type="text" name="thumbnail_count" value="'.$tp_settings['thumbnail_count'].'">
			  			</td>
			  		</tr>
	  				<tr>
  						<td align="right" width="200">
			  				<label for="thumb_count">Authentication:</label>
			  			</td>
  						<!-- <td><input type="checkbox" name="allow_anon" '.($tp_settings['allow_anon'] ? 'checked="checked"' : "").' ></td> -->
  						<td>
  						<select name="auth_type" onchange="
				if(this.value == \'1\') {
					document.getElementById(\'twitter-settings\').style.display=\'block\';
				} else {
					document.getElementById(\'twitter-settings\').style.display=\'none\';
				}
			">
  							<option '.($tp_settings['auth_type'] == AUTH_TYPE_ANON ? 'SELECTED' : '').' value="0">None</option>
  							<option '.($tp_settings['auth_type'] == AUTH_TYPE_TWITTER ? 'SELECTED' : '').' value="1">Twitter Login</option>
  							<option '.($tp_settings['auth_type'] == AUTH_TYPE_WP ? 'SELECTED' : '').' value="2">Wordpress Login</option>
  						</select>
  						</td>
  					</tr>
	  			</table>
	  			<span id="twitter-settings" style="'.($tp_settings['auth_type'] != AUTH_TYPE_TWITTER ? "display:none;" : "").'">
	  			<h2>Twitter Settings</h2>
	  			<p>You only need to fill out your Twitter login info if you want to post images to Twitter from your gallery page.</p>
	  			<table cellpadding="10" cellspacing="10">
	  				<tr>
	  					
  						<td align="right" width="200">
			  				<label for="twitter_username">Twitter username:</label>
			  			</td>
			  			<td>
			  				<input type="text" name="twitter_username" value="'.$tp_settings['twitter_username'].'">
			  			</td>
			  		</tr>
			  		<tr>
	  					
  						<td align="right" width="200">
			  				<label for="twitter_password">Twitter password:</label>
			  			</td>
			  			<td>
			  				<input type="password" name="twitter_password" value="'.$tp_settings['twitter_password'].'">
			  			</td>
			  		</tr>
			  	</table>
			  	</span>';
	  		echo '
	  			<h2>URL Shortening</h2>
	  			<p>Select the URL shortening service you would like to use.</p>
	  			<table cellpadding="10" cellspacing="10">
	  				<tr>
	  					
  						<td align="right" width="200">
			  				<label for="url_shortening_custom_endpoint">URL Shortening Service:</label>
			  			</td>
			  			<td>
			  				<script>
			  					function check_custom(value) {
			  						if(value == "Custom") {
			  							document.getElementById(\'tr_custom_url\').style.display = "";
			  						} else {
			  							document.getElementById(\'tr_custom_url\').style.display = "none";
			  						}
			  					}
			  				</script>
			  				<select name="url_shortening_service" onChange="check_custom(this.value);">';
	  							foreach($url_shorteners as $key => $value) {
	  								echo '<option value="'.$key.'"';
	  								if($key == $tp_settings['url_shortening_service']) {
	  									echo ' selected="selected" ';
	  								}
	  								echo '>'.$key.'</option>';
	  							}	
	  					
	  	echo '				</select>
			  			</td>
			  		</tr>
			  		<tr id="tr_custom_url" style="display:'.($tp_settings['url_shortening_service'] == "Custom" ? "" : "none").';">
			  			<td align="right" width="200">
			  				<label for="url_shortening_custom_endpoint">Custom Shortener URL:</label>
			  			</td>
			  			<td>
			  				<small>Put %s where you want the long url to go. ( ex: http://b1t.me/api/shorten.xml/%s )</small><br />
			  				<input type="text" name="url_shortening_custom_endpoint" value="'.$tp_settings['url_shortening_custom_endpoint'].'">
			  			</td>
			  		</tr>
			  		<tr>
	  					
  						<td align="right" width="200">
			  				<label for="shortener_login">Shortener Login:</label><br>
			  				<small>(optional)</small>
			  			</td>
			  			<td>
			  				<input type="text" name="shortener_login" value="'.$tp_settings['shortener_login'].'">
			  			</td>
			  		</tr>
			  		<tr>
	  					
  						<td align="right" width="200">
			  				<label for="shortener_api_key">Shortener API Key:</label><br>
			  				<small>(optional)</small>
			  			</td>
			  			<td>
			  				<input type="text" name="shortener_api_key" value="'.$tp_settings['shortener_api_key'].'">
			  			</td>
			  		</tr>
			  	</table>';
			  	
		echo '
	  			<h2>Custom CSS</h2>
	  			<p>Add custom styling to your gallery page</p>
	  			<table cellpadding="10" cellspacing="10">
	  				<tr>
			  			<td colspan="2">
			  				<textarea rows="12" cols="60" name="custom_css" id="custom_css">'.$tp_settings['custom_css'].'</textarea>
			  			</td>
			  		</tr>
			  	</table>';  
		echo '<input type="submit" name="save_settings" value="Save Settings" class="button-primary" >
	  		</form>';
	  		
			echo '<p>Follow <a href="http://twitter.com/brandontreb">@brandontreb</a> on Twitter</p> ';
			echo '<p>Like TweetPress? Help out by <a href="https://www.paypal.com/cgi-bin/webscr?cmd=_s-xclick&hosted_button_id=9008484">donating</a>.</p>';
			
			
		echo '<!-- <a href="#" onClick="document.getElementById(\'log-text\').style.display = \'\';return false;">Log</a>';
		echo '<textarea style="display:none;" rows="12" cols="60" name="log-text" id="log-text">'.$tp_settings['log'].'</textarea> -->';
	}

	/*
	 * Displaying the Gallery
	 */
	 
	add_action('get_header', 'check_gallery_path',0);

	function check_gallery_path() {
		global $tp_settings,$wp_query;
		
		if($tp_settings['page_id'] != 0 && isset($_GET['image_id'])) {
			if(!isset($_GET['page_id'])) {
				if($wp_query->post->ID != $tp_settings['page_id']) {
					$gallery_url = get_option ( 'siteurl' ) . '/?' . $_SERVER['QUERY_STRING'] . '&page_id=' . $tp_settings['page_id'];
					header("Location: $gallery_url");
					exit;
				}
			}	
		}
	}

	function load_gallery_on_page() {
		global $db,$wp_query,$tp_settings;
		if($tp_settings['page_id'] != 0) {
			if($wp_query->post->ID == $tp_settings['page_id']	) {
				add_filter('the_content','load_gallery');
			}
		} else if(is_home() || is_front_page()) {
			add_action('loop_start','load_gallery_home');
		}
	}
	
	function load_gallery($content) {
		include_once('gallery.php');
		return tp_gallery() . $content;
	}
	
	function load_gallery_home() {
		include_once('gallery.php');
		echo tp_gallery();
	}

	add_action('get_header','load_gallery_on_page');

	/*
	 * API
	 */
	 
	add_action('plugins_loaded', 'tp_upload',-1);
	add_action('plugins_loaded', 'tp_check_show_image',-2);
	
	function tp_check_show_image() {
		global $wpdb;
		$table_name = $wpdb->prefix . "tweetpress";
		if(isset($_REQUEST['show_image']) && isset($_REQUEST['image_id'])) {
			$image = $wpdb->get_row($wpdb->prepare("SELECT * FROM $table_name WHERE id = %d",$_REQUEST['image_id']));
			header("Location: " . get_option ( 'siteurl' ).GALLERYPATH.'/'.$image->name);
			exit(0);
		}
	}
	
	function tp_upload() {
		global $wpdb,$tp_settings,$_POST,$_GET,$_SERVER,$_FILES, $url_shorteners;
		
		$source = $_REQUEST['source'];
		
		// for ping requests
		if(isset($_GET['tp_ping'])) {
		
			if(isset($_REQUEST['username']) && isset($_REQUEST['password'])) {
				if(validate_credentials($_REQUEST['username'],$_REQUEST['password'])) {			
					echo '<?xml version="1.0" encoding="UTF-8"?>
					<rsp stat="ok">
					    <message>TweetPress plugin successfully installed</message>
					</rsp>';
				} else {
					returnError(1,"Invalid username or password");
				}
			} else {
				echo '<?xml version="1.0" encoding="UTF-8"?>
					<rsp stat="ok">
					    <message>TweetPress successfully installed</message>
					</rsp>';
			}
			exit(0);
		}
		
		if(isset($_FILES['media']))  {
			tp_log("/**** New TP Upload Session ".date("Y-m-d H:i:s")." ****/");
			tp_log('Starting upload of file '.$_FILES['media']['name'].' from '.$source.'...');
			/* User validation */
			if(!$tp_settings['allow_anon'] && !is_user_logged_in()) {
				if($source == "tweetie") {
					// If tweetie, we vaidate on twitter credentials
					if(isset($_REQUEST['username']) && isset($_REQUEST['password'])) {
						if(!validate_credentials($_REQUEST['username'],$_REQUEST['password'],true)) {
							tp_log("ERROR: Invalid username or password");
							returnError(1,"Invalid username or password",false);
							exit;
						}
					} else {
						tp_log("ERROR: Username or password not specified: " . serialize($_REQUEST));
						returnError(1,"Username or password not specified",false);
						exit;
					}
				} else {
				
					if(isset($_REQUEST['username']) && isset($_REQUEST['password'])) {
						if(!validate_credentials($_REQUEST['username'],$_REQUEST['password'])) {
							tp_log("ERROR: Invalid username or password");
							returnError(1,"Invalid username or password");
							exit;
						}
					} else {
						tp_log("ERROR: Username or password not specified");
						returnError(1,"Username or password not specified" . serialize($_REQUEST));
						exit;
					}
				
				}
			} else {
				tp_log("Posting as an anonymous user");
			}
			
			if(!isset($_FILES) || !array_key_exists('tmp_name', $_FILES['media'])) {
				tp_log("ERROR: No Image Provided.");
				returnError(2,"No image provided",($source != "tweetie"));
				exit;
			} 
			
			// Localize the variables
			$fileName = $_FILES['media']['name'];
			$tmpName  = $_FILES['media']['tmp_name'];
			$fileSize = $_FILES['media']['size'];
			
			// Get the file type
			$typeArray = explode(".",$fileName);
			$fileType  = "image/" . $typeArray[1];
			
			$ok = false;
			if(stristr($fileType,"png") || stristr($fileType,"jpg") || stristr($fileType,"jpeg") || stristr($fileType,"tiff") || stristr($fileType,"gif")) {
				$ok = true;
			}
			
			if($ok == false) {
				tp_log("ERROR: Invalid Image Type.");
				returnError(3,"Invalid image type",($source != "tweetie"));
				exit;
			}
			
			// Resolve the path
			$uploadDir  = UPLOADDIR . '/tweetpress/';
			$fileName   = basename($fileName);
			$uploadFile = $uploadDir . $fileName;
		
			// Give a unique name if file exists
			if(file_exists($uploadFile)) {
				$fileName   = date('YmdHis') . $fileName;
				$uploadFile = $uploadDir . $fileName;
			}
			
			// Update the database
			$table_name = $wpdb->prefix . "tweetpress";
			$wpdb->query($wpdb->prepare("INSERT INTO $table_name (type,name,size,message,latitude,longitude) VALUES('%s','%s','%d','%s','%s','%s')",
				$fileType,$fileName,$fileSize,$_POST['message'],$_POST['latitude'],$_POST['longitude']));
			
			// Save file to disk
			if (move_uploaded_file($_FILES['media']['tmp_name'], $uploadFile)) {
				tp_log("Wrote file to $uploadFile");
				
				if(file_exists(ABSPATH. 'wp-admin/includes/image.php')) {
					if(!function_exists("wp_crop_image")) {
						include(ABSPATH. 'wp-admin/includes/image.php');
					}
				}
			
				// thumbnail
				if(function_exists("wp_crop_image")) {					
					$created = wp_crop_image( UPLOADDIR . 'tweetpress/' . $fileName, 0,0, 500, 500, THUMBSIZE, THUMBSIZE,false, UPLOADDIR . 'tweetpress/thumbs/' . $fileName );
					
					tp_log("Thumb, creating a copy:" . addslashes($created));
					
					if(!file_exists(UPLOADDIR . 'tweetpress/thumbs/' . $fileName)) {
						copy($uploadFile,UPLOADDIR . 'tweetpress/thumbs/' . $fileName);
					}
					
				} else {
					tp_log("No thumbnail, creating a copy");
					copy($uploadFile,UPLOADDIR . 'tweetpress/thumbs/' . $fileName);
				}
			
				$long_url = get_option ( 'siteurl' ) . '?page_id=' . $tp_settings['page_id'] . '&image_id=' . $wpdb->insert_id;
				$short_url = null;
				$shortener_url = $url_shorteners[$tp_settings['url_shortening_service']];
				
				switch($tp_settings['url_shortening_service']) {
					case "j.mp (bit.ly)":
						$apiKey = ($tp_settings['shortener_api_key'] != "") ? $tp_settings['shortener_api_key'] : "R_2a413ebd15254a72b500ec2ce83f982d";
						$login  = ($tp_settings['shortener_login'] != "") ? $tp_settings['shortener_login'] : "brandontreb";
						$url = prepare_url($shortener_url,urlencode($long_url),$login,$apiKey);
						$xml = file_get_contents($url);
						$short_url = value_in('shortUrl',$xml);
						break;
					
					case "TinyURL":
					case "is.gd":
					case "u.nu":
					case "Linkyy":
						$url = prepare_url($shortener_url,urlencode($long_url));
						$short_url = file_get_contents($url);
						break;
					case "Custom":
						$url = prepare_url($tp_settings['url_shortening_custom_endpoint'],urlencode($long_url));
						$short_url = file_get_contents($url);
						break;
				}
							
				$imageurl = get_option ( 'siteurl' ) . GALLERYPATH . '/' . $fileName;
				$thumburl = get_option ( 'siteurl' ) . GALLERYPATH . '/thumbs/' . $fileName;
				
				$wpdb->query($wpdb->prepare("UPDATE $table_name SET shortURL = '%s' WHERE id = %d",$short_url,$wpdb->insert_id));
				
				if(isset($_REQUEST['blog_upload']) && $_REQUEST['blog_upload'] == 'true') {
					tp_log("Uploaded to blog...");
					require('Twitter.class.php');
					$twitter = new Twitter();
					$twitter->username = $tp_settings['twitter_username'];
					$twitter->password = $tp_settings['twitter_password'];
					
					$twitter->update('xml',$_POST['message'] . ' ' . $short_url);
					
					header("Location: " . $short_url);
				} else {
					tp_log("Success!");
					if($source != "tweetie") {
						echo '<mediaurl>'.$short_url.'</mediaurl>';
						echo '<?xml version="1.0" encoding="UTF-8"?>
						<rsp stat="ok">
						 <mediaid>'.$wpdb->insert_id.'</mediaid>
						 <mediaurl>'.$short_url.'</mediaurl>
						 <imageurl>'.$imageurl.'</imageurl>
						 <thumburl>'.$thumburl.'</thumburl>
						</rsp>';
					} else {
						echo '<mediaurl>'.$short_url.'</mediaurl>';
					}
				}
				
			} else {
				tp_log("ERROR: Unable to write image to disk");
				returnError(4,"Unable to write image to disk",($source != "tweetie"));
			}
			
			exit;
		}
		
	}
	
	function prepare_url($url) {
		if ( is_null( $url ) )
			return;
		$args = func_get_args();
		array_shift($args);
		// If args were passed as an array (as in vsprintf), move them up
		if ( isset($args[0]) && is_array($args[0]) )
			$args = $args[0];
		
		return vsprintf($url, $args);
	}
	
	function value_in($element_name, $xml, $content_only = true) {
	    if ($xml == false) {
	        return false;
	    }
	    $found = preg_match('#<'.$element_name.'(?:\s+[^>]+)?>(.*?)'.
	            '</'.$element_name.'>#s', $xml, $matches);
	    if ($found != false) {
	        if ($content_only) {
	            return $matches[1];  //ignore the enclosing tags
	        } else {
	            return $matches[0];  //return the full pattern match
	        }
	    }
	    // No match found: return false.
	    return false;
	    
	}
	
	// Script from http://blog.blazed-designs.com/2009/04/01/make-your-date-stamps-like-twitter/
	function time_twit($time){
		$time = strtotime($time);
		//The Output the time is used at
		$format = "F m, Y g:i a";
		 
		//Time presets for the lazy (Time goes by seconds)
		$timeyear = 365 * 24 * 60 * 60;
		$timemonth = 30 * 7 * 24 * 60 * 60;
		$timeweek = 7 * 24 * 60 * 60;
		$timeday = 24 * 60 * 60;
		$timehour = 60 * 60;
		$timemins = 60;
		$timeseconds = 1;
		 
		//today's date
		$today = time();
		 
		//Get the time from today by minusing the time looked at by today's date
		$x = $today - $time;
		 
		//These define the out put
		if($x >= $timeyear){$x = date($format, $x); $dformat=""; $pre ="On the date: "; 
		}elseif($x >= $timemonth){$x = date($format, $x); $dformat=""; $pre ="On the date: ";
		}elseif($x >= $timeday){$x = round($x / $timeday); $dformat="days ago"; $pre ="About"; $x = round($x);
		}elseif($x >= $timehour){$x = round($x / $timehour); $dformat="hours ago"; $pre ="About"; 
		}elseif($x >= $timemins){$x = round($x / $timemins); $dformat="minutes ago"; $pre ="About";
		}elseif($x >= $timeseconds){$x = round($x / $timeseconds); $dformat="seconds ago"; $pre ="About"; 
		}
		return $pre." ".$x." ".$dformat;
	}
	
	function validate_credentials($username, $password, $twitter = false) {
		global $tp_settings;
		
		if($tp_settings['auth_type'] == AUTH_TYPE_ANON) return true;
		if($tp_settings['auth_type'] == AUTH_TYPE_WP) return user_pass_ok($username,$password);
		
		tp_log('Verifying credentials: ('.$username.' = '.$tp_settings['twitter_username'].
			') AND ('.tp_mask($password).' = '.tp_mask($tp_settings['twitter_password']).') ... '. 
			 (($username == $tp_settings['twitter_username']) && ($password == $tp_settings['twitter_password'])));
		return (($username == $tp_settings['twitter_username']) && ($password == $tp_settings['twitter_password']));
	}
	
	function returnError($code,$message,$xml = true) {
		if($xml) {
			echo str_replace("\t",'','<?xml version="1.0" encoding="UTF-8"?>
				<rsp stat="fail">
				<err code="'.$code.'" msg="'.$message.'" />
				</rsp>');
		} else {
			echo $message;
		}
	}
	
	/*
	 * Install 
	 */
	 
	 function tp_activate () {
	 	global $wpdb,$tp_settings;
		
		$table_name = $wpdb->prefix . "tweetpress";
		
		if($wpdb->get_var("SHOW TABLES LIKE '$table_name'") != $table_name) {
			echo "creating";
			$sql = "CREATE TABLE `".$table_name."` (
				`id` INT NOT NULL AUTO_INCREMENT PRIMARY KEY ,
				`type` VARCHAR( 25 ) NOT NULL ,
				`name` VARCHAR( 50 ) NOT NULL ,
				`size` VARCHAR( 25 ) NOT NULL ,
				`message` VARCHAR( 150 ) NULL ,
				`shortURL` VARCHAR( 150 ) NULL ,
				`twitterUsername` VARCHAR( 150 ) NULL ,
				`twitterLink` VARCHAR( 150 ) NULL ,
				`twitterImageURL` VARCHAR( 150 ) NULL ,
				`latitude` VARCHAR( 25 ) NOT NULL ,
				`longitude` VARCHAR( 25 ) NOT NULL ,
				`time` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
				)";
		
			require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
			dbDelta($sql);
		} else {
			$wpdb->query("ALTER TABLE `".$table_name."` ADD COLUMN `shortURL` VARCHAR( 150 ) NULL");
			$wpdb->query("ALTER TABLE `".$table_name."` ADD COLUMN `twitterUsername` VARCHAR( 150 ) NULL");
			$wpdb->query("ALTER TABLE `".$table_name."` ADD COLUMN `twitterLink` VARCHAR( 150 ) NULL");
			$wpdb->query("ALTER TABLE `".$table_name."` ADD COLUMN `twitterImageURL` VARCHAR( 150 ) NULL");
		}
		if(is_writable(UPLOADDIR)) {
			if(!is_dir(UPLOADDIR . 'tweetpress')) {
				$success = mkdir(UPLOADDIR . 'tweetpress');
			}
			
			if(!is_dir(UPLOADDIR . 'tweetpress/thumbs')) {
				mkdir(UPLOADDIR . 'tweetpress/thumbs');
			}
		}
		
		/** Migration Remove in later versions **/
		if($tp_settings['allow_anon']) {
			$tp_settings['auth_type'] = AUTH_TYPE_ANON;
			$tp_settings['allow_anon'] = 0;
			update_option('tp_settings',$tp_settings);
		}
		
		if(!$tp_settings['auth_type']) {
			if($tp_settings['twitter_username'] &&
			$tp_settings['twitter_password']) {
				$tp_settings['auth_type'] = AUTH_TYPE_TWITTER;
			} else {
				$tp_settings['auth_type'] = AUTH_TYPE_WP;
			}
			update_option('tp_settings',$tp_settings);
		}
	}
	register_activation_hook( __FILE__, 'tp_activate' );
	
	
	function tp_admin_notice() {
		global $tp_settings;
		if($tp_settings['configured'] != 1) {
			echo '<div class="error"><p><strong>' . sprintf( __('TweetPress is not configured. Please go to the <a href="%s">plugin admin page</a> to configure it. ' ), admin_url( 'options-general.php?page=tweetpress/tweetpress.php' ) ) . '</strong></p></div>';
		}

		if(!file_exists(UPLOADDIR . "tweetpress")) {
			echo '<div class="error"><p><strong>The folder ' . UPLOADDIR . 'tweetpress/ could not be created.  You must create it manually (and make it writable) or set your wp-content permissions to 777 and reload this page (you can set it back to 755 when you are done).' . '</strong></p></div>';
		} else {
			if(!is_writable(UPLOADDIR . "tweetpress")) {
				echo '<div class="error"><p><strong>' . UPLOADDIR . 'tweetpress/ is not writable! This directory must be writable for TweetPress to work. Contact an administrator if you are unsure about how to do this.' . '</strong></p></div>';
			}
		}
		
	}
	add_action( 'admin_notices', 'tp_admin_notice' );
	
	function tp_log($text) {
		return;
		global $tp_settings;
		$tp_settings['log'] = $tp_settings['log'] . "\n" . $text;
		update_option('tp_settings',$tp_settings);
	}
	
	function tp_mask($text, $mask = "*") {
		$return = "";
		for($x = 0; $x < strlen($text); $x++) {
			$return .= $mask;
		}
		return $return;
	}
	
?>