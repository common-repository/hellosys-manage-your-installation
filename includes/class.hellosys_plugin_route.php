<?php

require_once dirname( HD_HELLOSYS_PLUGIN_FILE ) . '/../rest-api/lib/endpoints/class-wp-rest-users-controller.php';

class HS_Plugin_Route extends WP_REST_Controller {

    public function register_routes() {

      // Routes namespace and base
      $namespace = 'hellosys';
      $base = 'plugin';

      // Check login
      register_rest_route( $namespace, '/' . $base . '/login', array(
          'methods'         => WP_REST_Server::READABLE,
          'callback'        => array( $this, 'check_login' ),
          'permission_callback' => array( $this, 'check_login_permission_check' ),
		      'args'            => array(
			    'context'          => array(),
		  )
      ) );

      // Get site data
      register_rest_route( $namespace, '/' . $base . '/info', array(
          'methods'         => WP_REST_Server::READABLE,
          'callback'        => array( $this, 'get_info' ),
          'permission_callback' => array( $this, 'get_info_permission_check' ),
		      'args'            => array(
			    'context'          => array(),
		  )
      ) );

      // Put/remove site in/from maintenance mode
      register_rest_route( $namespace, '/' . $base . '/maintenance', array(
          'methods'         => WP_REST_Server::READABLE,
          'callback'        => array( $this, 'set_maintenance' ),
          'permission_callback' => array( $this, 'set_maintenance_permission_check' ),
          'args'            => array(
          'context'          => array(),
      )
      ) );

      // Not yet implemented
      register_rest_route( $namespace, '/' . $base . '/screenshot', array(
          'methods'         => WP_REST_Server::READABLE,
          'callback'        => array( $this, 'print_screen' ),
          'permission_callback' => array( $this, 'print_screen_permission_check' ),
          'args'            => array(
          'context'          => array(),
      )
      ) );

    // Get/Update version of wp core
     register_rest_route( $namespace, '/' . $base . '/wpversion', array(
        array(
           'methods'         => WP_REST_Server::READABLE,
           'callback'        => array( $this, 'get_wpversion' ),
           'permission_callback' => array( $this, 'get_version_permission_check' ),
           'args'            => array( ),
           ),
       array(
           'methods'         => WP_REST_Server::CREATABLE,
           'callback'        => array( $this, 'update_wpversion' ),
           'permission_callback' => array( $this, 'update_version_permission_check' ),
           'args'            => array(),
           ),
       ) );

       // Get site plugins
       register_rest_route( $namespace, '/' . $base . '/plugins', array(
          array(
             'methods'         => WP_REST_Server::READABLE,
             'callback'        => array( $this, 'get_plugins' ),
             'permission_callback' => array( $this, 'get_plugins_permission_check' ),
             'args'            => array(),
             ),
         ) );

       // Update a given plugin
       register_rest_route( $namespace, '/' . $base . '/plugins/update', array(
          array(
             'methods'         => WP_REST_Server::READABLE,
             'callback'        => array( $this, 'update_plugin' ),
             'permission_callback' => array( $this, 'update_plugin_permission_check' ),
             'args'            => array(),
             ),
         ) );

       // Get site users list
       register_rest_route( $namespace, '/' . $base . '/users', array(
           array(
               'methods'         => WP_REST_Server::READABLE,
               'callback'        => array( $this, 'get_users' ),
               'permission_callback' => array( $this, 'get_users_permission_check' ),
               'args'            => array(
                   'context'          => array(
                       'default'      => 'view',
                   ),
               ),
           ),
       ) );

       // Update a given user
       register_rest_route( $namespace, '/' . $base . '/users' . '/(?P<id>[\d]+)', array(
           array(
               'methods'         => WP_REST_Server::READABLE,
               'callback'        => array( $this, 'modify_user' ),
               'permission_callback' => array( $this, 'modify_user_permissions_check' ),
               'args'            => array(
                   'context'          => array(
                       'default'      => 'view',
                   ),
               ),
           ),
       ) );
      }

