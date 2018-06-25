<?php
$start_date = get_post_meta( $post->ID, 'takeover_start', TRUE );
$end_date = get_post_meta( $post->ID, 'takeover_end', TRUE );
$takeover_background = get_post_meta( $post->ID, 'takeover_background', TRUE );
$takeover_ad_targeting = get_post_meta( $post->ID, 'takeover_ad_targeting', TRUE );
?>
<table class="form-table">
  <tbody>
  <tr>
    <th><label for="start">Start Date</label></th>
    <td>
      <input class="datepicker_field" type="text" name="takeover_start" value="<?php echo esc_attr( $start_date ); ?>" />
    </td>
  </tr>
  <tr>
    <th><label for="end">End date</label></th>
    <td>
      <input class="datepicker_field" type="text" name="takeover_end" value="<?php echo esc_attr( $end_date ); ?>" />
    </td>
  </tr>
  <tr>
    <th><label for="takeover_background"><?php esc_html_e( 'Background Image' ); ?></label></th>
    <td>
      <span class="uploaded_image">
        <?php if ( '' !== $takeover_background ) : ?>
          <img src="<?php echo esc_url( $takeover_background ); ?>" alt="" title="" />
        <?php endif; ?>
      </span>
      <input type="text" name="takeover_background" value="<?php echo esc_url( $takeover_background ); ?>" class="featured_image_upload widefat">
      <input type="button" name="image_upload" value="<?php esc_html_e( 'Upload Image' ); ?>" class="button upload_image_button">
      <input type="button" name="remove_image_upload" value="<?php esc_html_e( 'Remove Image' ); ?>" class="button remove_image_button">
    </td>
  </tr>
  <tr>
    <th><label for="ad_targeting">Set DFP takeover targeting?</label></th>
    <td>
      <select name="takeover_ad_targeting" id="takeover_ad_targeting">
        <option <?php selected( 0, $takeover_ad_targeting); ?>  value="0">No</option>
        <option <?php selected( 1, $takeover_ad_targeting); ?> value="1">Yes</option>
      </select>
    </td>
  </tr>
</table>