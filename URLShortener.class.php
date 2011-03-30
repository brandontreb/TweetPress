<?php
	/*
	 * URLShortener.class.php
	 * Author: Brandon Trebitowski
	 * URL: http://brandontreb.com
	 * Creation Date: 11/16/09
	 * Version 1.0
	 *
	 * PHP Class that allows you to easily interface with multiple
	 * URL shortening services.
	 *
	 * Example usage:
	 *
	 * Basic:
	 * 	$s = new URLShortener('tr.im');
	 *  echo $s->shorten("http://brandontreb.com");
	 *
	 * Using API Key and Login
	 *	$s = new URLShortener('tr.im');
	 *  $s->login = 'brandontreb';
	 *	$s->APIKey = 'R_2a413ebd15254a72b500ec2ce83f982d';
	 *  echo $s->shorten("http://brandontreb.com");
	 *
	 * Custom URL:
	 *  $s = new URLShortener('custom','http://b1t.me/api/shorten.xml/%s');
	 *  echo $s->shorten("http://brandontreb.com");
	 *
	 */
	
	class URLShortener {
	
		/* Shortener URLS */
		var $url_shorteners = array(
		'j.mp' 			=> 'http://api.j.mp/shorten?format=xml&version=2.0.1&longUrl=%s&login=%s&apiKey=%s',
		'tr.im'			=> 'http://api.tr.im/v1/trim_simple?url=%s',
		'TinyURL'		=> 'http://tinyurl.com/api-create.php?url=%s',
		'is.gd'			=> 'http://is.gd/api.php?longurl=%s',
		'u.nu'			=> 'http://u.nu/unu-api-simple?url=%s',
		'Linkyy'		=> 'http://linkyy.com/create_api?url=%s',
		'custom'		=> ''
		);
		
		var $shortenerName;
		var $shortenerURL;
		var $customShortenerURL;
		var $longURL;
		var $shortURL;
		
		/* Optional API variables */
		var $APIKey;
		var $login;
		
		function __construct($shortener = 'j.mp',$customAPIEndpoint = "") {
			if(array_key_exists($shortener,$this->url_shorteners)) {
				$this->shortenerName = $shortener;
				$this->shortenerURL = $this->url_shorteners[$shortener];
				$this->customShortenerURL = $customAPIEndpoint;
			} else {
				echo "Shortener $shortener not supported.";
			}
		}
		
		function shorten($url,$encode = true) {
		
			if($encode) 
				$this->longURL = urlencode($url);
			else
				$this->longURL = $url;
				
			switch($this->shortenerName) {
				case "j.mp":
					$apiKey = ($this->APIKey != "") ? $this->APIKey : "R_2a413ebd15254a72b500ec2ce83f982d";
					$login  = ($this->login != "") ? $this->login : "brandontreb";
					$surl = $this->prepare_url($this->shortenerURL,$this->longURL,$login,$apiKey);
					$xml = file_get_contents($surl);
					$this->shortURL = $this->value_in('shortUrl',$xml);
					break;
				case "TinyURL":
				case "is.gd":
				case "u.nu":
				case "tr.im":
				case "Linkyy":
					$url = $this->prepare_url($this->shortenerURL,$this->longURL);
					$this->shortURL = file_get_contents($url);
					break;
				case "custom":
					$url = $this->prepare_url($this->customShortenerURL,$this->longURL);
					$this->shortURL = file_get_contents($url);
					break;
				default:
					echo "No valid shortener set!";
					$this->shortURL = $this->longURL;
					break;
			}
			
			return $this->shortURL;
		}
		
		function value_in($element_name, $xml, $content_only = true) {
		    if ($xml == false) {
		        return false;
		    }
		    $found = preg_match('#<'.$element_name.'(?:\s+[^>]+)?>(.*?)'.
		            '</'.$element_name.'>#s', $xml, $matches);
		    if ($found != false) {
		        if ($content_only) {
		            return $matches[1];  
		        } else {
		            return $matches[0];  
		        }
		    }

		    return false;
		}
		
		function prepare_url($url) {
			if ( is_null( $url ) )
				return;
			$args = func_get_args();
			array_shift($args);

			if ( isset($args[0]) && is_array($args[0]) )
				$args = $args[0];
			
			return vsprintf($url, $args);
		}
	}
	
?>