      public function check_login( $request ) {

        global $wpdb;
        $params = $request->get_params();

        if(isset($params['token'])){
          $token = $params['token'];
          $results = $wpdb->get_results( "SELECT * FROM $wpdb->prefix" . "usermeta WHERE meta_key = 'login_token' && meta_value = '$token'", ARRAY_A );
          if($results){
            $data = array('site_title' => get_bloginfo());
            $response = array('result' => true, 'data' => $data);
  			    return new WP_REST_Response( $response , 200 );
          }
          else{
            $response = array('result' => false, 'data' => 'wrong token');
  			    return new WP_REST_Response( $response , 200 );
          }
        }else if(isset($params['user_login']) && isset($params['user_pw'])){
            $user = wp_authenticate($params['user_login'], $params['user_pw']);

            if($user instanceof WP_User){

              $token_meta = get_user_meta($user->id, 'login_token', true);
              if(!$token_meta){
                $token_meta = md5(uniqid(rand(), true));
                update_user_meta($user->id, 'login_token', $token_meta);
              }

              $data = array('token' => $token_meta, 'site_title' => get_bloginfo());
              $response = array('result' => true, 'data' => $data);
    			    return new WP_REST_Response( $response , 200 );

            }else if($user instanceof WP_Error){

              $result = $user->errors;
              $response = array('result' => false, 'data' =>  array(), 'reason' => key($result));
    			    return new WP_REST_Response( $response , 200 );
            }
        }
        else{
          $response = array('result' => false, 'data' => array(), 'reason' => 'malformed request');
          return new WP_REST_Response( $response, 400 );
        }

    }

    public function get_wpversion( $request ) {

      global $wp_version;

      if($wp_version){
        $response = array('result' => true, 'data' => array('wp_version' => $wp_version));
        return new WP_REST_Response( $response , 200 );
      }
      else{
        $response = array('result' => false, 'data' => 'failed to access wp version');
        return new WP_REST_Response( $response , 200 );
      }
    }

    public function print_screen( $request ) {

        $response = array('result' => false, 'data' => 'not yet implemented');
        return new WP_REST_Response( $response , 200 );
    }

    public function set_maintenance( $request ) {
		$maintenance = get_option('hd_hellosys_maintenance');
        $this->maintenance_mode(!$maintenance);
        if($maintenance){
        	$response = array('result' => true, 'data' => 'maintenance mode deactivated');
        }
        else{
        	$response = array('result' => true, 'data' => 'maintenance mode activated');
        }
        return new WP_REST_Response( $response , 200 );
    }

    public function maintenance_mode( $enable = false ) {
      //$maintenance_path= ABSPATH . ".maintenance";
        /*$content = '<?php $upgrading = ' . time() . '; ?>';
        $fp = fopen($maintenance_path,"wb");
        fwrite($fp,$content);
        fclose($fp);*/
        update_option('hd_hellosys_maintenance', $enable);
  	}

    public function get_users( $request ) {
      $users = get_users();
      $users_data = array();

      foreach($users as $user){
	      $is_admin = false;
	      if(isset($user->allcaps['manage_options'])){
		      $is_admin = $user->allcaps['manage_options'];
	      }
          $user_blocked_meta = get_user_meta($user->id, 'user_blocked', true);
          $avatar = get_avatar_url($user->id);
        	$users_data[] = array('user_id' => (int) $user->data->ID, 'display_name' => $user->data->display_name, 'email' => $user->data->user_email, 'user_blocked' => ($user_blocked_meta!==''), 'avatar' => $avatar,
        	'role' => $user->roles[0], 'is_admin' => $is_admin);
      }

      $response = array('result' => true, 'data' => $users_data);
      return new WP_REST_Response( $response , 200 );
    }

    public function get_plugins( $request ) {

        wp_update_plugins();
        $plugins_list = get_option('_site_transient_update_plugins');
        $has_update_plugins = $plugins_list->response;
        $no_update_plugins = $plugins_list->no_update;

        $all_plugins = get_plugins();
        $active_plugins = get_option('active_plugins');
        $plugins_array = array();

        foreach($all_plugins as $key => $value){
	        if(in_array($key, $active_plugins)){
		        $id = $key;
		        $current_version = $value['Version'];
		        $slugarray = explode('/', $key);
		        $slug = $slugarray[0];
		        $name = $value['Name'];
		        $has_update = false;
		        $new_version = $current_version;

		        if(isset($has_update_plugins[$key])){
			        $has_update = true;
			        $new_version = $has_update_plugins[$key]->new_version;
		        }

		        $plugins_array[] = array('slug' => $slug, 'name' => $name, 'id' => $key, 'current_version' => $current_version, 'new_version' => $new_version, 'has_update' => $has_update);
	        }
        }

        $response = array('result' => true, 'data' => $plugins_array);
        return new WP_REST_Response( $response , 200 );
    }

