<?php
$ad_code = HBI_Ad_Manager_Public::generate_ad_code_html( $settings->ad_unit );

if( $ad_code )
  echo "<div class='widget dfp_ad_unit_widget'>$ad_code</div>";


