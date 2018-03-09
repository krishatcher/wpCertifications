<?php

/*
Plugin Name: wpCertifications
Plugin URI:  http://www.krishatcher.com/wordpress/wpCertifications
Description: Wordpress plugin which allows sites to track certifications which users have completed.
Version:     0.5
Author:      Kris Hatcher
License:     GPL2
License URI: https://www.gnu.org/licenses/gpl-2.0.html

wpCertifications is free software: you can redistribute it and/or modify
it under the terms of the GNU General Public License as published by
the Free Software Foundation, either version 2 of the License, or
any later version.

wpCertifications is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
GNU General Public License for more details.

You should have received a copy of the GNU General Public License
along with wpCertifications. If not, see https://www.gnu.org/licenses/gpl-2.0.html.
*/

class wpCertifications {
	function register_menu() {
		// Add a new submenu under Settings:
		add_options_page( __( 'WP Certs Configuration', 'config-pageTitle' ), __( 'wpCertifications', 'config-menuName' ), 'manage_options', 'wpCertifications', array( 'wpCertifications', 'get_settings_page' ) );
	}

	function register_styles() {
		if (!is_admin())
		{
			wp_register_style( 'wpCertifications', plugins_url( 'form.css', __FILE__ ) );
			wp_enqueue_style( 'wpCertifications' );
		}
	}

	function register_scripts() {
		if (is_admin()) {
			wp_register_script( 'wpCertifications', plugins_url( 'options.js', __FILE__ ), [], false, true );
		} else {
			wp_register_script( 'wpCertifications', plugins_url( 'form.js', __FILE__ ), [], false, true );
		}

		wp_enqueue_script( 'wpCertifications' );
	}

    function load() {
        $sc = new wpCertifications();

        $sc->register_styles();
        $sc->register_scripts();
    }

	function get_settings_page() {
		if ( !current_user_can( 'manage_options' ) )  {
			wp_die( __( 'You do not have sufficient permissions to access this page.' ) );
		}

		// variables for the field and option names
		$opt_name = 'wpCertifications_configData';
		$hidden_field_name = 'sg_submit_hidden';
		$api_key_field = 'sg_api_key';
		$list_id_field = 'sg_list_id';

		$view_bag = array();

		// Read in existing option value from database
		$opt_val = get_option( 'wpCertifications_configData' );

		$saved_data = json_decode($opt_val);

		$api_key = $saved_data->api_key;
		$list_name = $saved_data->list_name;
		$list_id = $saved_data->list_id;
		$at_network = "";

		if ($saved_data->api_key_at_network) {
			$at_network = 'disabled="disabled" data-autoRun="true"';
		}

		// See if the user has posted us some information
		// If they did, this hidden field will be set to 'Y'
		if( isset($_POST[ $hidden_field_name ]) && $_POST[ $hidden_field_name ] == 'Y' ) {
			$apiInfo = explode("~", $_POST[$list_id_field]);

			// Read posted values
			$opts = new wpCertificationsOptions();
			$opts->api_key = $_POST[$api_key_field];
			$opts->list_id = $apiInfo[0];
			$opts->list_name = $apiInfo[1];

			if ($opts->api_key == "") {
				// the API Key isn't shown to the user, so if the field is blank then they didn't change it
				// and we need to pull it from the DB values so that it's not overwritten.
				$opts->api_key = $api_key;
			} else if ($opts->list_id == $list_id) {
				// ensure that if the API Key changes, the List info is either changed or cleared out.
				$opts->list_id = "";
				$opts->list_name = "";
			}

			// Save the posted value in the database
			update_option($opt_name, json_encode($opts));

			$api_key = $opts->api_key;
			$list_name = $opts->list_name;

			$view_bag['saved'] = true;
		}

		$view_bag['api_key_field'] = $api_key_field;
		$view_bag['list_id_field'] = $list_id_field;
		$view_bag['hidden_field_name'] = $hidden_field_name;
		$view_bag['api_key'] = $api_key;
		$view_bag['list_name'] = $list_name;
		$view_bag['at_network'] = $at_network;

		//$sc = new wpCertifications();
		//$sc->get_render( 'options.php', $view_bag );

		$data = $view_bag;
		require dirname(__FILE__).'/options.php';
	}

	function short_code_func(){
		// Read in existing option value from database
		$opt_val = get_option( 'wpCertifications_configData' );

		$savedData = json_decode($opt_val);

		$view_bag['list_id'] = $savedData->list_id;
		$view_bag['api_key'] = $savedData->api_key;

		$sc = new wpCertifications();
		$content = $sc->get_render( 'form.php', $view_bag );
        return $content;
	}

	function get_render($tpl, $data = array()){
		extract($data);
        
        ob_start();
		require dirname(__FILE__).'/'.$tpl;
        return ob_get_clean();
	}
}

class wpCertificationsOptions {
	public $api_key = "";
	public $list_id = "";
	public $list_name = "";
	public $api_key_at_network = false;
}

add_action( 'admin_menu', array( 'wpCertifications', 'register_menu' ) );
add_action( 'admin_enqueue_scripts', array( 'wpCertifications', 'register_scripts' ) );

add_action( 'plugins_loaded', array( 'wpCertifications', 'load' ) );

if (!is_admin()) {
    add_shortcode('wpCertifications', array('wpCertifications', 'short_code_func'));
}