    public function update_plugin( $request ) {

        $plugins_list = get_option('_site_transient_update_plugins');
        $params = $request->get_params();

        if(isset($params['id'])){
          $id = $params['id'];

          $exists = array_key_exists($id, $plugins_list->response);

          if($exists){

            include_once( ABSPATH . 'wp-admin/includes/class-wp-upgrader.php' );
        		include_once( ABSPATH . 'wp-admin/includes/file.php' );
        		include_once( ABSPATH . 'wp-admin/includes/plugin.php' );
        		include_once( ABSPATH . 'wp-admin/includes/update.php' );
        		if ( ! class_exists( 'Bulk_Plugin_Upgrader_Skin' ) ){
        			include_once( ABSPATH . 'wp-admin/includes/class-wp-upgrader-skins.php' );
            }

            $plugin = array($id);

            ob_start();

	            $upgrader = new Plugin_Upgrader( new Bulk_Plugin_Upgrader_Skin() );
      			$result = $upgrader->bulk_upgrade( $plugin );
	  			wp_update_plugins();

            ob_end_flush();
            ob_clean();
            ob_start();


            if(!$result){
              $response = array('result' => false, 'data' => array(), 'reason' => 'failed to update plugin');
            }
            else{
              $response = array('result' => true, 'data' => array());
            }

          }
          else{
            $response = array('result' => false, 'data' => array(), 'reason' => 'plugin does not need update');
          }
          return new WP_REST_Response( $response , 200 );
        }
        else{
          $response = array('result' => false, 'data' => array(), 'reason' => 'malformed request');
          return new WP_REST_Response( $response, 400 );
        }

    }

      public function update_wpversion( $request ) {

        global $wp_version;

        $version = $wp_version;

        $core = get_option('_site_transient_update_core');
        if(isset($core->updates[0])){
        	$version = $core->updates[0];
        }

        require_once( ABSPATH . 'wp-admin/includes/class-wp-upgrader.php' );
        require_once( ABSPATH . 'wp-admin/includes/file.php' );
        require_once( ABSPATH . 'wp-admin/includes/update.php' );
        require_once( ABSPATH . 'wp-admin/includes/misc.php' );

        $upgrader = new \Core_Upgrader();
        $re = $upgrader->upgrade($version, array('allow_relaxed_file_ownership' => true));

        if($re instanceof WP_Error){
          $result = $re->errors;
          $response = array('result' => false, 'data' =>  key($result));
          return new WP_REST_Response( $response , 200 );
        }else{
          $response = array('result' => true, 'data' =>  $re);
          return new WP_REST_Response( $response , 200 );
        }
    }

    public function get_info( $request ) {

        $folder_size = $this->get_dir_size(ABSPATH);
        global $wp_version;

        // TTFB + TTLB + SIZE
        $site_url = get_site_url();
        $output = shell_exec("curl -Ls -o /dev/null -w '%{time_starttransfer},%{size_download},%{time_total}' $site_url");

        if(empty($output)){
          $ttfb = "0";
          $ttlb = "0";
          $page_size = "0";
	    } else {
          $dados = explode(",",$output);
          $ttfb = $dados[0];
          $ttlb = $dados[2];
          $page_size = $dados[1];
	    }

		  $count_users = count_users();

          $data = array('ttfb' => (double) $ttfb, 'ttlb' => (double) $ttlb, 'page_size' => (int) $page_size, 'folder_size' => (float) $folder_size, 'users_number' => $count_users['total_users']);

          if($wp_version){
            $data['wp_version'] =  $wp_version;

            $data['wp_maintenance'] = (boolean) get_option('hd_hellosys_maintenance');


            $version_object = get_option('_site_transient_update_core');

            if(!empty($version_object)){

              if($version_object->version_checked !== $version_object->updates[0]->version){
                $data['wp_update_version'] =  $version_object->updates[0]->version;
                $data['has_update_core'] = true;
              }
              else{
                $data['wp_update_version'] =  $version_object->updates[0]->current;
                $data['has_update_core'] = false;
              }

              $data['php_version'] = phpversion();
              $data['mysql_version'] = $version_object->updates[0]->mysql_version;

              $active_plugins = get_option('active_plugins');
              $to_update_plugins = get_option('_site_transient_update_plugins');
              $count = 0;
              foreach ($active_plugins as $plugin)  {
	              if ($to_update_plugins->response[$plugin] ) {
					  $count +=1;
	              }
              }

              $data['active_plugins'] = count($active_plugins);
              $data['has_update_plugins'] = $count;

              $response = array('result' => true, 'data' => $data);
            }else{
              $response = array('result' => false, 'data' => array(), 'reason' => 'failed to access version data');
            }
          }
          else{
            $response = array('result' => false, 'data' => array(), 'reason' => 'failed to access wp version');
          }


        return new WP_REST_Response( $response , 200 );
    }

