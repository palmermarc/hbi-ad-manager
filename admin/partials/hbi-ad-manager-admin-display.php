<?php

/**
 * Provide a dashboard view for the plugin
 *
 * This file is used to markup the public-facing aspects of the plugin.
 *
 * @link       http://example.com
 * @since      1.0.0
 *
 * @package    HBI_Ad_Manager
 * @subpackage HBI_Ad_Manager/admin/partials
 */
?>
<div class="wrap">
    <form method="post" action="options.php">
    <?php
    settings_fields( 'hbi_ad_manager_option_group' );
    do_settings_sections( 'hbi_ad_manager' );
    submit_button();
    ?>
    </form>
</div>
