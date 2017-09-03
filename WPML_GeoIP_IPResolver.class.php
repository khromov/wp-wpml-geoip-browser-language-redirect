<?php
/**
 * Class for handling the GeoIP conversion
 */

class WPML_GeoIP_IPResolver
{
	var $db;
	var $language_mappings;
	var $default_language;

	/**
	 * Load dependencies, etc
	 */
	function __construct()
	{
		/**
		 * MaxMind Country codes
		 *
		 * AP, EU, AD, AE, AF, AG, AI, AL, AM, CW, AO, AQ, AR, AS, AT, AU, AW, AZ, BA, BB, BD, BE, BF, BG,
		 * BH, BI, BJ, BM, BN, BO, BR, BS, BT, BV, BW, BY, BZ, CA, CC, CD, CF, CG, CH, CI, CK, CL, CM, CN,
		 * CO, CR, CU, CV, CX, CY, CZ, DE, DJ, DK, DM, DO, DZ, EC, EE, EG, EH, ER, ES, ET, FI, FJ, FK, FM,
		 * FO, FR, SX, GA, GB, GD, GE, GF, GH, GI, GL, GM, GN, GP, GQ, GR, GS, GT, GU, GW, GY, HK, HM, HN,
		 * HR, HT, HU, ID, IE, IL, IN, IO, IQ, IR, IS, IT, JM, JO, JP, KE, KG, KH, KI, KM, KN, KP, KR, KW,
		 * KY, KZ, LA, LB, LC, LI, LK, LR, LS, LT, LU, LV, LY, MA, MC, MD, MG, MH, MK, ML, MM, MN, MO, MP,
		 * MQ, MR, MS, MT, MU, MV, MW, MX, MY, MZ, NA, NC, NE, NF, NG, NI, NL, NO, NP, NR, NU, NZ, OM, PA,
		 * PE, PF, PG, PH, PK, PL, PM, PN, PR, PS, PT, PW, PY, QA, RE, RO, RU, RW, SA, SB, SC, SD, SE, SG,
		 * SH, SI, SJ, SK, SL, SM, SN, SO, SR, ST, SV, SY, SZ, TC, TD, TF, TG, TH, TJ, TK, TM, TN, TO, TL,
		 * TR, TT, TV, TW, TZ, UA, UG, UM, US, UY, UZ, VA, VC, VE, VG, VI, VN, VU, WF, WS, YE, YT, RS, ZA,
		 * ZM, ME, ZW, A1, A2, O1, AX, GG, IM, JE, BL, MF, BQ, SS, O1
		 */

		//Array with structure MaxMind Code => WPML Code
		/*
		$this->language_mappings = array(
			'SE' => 'sv', //Sweden
			'NO' => 'nb', //Norway
			'FI' => 'fi', //Finland
			'DK' => 'da', //Denmark
			'US' => 'en', //USA
			'CA' => 'en'  //Canada
		); */
		$devd_arr = get_option( 'wpml_geo_redirect_language_mappings' );
		$country_arr = $lang_codes_arr = $lang_currency_arr = array();
		foreach($devd_arr as $keyy=>$valuee){
			$country_arr[] = $keyy;
			foreach($valuee as $keys=>$values){
				$lang_codes_arr[] = $keys;
				$lang_currency_arr[] = $values;
			}
		}
		$language_mappings_arr = array_combine($country_arr,$lang_codes_arr);

		/* $this->language_mappings = get_option( 'wpml_geo_redirect_language_mappings' ); */
		$this->language_mappings = $language_mappings_arr;

		//Set the default WPML language which is used if no matching language is found
		//$this->default_language = 'sv';
		$default_language_arr = get_option( 'wpml_geo_redirect_default_language' );
		foreach($default_language_arr as $key=>$value){
			$default_langguage = $key;
		}
		$this->default_language = $default_langguage;
		
		//Make sure to not redeclare the GeoIP API if it is loaded already.
		if(!function_exists('geoip_country_code_by_addr')) 
		{
			include_once('lib/geoip-api-php/geoip.inc');
			include_once('lib/geoip-api-php/geoipregionvars.php');
			include_once('lib/geoip-api-php/timezone/timezone.php');
		}
		
		//MaxMind gets cranky when we don't use the full path
		$this->db = geoip_open(plugin_dir_path(__FILE__) . '/database/GeoIP.dat', GEOIP_STANDARD);
	}
	/**
	 * Returns a WPML-compatible country code from an IP address
	 *
	 * @param $ip
	 * @param bool $as_json
	 * @return string
	 */
	function ip_to_wpml_country_code($ip, $as_json = true)
	{
		//This returns empty string if something went wrong
		$country_code = @geoip_country_code_by_addr($this->db, $ip);

		//Try to match against language mappings
		foreach($this->language_mappings as $maxmind_code => $wpml_code)
		{
			if($maxmind_code == $country_code)
				return $this->return_country_code($wpml_code);
		}

		//We didn't match anything, return the default code
		return $this->return_country_code($this->default_language);
	}

	function return_country_code($country_code)
	{
		return json_encode(array('country_code' => $country_code));
	}

	function set_json_header()
	{
		//http://stackoverflow.com/a/11112311/2572827
		header('Content-Type: application/json');
	}
}