    public function modify_user( $request ) {

      $params = $request->get_params();

      if(isset($params['action']) && isset($params['id'])){
        $action = sanitize_text_field($params['action']);
        $user_id = sanitize_text_field($params['id']);
        $user = new WP_USER($user_id);

        if(isset($user->data->user_login)){

          $user_login = $user->data->user_login;

          if($action == 'block'){

	        if (user_can($user, 'manage_options')){
		       	$response = array('result' => false, 'data' => array(), 'reason' => 'cannot_block_admin');
				return new WP_REST_Response( $response , 200 );
	        }
	        else{

	            $user_blocked_meta = get_user_meta($user->id, 'user_blocked', true);

	            if(!$user_blocked_meta){
	              $block_msg = 'user_blocked';
	              update_user_meta($user->id, 'user_blocked', 1);
	            }
	            else{
	              $block_msg = 'user_unblocked';
	              update_user_meta($user->id, 'user_blocked', 0);
	            }

	            $response = array('result' => true, 'data' => $block_msg);
	            return new WP_REST_Response( $response , 200 );

            }
          }
          else if($action == 'change_pw'){

            $result = $this->retrieve_password($user_login);
            if($result){
              $response = array('result' => true, 'data' => array());
            }
            else{
              $response = array('result' => false, 'data' => array(), 'reason' => 'failed to send email');
            }
            return new WP_REST_Response( $response , 200 );
          }
          else{
            $response = array('result' => false, 'data' => array(), 'reason' => 'malformed request');
            return new WP_REST_Response( $response, 400 );
          }
      }else{
        $response = array('result' => false, 'data' => array(), 'reason' => 'user does not exist');
        return new WP_REST_Response( $response, 400 );
      }
    }
      else{
        $response = array('result' => false, 'data' => array(), 'reason' => 'malformed request');
        return new WP_REST_Response( $response, 400 );
      }
    }

    /*   * *
    * Get the directory size
    * @param directory $directory
    * @return integer
    */

    function get_dir_size($directory) {
        $size = 0;
        foreach (new RecursiveIteratorIterator(new RecursiveDirectoryIterator($directory)) as $file) {
            $size += $file->getSize();
        }
        $to_gigabyte = 1024 * 1024 * 1024;
        return $this->divideFloat($size, $to_gigabyte);
    }

    function divideFloat($a, $b, $precision=3) {
    	$a*=pow(10, $precision);
    	$result=(int)($a / $b);
    	if (strlen($result)==$precision) return '0.' . $result;
    	else return preg_replace('/(\d{' . $precision . '})$/', '.\1', $result);
	}

