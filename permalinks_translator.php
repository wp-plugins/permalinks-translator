<?php 
/*
Plugin Name: Permalinks Translator
Plugin URI: http://blog.andyhot.gr
Description: This plugins provides translated permalinks for titles.
Author: Andreas Andreou
Version: 0.5.1
Author URI: http://blog.andyhot.gr
*/

define('PERMALINKSTRANSLATOR_VERSION', '0.5.1');

function permalinks_translator_sanitize_title($text){
	if ( !is_admin() ) return $text;

	// only call this from the new article/post page
	$dd = debug_backtrace();
	if (!$dd[5]['file']) return $text;

	/*if (permalinks_translator_endswith($dd[5]['file'], 'post.php')) {
		if ($dd[5]['function']=="require_once") return $text;
		if ($dd[5]['function']!="wp_update_post" && $dd[5]['function']!="wp_insert_post") return $text;
		return permalinks_translator_do_translate($text, TRUE);
		//return $text;
	}*/
	
	if (!(permalinks_translator_endswith($dd[5]['file'], 'admin-ajax.php') || 
		permalinks_translator_endswith($dd[5]['file'], 'edit-form-advanced.php') ||
		(permalinks_translator_endswith($dd[5]['file'], 'post.php') && ($dd[5]['function']=="wp_update_post"||$dd[5]['function']=="wp_insert_post"))
	)) {
		return $text;
	}
	//echo $dd[0]['file'].'<br/>';
	/*echo $dd[1]['file'].'<br/>';
	echo $dd[2]['file'].'<br/>';
	echo $dd[3]['file'].'<br/>';
	echo $dd[4]['file'].'<br/>';
	echo $dd[5]['file'].' (5) <br/>';
echo $dd[6]['file'].'<br/>';
echo $dd[7]['file'].'<br/>';
echo $dd[8]['file'].'<br/>';*/

//print_r($dd);

	//echo $text;
	return permalinks_translator_do_translate($text, TRUE);
	//die();
}

function permalinks_translator_insert_post_data($data){
	if ($data['guid']) return $data;
	if ($data['post_status']!="draft") return $data;

	if ( !empty($data['post_name']) && $data['post_name']) return $data;
	
	//print_r($data);

	$data['post_name'] = sanitize_title($data['post_title'].'-andy');
	$data['post_title'] = sanitize_title($data['post_title']);
	//echo('NAME:'.$data['post_name']);
	//die();
	return $data;
}

function permalinks_translator_admin() {
	if (function_exists('add_options_page')) {
		add_options_page(__('Permalinks Translator Settings','permalinkstranslator'), __('Permalinks Translator','permalinkstranslator'),'publish_posts', 'permalinks_translator_settings','permalinks_translator_settings');
	}
}

function permalinks_translator_settings() {
	if (isset($_POST['submit'])) {
		update_option('permalinks_translator_langsrc', $_POST['langsrc']);
		update_option('permalinks_translator_langdest', $_POST['langdest']);
		echo '<div class="updated fade"><p>Settings updated</p></div>';
	}
	include 'permalinks_translator_settings.php';
}

function permalinks_translator_edit_title_slug($text) {
	if ( !is_admin() ) return $text;

	// only call this from the new article/post page
	$dd = debug_backtrace();	
	$filename = $dd[2]['file'];
	if (!permalinks_translator_endswith($filename, 'post.php')) {
		return $text;
	}
	return permalinks_translator_do_translate($text);
}

function permalinks_translator_do_translate($text, $encode=FALSE) {
	$langsrc = get_option('permalinks_translator_langsrc', 'el');	
	$langdest = get_option('permalinks_translator_langdest', 'en');

	$translated = permalinks_translator_translate(array($encode?urlencode($text):$text), $langsrc, $langdest);
	if (!$translated) return $text;
	return $translated[0];
}

function permalinks_translator_translate($src_texts = array(), $src_lang, $dest_lang){

  //setting language pair
  $lang_pair = $src_lang.'|'.$dest_lang;
  $src_texts_query = "";
  foreach ($src_texts as $src_text){
    $src_texts_query .= "&q=".$src_text;
  }

  $url = "http://ajax.googleapis.com/ajax/services/language/translate?v=1.0"
		.$src_texts_query."&langpair=".urlencode($lang_pair);

  $body = permalinks_translator_http_get($url);
  if (!body) return false;

  // now, process the JSON string
  $json = json_decode($body, true);

  if ($json['responseStatus'] != 200){
    return false;
  }

  $results = $json['responseData'];
  $return_array = array();

  foreach ($results as $result){
      $return_array[] = urldecode($result);
  }
  
  return $return_array;
}

function permalinks_translator_endswith($string, $test) {
    $strlen = strlen($string);
    $testlen = strlen($test);
    if ($testlen > $strlen) return false;
    return substr_compare($string, $test, -$testlen) === 0;
}

function permalinks_translator_curl($url) {
  $ch = curl_init();
  curl_setopt($ch, CURLOPT_URL, $url);
  curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
  curl_setopt($ch, CURLOPT_REFERER, "http://www.YOURWEBSITE.com");
  $body = curl_exec($ch);
  curl_close($ch);
	return $body;
}

function permalinks_translator_http_get($url) {
	global $wp_version;
	
	$this_version = constant('PERMALINKSTRANSLATOR_VERSION');

	$urlParts = parse_url($url);
	$port = $urlParts['port'] || 80; if ($port==1) $port=80;
	$host = $urlParts['host'];
	$path = $urlParts['path'].'?'.$urlParts['query'];

	$http_request  = "GET $path HTTP/1.0\r\n";
	$http_request .= "Host: $host\r\n";
	$http_request .= "User-Agent: WordPress/$wp_version | PermalinksTranslator/$this_version\r\n";
	$http_request .= "\r\n";
	
	$http_host = $host;

	$response = '';
	if( false != ( $fs = @fsockopen($http_host, $port, $errno, $errstr, 10) ) ) {
		fwrite($fs, $http_request);

		while ( !feof($fs) )
			$response .= fgets($fs, 1160); // One TCP-IP packet
		fclose($fs);
		$response = explode("\r\n\r\n", $response, 2);
	}
	// $response[0] are the response headers
	return $response[1];
}

add_action('admin_menu', 'permalinks_translator_admin');
add_filter('sanitize_title', 'permalinks_translator_sanitize_title', 1);
//add_filter('editable_slug', 'permalinks_translator_edit_title_slug', 1);
//add_filter('wp_insert_post_data', 'permalinks_translator_insert_post_data');
//get_sample_permalink
//wp_unique_post_slug
?>