<?php

if (! defined('ABSPATH')) {
    exit(); // Exit if accessed directly
}

$user_id = get_current_user_id();
$token_meta = get_user_meta($user_id, 'login_token', true);
$site_url = get_site_url();

if(! $token_meta){
  $token_meta = md5(uniqid(rand(), true));
  update_user_meta($user_id, 'login_token', $token_meta);
}

$qr_string = $site_url . '|' . get_bloginfo() . '|' . $token_meta;

$hd_wooacf_field_errors = '';
$hd_wooacf_field_success = '';

$incoming_hook_url = get_option('hd_hs_slack_incoming_hook_url');

if ($_SERVER["REQUEST_METHOD"] == "POST") {
  if(isset($_POST['incoming_hook_url'])){
    update_option('hd_hs_slack_incoming_hook_url' ,$_POST['incoming_hook_url']);
    $incoming_hook_url = $_POST['incoming_hook_url'];
    $hd_wooacf_field_success = __("Settings updated", "hellodev-hellosys");
  }
}

$outgoing_hook_url = get_site_url() . '/index.php?hellosys_endpoint=1';


?>

<style>
.input-add-field {
	width: 20em !important;
}
</style>

<div class="wrap">

	<h2><?php _e("hellosys.io", "hellodev-hellosys"); ?>
  </h2>

	<div style="margin-top:40px;"><a href="http://hellosys.io"><img src="http://hellosys.io/banner.png" style="width: 60%; height: auto;"></a></div>
  </br>

  <h3><?php _e("Login QRCode", "hellodev-hellosys"); ?>
  </h3>

  <img src="http://chart.googleapis.com/chart?chs=200x200&cht=qr&chl=<?php echo $qr_string; ?>" alt="Login QRCode">

</div>
