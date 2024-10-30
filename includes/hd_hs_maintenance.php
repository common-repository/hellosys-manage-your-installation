<?php

class hd_hs_maintenance{

    public function __construct(){
      $this->start();
    }

public function start(){
  $maintenance = get_option("hd_hellosys_maintenance");
  if ($maintenance === "true" || $maintenance) {
      $content = file_get_contents("http://api.hellosys.io/wp-content/plugins/hellosys-api/maintenance/index.html");
      echo $content;

  } else {
      $blog_url = get_site_url();
      header("Location: " . $blog_url);
  }
  }
}
