<?php
/*
Plugin Name: WPML GeoIP Browser Language Redirect
Plugin URI: http://khromov.se
Description: Redirects users to their appropriate languages intelligently by utilizing the MaxMind GeoIP database
Version: 1.1
Author: khromov
Author URI: http://khromov.se
License: GPL2
*/

class WPML_GeoIP_Browser_Language_Redirect
{
	/** Initialize and add actions */
	function __construct()
	{
		//Init script
		add_action('wp_print_scripts', array(&$this, 'enqueue_scripts'), 100);

		//Register AJAX endpoint
		add_filter('query_vars', array(&$this, 'register_vars'));
		add_action('wp', array(&$this, 'register_endpoints'));
		//template_redirect is suh-low
		//pre_get_posts is pretty close
		//wp too
	}

	/** Unload old browser redirect and add new one **/
	function enqueue_scripts()
	{
		global $sitepress, $sitepress_settings;

		//De-register old script
		wp_deregister_script('wpml-browser-redirect');

		//Register new one
		wp_enqueue_script('wpml-browser-redirect', plugins_url('js/browser-redirect-geoip.js', __FILE__) , array('jquery', 'jquery.cookie'));

		$args['skip_missing'] = intval($sitepress_settings['automatic_redirect'] == 1);

		//Build multi language urls array
		$languages = $sitepress->get_ls_languages($args);
		$language_urls = array();
		foreach($languages as $language)
			$language_urls[$language['language_code']] = $language['url'];

		//print_r($languages);

		//Cookie parameters
		$http_host = $_SERVER['HTTP_HOST'] == 'localhost' ? '' : $_SERVER['HTTP_HOST'];
		$cookie = array(
			'name' => '_icl_visitor_lang_js',
			'domain' => (defined('COOKIE_DOMAIN') && COOKIE_DOMAIN? COOKIE_DOMAIN : $http_host),
			'path' => (defined('COOKIEPATH') && COOKIEPATH ? COOKIEPATH : '/'),
			'expiration' => $sitepress_settings['remember_language']
		);


		// Send params to javascript
		$params = array(
			'ajax_url' => plugins_url('ajax.php', __FILE__),
			'cookie'            => $cookie,
			'pageLanguage'      => defined('ICL_LANGUAGE_CODE')? ICL_LANGUAGE_CODE : get_bloginfo('language'),
			'languageUrls'      => $language_urls,
		);

		//Let's add the data!
		wp_localize_script('wpml-browser-redirect', 'wpml_browser_redirect_params', $params);
	}

	/**
	 * Register vars
	 *
	 * @param $vars
	 * @return array
	 */
	function register_vars($vars)
	{
		$vars[] = 'wpml_geoip';
		return $vars;
	}

	/**
	 * Checks for our magic var and performs actual work.
	 */
	function register_endpoints()
	{
		if(intval(get_query_var('wpml_geoip')) == 1)
		{
			include('WPML_GeoIP_IPResolver.class.php');
			$ipr = new WPML_GeoIP_IPResolver();

			$ipr->set_json_header();
			if (array_key_exists('HTTP_X_FORWARDED_FOR', $_SERVER)) {
				$tmp_ip_array = explode(',', $_SERVER['HTTP_X_FORWARDED_FOR']);
				$tmp_ip = $tmp_ip_array[0];
			} else {
				$tmp_ip = $_SERVER['REMOTE_ADDR'];
			}
			echo $ipr->ip_to_wpml_country_code($tmp_ip);
			die();
		}
	}
}

$wpml_gblr = new WPML_GeoIP_Browser_Language_Redirect();