    function retrieve_password($user_login){
      global $wpdb, $wp_hasher;

      $user_login = sanitize_text_field($user_login);

      if ( empty( $user_login) ) {
          return false;
      } else if ( strpos( $user_login, '@' ) ) {
          $user_data = get_user_by( 'email', trim( $user_login ) );
          if ( empty( $user_data ) )
             return false;
      } else {
          $login = trim($user_login);
          $user_data = get_user_by('login', $login);
      }

      do_action('lostpassword_post');


      if ( !$user_data ) return false;

      // redefining user_login ensures we return the right case in the email
      $user_login = $user_data->user_login;
      $user_email = $user_data->user_email;

      do_action('retreive_password', $user_login);  // Misspelled and deprecated
      do_action('retrieve_password', $user_login);

      $allow = apply_filters('allow_password_reset', true, $user_data->ID);

      if ( ! $allow )
          return false;
      else if ( is_wp_error($allow) )
          return false;

      $key = wp_generate_password( 20, false );
      do_action( 'retrieve_password_key', $user_login, $key );

      if ( empty( $wp_hasher ) ) {
          require_once ABSPATH . 'wp-includes/class-phpass.php';
          $wp_hasher = new PasswordHash( 8, true );
      }
      $hashed = time() . ':' . $wp_hasher->HashPassword( $key );
      $wpdb->update( $wpdb->users, array( 'user_activation_key' => $hashed ), array( 'user_login' => $user_login ) );

      $headers = array('Content-Type: text/html; charset=UTF-8');

  	  $body = "<!DOCTYPE html PUBLIC '-//W3C//DTD XHTML 1.0 Transitional//EN' 'http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd'>
  		<html xmlns='http://www.w3.org/1999/xhtml'>
  		<head>
  		<!-- If you delete this tag, the sky will fall on your head -->
  		<meta name='viewport' content='width=device-width' />

  		<meta http-equiv='Content-Type' content='text/html; charset=UTF-8' />
  		<title>hellosys.io</title>

  		<link rel='stylesheet' type='text/css' href='https://api.hellosys.io/wp-content/plugins/hellosys-api/email-template/stylesheets/email.css' />
  		<link href='https://fonts.googleapis.com/css?family=Lato:400,700' rel='stylesheet' type='text/css'>

  		</head>

  		<body bgcolor='#FFFFFF'>

  		<!-- HEADER -->
  		<table class='head-wrap'>
  		<tr>
  			<td></td>
  			<td class='header container'>

  					<div class='content' style='text-align: center; margin-top: 30px;'>
  						<table>
  						<tr>
  							<td><img src='https://api.hellosys.io/wp-content/plugins/hellosys-api/email-template/assets/hellosys.svg' /></td>
  						</tr>
  					</table>
  					</div>

  			</td>
  			<td></td>
  		</tr>
  		</table><!-- /HEADER -->


  		<!-- BODY -->
  		<table class='body-wrap'>
  		<tr>
  			<td></td>
  			<td class='container' bgcolor='#FFFFFF'>

  				<div class='content'>
  				<table>
  					<tr>
  						<td>

  							<div style='width:100%; text-align:center;'><img style='width:200px; height:94px;' src='https://api.hellosys.io/wp-content/plugins/hellosys-api/email-template/assets/password.png' /></div>

  							<div style='width: 100%; text-align: center; margin-top: 30px;'><h3>Password Recovery!</h3></div>

  							<div style='width: 100%; text-align: center; margin-top: 30px; line-height: 30px;'><p>" . nl2br($message) . " </p></div>

  							<div style='width: 100%; text-align: center; margin-top: 50px;'><a href='". $url ."'><span class='btt'>RESET PASSWORD</span></a></div>

  						</td>
  					</tr>
  				</table>
  				</div>

  			</td>
  			<td></td>
  		</tr>
  		</table><!-- /BODY -->

  		<!-- FOOTER -->
  		<table class='footer-wrap'>
  		<tr>
  			<td></td>
  			<td class='container'>
  					<div style='width: 100%; height: 2px; border-bottom: 1px solid #F0F0F0; margin-top: 60px;'>&nbsp;</div>
  					<!-- content -->
  					<div class='content'>
  					<table>
  					<tr>
  						<td align='left' width='50%'>
  							<h4>hellosys.com</h4>
  						</td>
  						<td width='50%' align='right'>
  							<div style='display: block;'>
  								<a href='https://www.facebook.com/hellodevapps/'><img src='https://api.hellosys.io/wp-content/plugins/hellosys-api/email-template/assets/footer_icon_facebook.svg'></a>
  								<a href='https://www.instagram.com/hellodevapps/'><img src='https://api.hellosys.io/wp-content/plugins/hellosys-api/email-template/assets/footer_icon_instagram.svg'></a>
  								<a href='https://twitter.com/hellodevapps'><img src='https://api.hellosys.io/wp-content/plugins/hellosys-api/email-template/assets/footer_icon_twitter.svg'></a>
  							</div>
  						</td>
  					</tr>
  				</table>
  					</div><!-- /content -->

  			</td>
  			<td></td>
  		</tr>
  		</table><!-- /FOOTER -->

  		</body>
  		</html>";


      if ( $message && !wp_mail($user_email,"hellosys.io | Password Recovery", $body, $headers)) {
          return false;
      }

      return true;
  }

    public function is_token_valid($request){
      global $wpdb;
      $params = $request->get_params();
      if(isset($params['token'])){
        $token = $params['token'];
        $results = $wpdb->get_results( "SELECT * FROM $wpdb->prefix" . "usermeta WHERE meta_key = 'login_token' && meta_value = '$token'", ARRAY_A );
        if($results){
          return true;
        }
      }
        return false;

      }

    // Permission validations
    public function get_version_permission_check( $request ) {

      return $this->is_token_valid($request);
    }

    public function update_version_permission_check( $request ) {
      return $this->is_token_valid($request);
    }

    public function modify_user_permissions_check( $request ) {
      return $this->is_token_valid($request);
    }

    public function check_login_permission_check( $request ) {
      return true;
    }

    public function get_info_permission_check( $request ) {
      return $this->is_token_valid($request);
    }

    public function get_plugins_permission_check( $request ) {
      return $this->is_token_valid($request);
    }

    public function get_users_permission_check( $request ) {
      return $this->is_token_valid($request);
    }

    public function update_plugin_permission_check( $request ) {
      return $this->is_token_valid($request);
    }

    public function set_maintenance_permission_check( $request ) {
      return $this->is_token_valid($request);
    }

    public function print_screen_permission_check( $request ) {
      return $this->is_token_valid($request);
    }

}
