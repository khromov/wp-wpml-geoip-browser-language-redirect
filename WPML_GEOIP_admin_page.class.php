<?php
if( ! defined( 'ABSPATH' ) ) exit;

class WPML_geoip_admin_page
{
	var $language_mappings;
	var $default_language;


	function __construct( $language_mappings , $default_language ){
		$this->language_mappings = $language_mappings;
		$this->default_language  = $default_language;
	}
	

	function display_wpml_geo_redirect_admin_page(){
		?>

		<div class="wrap">
			<div id="icon-plugins" class="icon32"></div>
			<h2>WPML GEO Redirect</h2>

			<?php $this->display_feedback_admin_notices() ?>

			<form method="post" action="options-general.php?page=wpml_geo_redirect_settings" id="options_form" >
				<?php
				/* $devd_arr = get_option( 'wpml_geo_redirect_language_mappings' );
				echo "<pre>";print_r($devd_arr);echo "</pre>";
				$country_arr = $lang_codes_arr = $lang_currency_arr = array();
				foreach($devd_arr as $keyy=>$valuee){
					$country_arr[] = $keyy;
					foreach($valuee as $keys=>$values){
						$lang_codes_arr[] = $keys;
						$lang_currency_arr[] = $values;
					}
				}
				$language_mappings_arr = array_combine($country_arr,$lang_codes_arr);
				echo "<pre>";print_r($language_mappings_arr);echo "</pre>";
				echo "<pre>";print_r($country_arr);echo "</pre>";
				echo "<pre>";print_r($lang_codes_arr);echo "</pre>";
				echo "<pre>";print_r($lang_currency_arr);echo "</pre>"; */
				
				echo '<table class="form-table" >';
				echo "<tr>
						<td>Country Code / Language Code</td>
							<td width='83%'>Remove</td>
						</tr>";
				$table_row = $this->display_language_mapping_rows( $this->language_mappings );
	
				$this->display_add_new_country_mapping_row( $table_row );
				$this->display_select_default_language_row( $table_row , $this->default_language );
				echo "</table>";
	
				wp_nonce_field( 'wpml_geo_redirect_update_action' );
				submit_button();
				?>
			</form>
		</div>

		<?php
	}
	

	// Helpers ///////////////////////////////////////////////////////////////////////////////////////////////////

	private function display_feedback_admin_notices(){

		if( isset($_GET['feedback'] ) ){

			$feedback = trim( $_GET['feedback'] );
			switch( $feedback ){

				case 'success':
					?>
					<div class="notice notice-success is-dismissible">
						<p>Configuration data was updated.</p>
					</div>
					<?php
					break;

				case 'duplicate_key':
					?>
					<div class="notice notice-error is-dismissible">
						<p>There has been an error. Country codes need to be unique.</p>
					</div>
					<?php
					break;

				case 'form_submission_error':
					?>
					<div class="notice notice-error is-dismissible">
						<p>There has been a form submission error.</p>
					</div>
					<?php
					break;
			}
		}
	}


	private function display_language_mapping_rows( $language_mappings ){

		$table_row = 0;

		foreach( $language_mappings as $country_code => $lang_code){
			foreach($lang_code as $key=>$value){
				$lang_codes = $key;
				$lang_currency = $value;
			}

			echo '<tr valign="top">';
			echo '<td>';
			echo $this->display_icl_language_flag( $country_code );
			echo "&nbsp;";
			echo $this->display_mm_country_code_dropdown( $table_row , $country_code );
			echo "<strong> => </strong>";
			echo $this->display_language_code_dropdown( $table_row , $lang_codes );
			echo '</td>';
			echo '<td>';
			echo $this->display_language_currency_dropdown_devd( $table_row , $lang_currency);
			echo '</td>';
			echo '<td>';
			echo '<input type="checkbox" name="remove_country_code['.$table_row.']" value="' . $country_code . '">';
			echo '</td>';
			echo '</tr>';

			$table_row++;
		}
		return $table_row;
	}


	private function display_icl_language_flag( $country_code ){

		if( defined( 'WPML_GEO_REDIRECT_SHOW_FLAGS' ) &&
		    file_exists( ICL_PLUGIN_PATH . '/res/flags/' . strtolower( $country_code ) .'.png' )){

			$url = ICL_PLUGIN_URL . '/res/flags/' . strtolower( $country_code ) .'.png';
			return $img_tag = '<img width="18" height="12" alt="'.$country_code.'" src="' . $url . '" />';
		}
		return false;
	}


