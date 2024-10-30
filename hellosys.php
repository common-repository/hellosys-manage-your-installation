<?php

/*
Plugin Name: Hellosys
Plugin URI: http://wordpress.hellodev.us/plugins/hellosys
Description: Your website management app makes everything faster and easier.
Version: 1.0
Author: Hellodev
Text Domain: hellodev-hellosys
Author URI: http://hellodev.us
License: Closed Source
*/

if(!class_exists('hellodev_hellosys')){

  class hellodev_hellosys {

      public function __construct(){

        // Checks if user is admin and if WooCommerce is inactive
      	if ( !in_array( 'rest-api/plugin.php', apply_filters( 'active_plugins', get_option( 'active_plugins' ) ))){

      		// If so display warning message on admin notices
      		add_action( 'admin_notices', array($this, 'hd_hs_activation_notice'));

      	}else{
          // Register custom routes
          add_action('rest_api_init', array($this, 'hd_hs_resgister_routes'));

          // Add meta to header
          add_action('wp_head', array($this, 'hd_hs_inject_header'));

          // Add plugin settings
          add_action( 'admin_menu', array( $this, 'hd_hs_plugin_settings_menu' ) );

          // Add rewrite rule
          add_action( 'init', array( $this, 'hellosys_init_internal' ));
          add_filter( 'query_vars', array( $this, 'hellosys_query_vars'));
          add_action( 'parse_request', array( $this,'hellosys_parse_request' ));

          // Stop blocked users from loggin in
          add_action( 'wp_login', array( $this, 'hellosys_user_login'), 10, 2 );
          add_filter( 'login_message', array( $this, 'hellosys_user_login_message'));

          // Redirect to maintenance page when in maintenance mode
          add_action('template_redirect', array($this, 'hellosys_maintenance_redirect'));
        }
  	  }

  	  public function hellosys_maintenance_redirect(){
          $maintenance = get_option('hd_hellosys_maintenance');
          if($maintenance == 1 || $maintenance == 'true'){
              $url = get_home_url() . '/maintenance';
              header('Location: '. $url);
          }
      }

      public function hellosys_user_login( $user_login, $user = null ) {

    		if ( !$user ) {
    			$user = get_user_by('login', $user_login);
    		}
    		if ( !$user ) {
    			// not logged in - definitely not disabled
    			return;
    		}
    		// Get user meta
    		$disabled = get_user_meta( $user->ID, 'user_blocked', true );

    		// Is the use logging in disabled?
    		if ( $disabled == '1' ) {
    			// Clear cookies, a.k.a log user out
    			wp_clear_auth_cookie();

    			// Build login URL and then redirect
    			$login_url = site_url( 'wp-login.php', 'login' );
    			$login_url = add_query_arg( 'disabled', '1', $login_url );
    			wp_redirect( $login_url );
    			exit;
    		}
    	}

      public function hellosys_user_login_message( $message ) {

    		if ( isset( $_GET['disabled'] ) && $_GET['disabled'] == 1 )
    			$message =  '<div id="login_error">' . apply_filters( 'ja_disable_users_notice', __( 'Account blocked', 'hellodev-hellosys' ) ) . '</div>';

    		return $message;
    	}

      // Rewrites rule to change endpoint url
      function hellosys_init_internal(){
          add_rewrite_rule( "maintenance", "index.php?maintenance_endpoint=1", "top" );
          flush_rewrite_rules();
      }

      // Add endpoint
      function hellosys_query_vars( $query_vars ){
          $query_vars[] = 'maintenance_endpoint';
          return $query_vars;
      }

      // Adds file to endpoint
      function hellosys_parse_request( &$wp ){

          if ( array_key_exists( 'maintenance_endpoint', $wp->query_vars ) ) {
              new hd_hs_maintenance();
              exit();
          }
          // Malformed URL
          else{
          }

          return;
      }

      function hd_hs_activation_notice() {

        // Show error notice if REST API is not active
    		$hd_wooacf_notice = __('WP REST API is not active and hellosys is not working!', 'hellodev-hellosys');
        $download_url = 'https://wordpress.org/plugins/rest-api/';
    		echo "<div class='error'><p><strong>$hd_wooacf_notice </strong><a target='_blank' href=$download_url>Download here</a></p></div>";
    	}

      public function hd_hs_resgister_routes () {
       require_once(HD_HELLOSYS_PLUGIN_PATH . "/includes/class.hellosys_plugin_route.php");

         $plugin_route = new HS_Plugin_Route();
         $plugin_route->register_routes();
     }

     function hd_hs_plugin_settings_menu() {

        add_options_page(
       'hellosys', 'Hellosys', 'manage_options', 'hellosys-settings.php', array($this, 'hd_hs_settings_page'));
     }

    function hd_hs_settings_page() {
   		include HD_HELLOSYS_PLUGIN_PATH . 'includes/hd_hs_settings.php';
   	}

     public function hd_hs_inject_header () {
       _e( '<!-- This site uses hellosys - http://wordpress.hellodev.us/plugins/http://wordpress.hellodev.us/plugins/hellosys -->', 'hellodev-hellosys');
       echo "\n";
       echo '<meta name="hellosys" content="true" />';
       echo "\n";
    }
  }
}

if(class_exists('hellodev_hellosys')){

if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly
}

// Define some constants
define('HD_HELLOSYS_PLUGIN_PATH', plugin_dir_path( __FILE__ ));
define('HD_HELLOSYS_PLUGIN_FILE', __FILE__);

require_once HD_HELLOSYS_PLUGIN_PATH . 'includes/hd_hs_maintenance.php';

// Create new object
$hd_hellosys_loader = new hellodev_hellosys();

}
