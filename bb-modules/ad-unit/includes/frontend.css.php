<?php
$ad_unit = get_post( $settings->ad_unit );

$ad_height = get_post_meta( $ad_unit->ID, 'ad_height', TRUE );
$ad_width = get_post_meta( $ad_unit->ID, 'ad_width', TRUE );
?>

.fl-node-<?php echo $id; ?> .admin_advertising_display {
  height: <?php echo $ad_height; ?>px;
  line-height: <?php echo $ad_height; ?>px;
  width: <?php echo $ad_width; ?>px;
}