	private function display_mm_country_code_dropdown( $table_row=0 , $country_code_param=null , $remove_saved_codes=false ){

		$mm_country_codes = array(
			'AP','EU','AD','AE','AF','AG','AI','AL','AM','CW','AO','AQ','AR','AS','AT','AU','AW','AZ','BA','BB','BD','BE','BF','BG',
			'BH','BI','BJ','BM','BN','BO','BR','BS','BT','BV','BW','BY','BZ','CA','CC','CD','CF','CG','CH','CI','CK','CL','CM','CN',
			'CO','CR','CU','CV','CX','CY','CZ','DE','DJ','DK','DM','DO','DZ','EC','EE','EG','EH','ER','ES','ET','FI','FJ','FK','FM',
			'FO','FR','SX','GA','GB','GD','GE','GF','GH','GI','GL','GM','GN','GP','GQ','GR','GS','GT','GU','GW','GY','HK','HM','HN',
			'HR','HT','HU','ID','IE','IL','IN','IO','IQ','IR','IS','IT','JM','JO','JP','KE','KG','KH','KI','KM','KN','KP','KR','KW',
			'KY','KZ','LA','LB','LC','LI','LK','LR','LS','LT','LU','LV','LY','MA','MC','MD','MG','MH','MK','ML','MM','MN','MO','MP',
			'MQ','MR','MS','MT','MU','MV','MW','MX','MY','MZ','NA','NC','NE','NF','NG','NI','NL','NO','NP','NR','NU','NZ','OM','PA',
			'PE','PF','PG','PH','PK','PL','PM','PN','PR','PS','PT','PW','PY','QA','RE','RO','RU','RW','SA','SB','SC','SD','SE','SG',
			'SH','SI','SJ','SK','SL','SM','SN','SO','SR','ST','SV','SY','SZ','TC','TD','TF','TG','TH','TJ','TK','TM','TN','TO','TL',
			'TR','TT','TV','TW','TZ','UA','UG','UM','US','UY','UZ','VA','VC','VE','VG','VI','VN','VU','WF','WS','YE','YT','RS','ZA',
			'ZM','ME','ZW','AX','GG','IM','JE','BL','MF','BQ','SS'
		);

		/* For new country row - no need to show codes already used */
		if( $remove_saved_codes ){
			$mm_country_codes = array_diff( $mm_country_codes , array_keys( $this->language_mappings ) );
		}

		asort( $mm_country_codes );

		$select_name    = 'language_mappings[' . $table_row . '][country]';
		$output         = '<select name="' . $select_name . '" >';
		$output         .= "<option value='country_code_ddown_label'>Country Code</option>";
		
		foreach( $mm_country_codes as $country_code ) {

			$selected = $this->selected_html( $country_code , $country_code_param );

			$output .= "<option {$selected} >{$country_code}</option>";
		}

		$output .= "</select>";

		return $output;
	}


	private function selected_html( $value_1 , $value_2 ){
		return $selected = ($value_1 == $value_2 ? 'selected="selected"' : null);
	}
	
	
	private function display_language_code_dropdown( $table_row=0 , $lang_code_param='' , $is_default=false ){

		global $sitepress_settings;

		$args['skip_missing'] = intval($sitepress_settings['automatic_redirect'] == 1);
		$languages = apply_filters( 'wpml_active_languages', null, $args );

		$select_name = $is_default === true ? 'default_redirect_language' : 'language_mappings['. $table_row .'][language]';

		$output = '<select name="' . $select_name . '" >';

		foreach( $languages as $language ) {

			$selected = $this->selected_html( $language['code'] , $lang_code_param );
			$output .= "<option {$selected} >{$language['code']}</option>";
		}

		$output .= "</select>";

		return $output;
	}
	
	private function display_language_currency_dropdown_devd( $table_row=0 , $lang_curr_param='' , $is_default=false ){

		global $woocommerce_wpml;
		$currency_arr = $woocommerce_wpml->multi_currency->get_currencies('include_default = true');

		$select_name = 'language_mappings[' . $table_row . '][currency]';

		$output = '<select name="' . $select_name . '" >';

		foreach($currency_arr as $key => $value){

			$selected = $this->selected_html( $key , $lang_curr_param );
			$output .= "<option {$selected} >{$key}</option>";
		}

		$output .= "</select>";

		return $output;
	}


	private function display_add_new_country_mapping_row( $table_row ){
		
		echo '<tr valign="top" style="border-top: dotted black 2px;">';
		echo '<td>';
		echo $this->display_mm_country_code_dropdown( $table_row, null, true );
		echo "<strong> => </strong>";
		echo $this->display_language_code_dropdown( $table_row );
		echo '</td>';
		echo '<td>';
		echo $this->display_language_currency_dropdown_devd( $table_row );
		echo '</td>';
		echo '<td>';
		echo "&nbsp;";
		echo '</td>';
		echo '</tr>';

	}


	private function display_select_default_language_row( $table_row , $default_language ){

		echo '<tr valign="top" style="border-top: dotted black 2px;">';
		echo '<td colspan="2">';
		echo "Any other place on this planet";
		echo "<strong> => </strong>";
		echo $this->display_language_code_dropdown( $table_row , $default_language , true );
		echo '</td>';
		echo '</tr>';

	}
}
