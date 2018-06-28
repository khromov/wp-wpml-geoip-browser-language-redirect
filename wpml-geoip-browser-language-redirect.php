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

		if ( is_admin() ) {
			add_action( 'admin_menu', array( $this, 'add_admin_page_wpml_geo_redirect' ) );
			add_action( 'admin_menu', array( $this, 'digest_post_data' ) );
		}
	}


	function digest_post_data(){

		if( isset( $_POST['language_mappings'] )  ){

			$location = 'options-general.php?page=wpml_geo_redirect_settings';

			if( !wp_verify_nonce( $_POST['_wpnonce'], 'wpml_geo_redirect_update_action' )){
				$this->redirect_user( $location . '&feedback=form_submission_error' );
			}

			// Put whatever comes as country-language code combo into an array
			$data_for_option = array();

			foreach( $_POST['language_mappings'] as $val ){

				if( $val['country'] != 'country_code_ddown_label' ) {

					if(	array_key_exists ( $val['country']  , $data_for_option ) ){
						$this->redirect_user( $location . '&feedback=duplicate_key' );
					}

					$data_for_option[ $val['country'] ] = $val['language'];
				}
			}


			// Remove any country code marked for removal
			if( isset( $_POST['remove_country_code'] ) ){
				foreach( $_POST['remove_country_code'] as $country_code ){
					unset( $data_for_option[$country_code] );
				}
			}
			
			// Save in DB and redirect
			update_option( 'wpml_geo_redirect_default_language' , trim( $_POST['default_redirect_language'] ) );
			update_option( 'wpml_geo_redirect_language_mappings' , $data_for_option );

			$this->redirect_user( $location . '&feedback=success' );
		}

	}


	function redirect_user( $location ){
		header("Location: $location");
		exit();
	}


	function add_admin_page_wpml_geo_redirect(){

		add_options_page(
			'WPML GEO Redirect',
			'WPML GEO Redirect',
			'manage_options',
			'wpml_geo_redirect_settings',
			array( $this, 'wpml_geo_redirect_admin_page' ) );
	}
	

	function wpml_geo_redirect_admin_page(){

		$this->set_wp_options_with_default_values_if_necessary();

		$language_mappings      = get_option( 'wpml_geo_redirect_language_mappings' );
		$default_language       = get_option( 'wpml_geo_redirect_default_language' );

		include 'WPML_GEOIP_admin_page.class.php';

		$admin_page = new WPML_geoip_admin_page( $language_mappings , $default_language );
		$admin_page->display_wpml_geo_redirect_admin_page();

	}

	private function set_wp_options_with_default_values_if_necessary(){

        if ( null === get_option( 'wpml_geo_redirect_language_mappings' , null ) ) {
            $languages = apply_filters( 'wpml_active_languages', NULL, 'orderby=id&order=desc' );

            if ( !empty( $languages ) ) {
                $arr = array_keys( $languages );
                $default_language_mapping = array( 'SE' => $arr[0] );
                add_option( 'wpml_geo_redirect_language_mappings' , $default_language_mapping );
            }
        }
        if ( null === get_option( 'wpml_geo_redirect_default_language' , null ) ) {
            $default_language = apply_filters('wpml_default_language', NULL);
            add_option('wpml_geo_redirect_default_language', $default_language);
        }

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
		$languages = apply_filters( 'wpml_active_languages', null, $args );
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
			} else if (array_key_exists('HTTP_FORWARDED', $_SERVER)) {
				$tmp_ip = str_replace('for=','', $_SERVER['HTTP_FORWARDED']);
			} else {
				$tmp_ip = $_SERVER['REMOTE_ADDR'];
			}
			echo $ipr->ip_to_wpml_country_code($tmp_ip);
			die();
		}
	}
}

$wpml_gblr = new WPML_GeoIP_Browser_Language_Redirect();
