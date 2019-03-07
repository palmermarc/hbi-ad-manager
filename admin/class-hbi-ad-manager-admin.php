<?php

/**
 * The dashboard-specific functionality of the plugin.
 *
 * @link       http://example.com
 * @since      0.1
 *
 * @package    HBI_Ad_Manager
 * @subpackage HBI_Ad_Manager/admin
 */

/**
 * The dashboard-specific functionality of the plugin.
 *
 * Defines the plugin name, version, and two examples hooks for how to
 * enqueue the dashboard-specific stylesheet and JavaScript.
 *
 * @package    HBI_Ad_Manager
 * @subpackage HBI_Ad_Manager/admin
 * @author     Marc Palmer <mapalmer@hbi.com>
 */
class HBI_Ad_Manager_Admin {

	/**
	 * The ID of this plugin.
	 *
	 * @since    0.1
	 * @access   private
	 * @var      string    $plugin_name    The ID of this plugin.
	 */
	private $plugin_name;

	/**
	 * The version of this plugin.
	 *
	 * @since    0.1
	 * @access   private
	 * @var      string    $version    The current version of this plugin.
	 */
	private $version;

	/**
	 * Initialize the class and set its properties.
	 *
	 * @since    0.1
	 * @var      string    $plugin_name       The name of this plugin.
	 * @var      string    $version    The version of this plugin.
	 */
	public function __construct( $plugin_name, $version ) {

		$this->plugin_name = $plugin_name;
		$this->version = $version;

	}

	/**
	 * Register the stylesheets for the Dashboard.
	 *
	 * @since    0.1
	 */
	public function enqueue_styles() {

		wp_enqueue_style( $this->plugin_name, plugin_dir_url( __FILE__ ) . 'css/hbi-ad-manager-admin.css', array(), $this->version, 'all' );

    $screen = get_current_screen();
    if( $screen->post_type == 'takeover' ) {
      wp_enqueue_style( 'jquery-ui-redmond', 'https://ajax.googleapis.com/ajax/libs/jqueryui/1.12.1/themes/redmond/jquery-ui.css' );
      wp_enqueue_style( 'jquery-tiptip', FL_BUILDER_URL . 'css/jquery.tiptip.css', array(), $this->version );
      wp_enqueue_style( 'select2', FL_THEME_BUILDER_URL . 'css/select2.min.css', array(), $this->version );
      wp_enqueue_style( 'fl-theme-builder-layout-admin-edit', FL_THEME_BUILDER_URL . 'css/fl-theme-builder-layout-admin-edit.css', array(), $this->version );
    }
	}

	/**
	 * Register the JavaScript for the dashboard.
	 *
	 * @since    0.1
	 */
	public function enqueue_scripts() {
    global $pagenow;
    global $post;

    wp_enqueue_script( $this->plugin_name, plugin_dir_url( __FILE__ ) . 'js/hbi-ad-manager-admin.js', array( 'jquery','jquery-ui-datepicker' ), $this->version, true );

    $screen  = get_current_screen();

		if( $screen->post_type == 'takeover' ) {
      $object = get_post_type_object( $screen->post_type );

      // Scripts
      wp_enqueue_script( 'jquery-tiptip', FL_BUILDER_URL . 'js/jquery.tiptip.min.js', array( 'jquery' ) );
      wp_enqueue_script( 'select2', FL_THEME_BUILDER_URL . 'js/select2.full.min.js', array( 'jquery' ) );
      wp_enqueue_script( 'fl-theme-builder-layout-admin-edit', FL_THEME_BUILDER_URL . 'js/fl-theme-builder-layout-admin-edit.js', array( 'wp-util' ) );
      include_once( FL_THEME_BUILDER_DIR . 'classes/class-fl-theme-builder-rules-location.php' );
      include_once( FL_THEME_BUILDER_DIR . 'classes/class-fl-theme-builder-rules-user.php' );


      $bb_location_rules = new FLThemeBuilderRulesLocation();
      $FLThemeBuilderRulesUser = new FLThemeBuilderRulesUser();

      wp_localize_script( 'fl-theme-builder-layout-admin-edit', 'FLThemeBuilderConfig', array(
        'locations' => $bb_location_rules->get_admin_edit_config(),
        'exclusions' => $bb_location_rules->get_exclusions_admin_edit_config(),
        'nonce' => wp_create_nonce( 'fl-theme-builder' ),
        'postType' => $screen->post_type,
        'userRules' => $FLThemeBuilderRulesUser::get_saved( $post->ID ),
        'strings' => array(
          'allObjects' => _x( 'All %s', '%s is the post or taxonomy name.', 'fl-theme-builder' ),
          'alreadySaved' => _x( 'This location has already been added to the "%1$s" %2$s. Would you like to remove it and add it to this %1$s?', '%1$s is the post title. %2$s is the post type label.', 'fl-theme-builder' ),
          'assignedTo' => _x( 'Assigned to %s', '%s stands for post title.', 'fl-theme-builder' ),
          'choose' => __( 'Choose...', 'fl-theme-builder' ),
          'postTypePlural' => $object->label,
          'postTypeSingular' => $object->labels->singular_name,
          'search' => __( 'Search...', 'fl-theme-builder' ),
        ),
      ) );
    }
	}
    
  public function custom_ad_unit_title( $input ) {
    global $post_type;

    if ( is_admin() && 'ad_unit' == $post_type )
      return __( 'Enter Ad Unit Name' );

    return $input;
  }

  public function add_hbi_ad_manager_options_page() {
      add_options_page( 'HBI Ad Manager', 'HBI Ad Manager', 'administrator', 'hbi_ad_manager', array( $this, 'display_hbi_ad_manager_admin' ) );
  }

  public function display_hbi_ad_manager_admin() {
      require_once plugin_dir_path( dirname( __FILE__ ) ) . 'admin/partials/hbi-ad-manager-admin-display.php';
  }

  public function register_hbi_ad_manager_settings() {

    register_setting(
      'hbi_ad_manager_option_group',
      'hbi_ad_manager_settings',
      array( $this, 'validate_custom_ad_tag_fields' )
    );

    add_settings_section(
      'hbi_ad_manager_options_section',                         // the name of the section
      'HBI Ad Manager Settings',                                       // title displayed in this section
      NULL,                                                   // call back that renders the section description
      'hbi_ad_manager'                                        // page slug we are going to display this section on
    );

    add_settings_field(
      'set_request_mode',
      'Enable single request',
      array( $this, 'enable_single_request_callback' ),
      'hbi_ad_manager',
      'hbi_ad_manager_options_section'
    );

    add_settings_field(
      'active_conditionals',
      'Activate Conditional Functions',
      array( $this, 'activate_conditional_functions' ),
      'hbi_ad_manager',
      'hbi_ad_manager_options_section'
    );
  }

  function validate_custom_ad_tag_fields( $input ) {
    $new_input = array();

    $new_input['single_request'] = ( isset( $input['single_request'] ) ) ? 1 : 0;

    $new_conditionals = array();
    foreach( $input['active_conditionals'] as $function => $use ) {
      $new_conditionals[$function] = $use;
    }

    $new_input['active_conditionals'] = $new_conditionals;

    return $new_input;
  }

  function enable_single_request_callback() {
    $options = get_option('hbi_ad_manager_settings'); ?>
    <input type="checkbox" name="hbi_ad_manager_settings[single_request]" value="single" <?php checked( $options['single_request'], 1, true ); ?> />
    <p class="description">Because of limitations on the SingleRequest method, if you have more than 30 ad units, turning on Single Request may break the ads on your site. If you turn this on and your ads do not work, try disabling SingleRequest and seeing if that resolves the issue.</p>
    <?php
  }

  function enable_asnyc_rendering_callback() {
    $options = get_option('hbi_ad_manager_settings');
    ?><input type="checkbox" name="hbi_ad_manager_settings[async_rendering]" value="single" <?php checked( $options['async_rendering'], 1, true ); ?> /><?php
  }

  function activate_conditional_functions() {
    $options = get_option( 'hbi_ad_manager_settings' );
    $count = 0;
    foreach( $options['active_conditionals'] as $conditional_function => $use ) {
      $count++;
      if( $count == 1 ) { echo '<div class="one-third">'; }
      echo '<input type="hidden" name="hbi_ad_manager_settings[active_conditionals][' . $conditional_function . ']" value="0" />';
      echo '<p><input ' . checked( 1, $use, false ). ' type="checkbox" name="hbi_ad_manager_settings[active_conditionals][' . $conditional_function . ']" value="1" /> ' . $conditional_function . '</p>';
      if( $count == 20 ) { echo '</div>'; $count = 0; }
    }
    echo '<div class="clearfix"></div>';
  }
    
  function hbi_ad_manager_meta_boxes() {
    add_meta_box( 'ad-unit-details', esc_html__( 'Ad Unit Details', '' ), array( $this, 'hbi_ad_manager_meta_box' ), 'ad_unit' );

    add_meta_box( 'takeover-details', esc_html__( 'Takeover Details', '' ), array( $this, 'hbi_ad_manager_takeover_meta_box' ), 'takeover' );

    if( class_exists( 'FLThemeBuilderLoader' ) ) {
      add_meta_box('takeover-locations', esc_html__('Takeover Location', ''), array($this, 'hbi_ad_manager_takeover_location_meta_box'), 'takeover');
    }
  }
    
  function hbi_ad_manager_meta_box( $post ) {
    wp_nonce_field( basename( __FILE__ ), 'ad_unit_details' );
    require_once plugin_dir_path( dirname( __FILE__ ) ) . 'admin/partials/hbi-ad-manager-metabox.php';
  }
    
  function hbi_ad_manager_save_meta_boxes( $post_id ) {
    // Let's see if the nonce is set before we do anything too foolish
    if ( ! isset( $_POST['ad_unit_details'] ) )
      return $post_id;

    $nonce = $_POST['ad_unit_details'];

    // Well .. Sure, it's set. But anyone could have done that, and it's probably not even the right value
    if ( ! wp_verify_nonce( $nonce, basename( __FILE__ ) ) )
      return $post_id;

    // Huh .. It's an old code, but it checks out. Probably just an autosave - Ain't nobody got time for autosaves
    if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE )
      return $post_id;

    // I'll be dang, that checks out too. One last question - Bro, do you even edit?
    if ( ! current_user_can( 'edit_post', $post_id ) )
      return $post_id;

    // If it doesn't equal 1, then make it zero
    $collapse_empty_div = ( 1 == $_POST['collapse_empty_div'] ) ? 1 : 0;
    $dfp_network_code = $_POST['dfp_network_code'];
    $dfp_ad_unit = $_POST['dfp_ad_unit'];
    $tag_id = $_POST['tag_id'];
    $admap_to_use = ( isset( $_POST['admap_to_use'] ) ) ? absint( $_POST['admap_to_use'] ) : '';
    $logical_operator = ( 'AND' === $_POST['logical-operator'] ) ? 'AND' : 'OR';

    // Sanitize the ad width, but then only allow numbers. Not sure if the two-step is needed, but I'd rather be safe than sorry
    $ad_width = sanitize_text_field( $_POST['ad_width']);
    $ad_width = preg_replace( "/[^0-9]/", "", $ad_width );

    // Sanitize the ad height, but then only allow numbers. Not sure if the two-step is needed, but I'd rather be safe than sorry
    $ad_height = sanitize_text_field( $_POST['ad_height']);
    $ad_height = preg_replace( "/[^0-9]/", "", $ad_height );

    // Update the post meta. If it doesn't exist, update is smart enough to create it, and there's never a real need to delete it short of deleting the entire tag
    update_post_meta( $post_id, 'dfp_ad_unit', $dfp_ad_unit );
    update_post_meta( $post_id, 'ad_width', $ad_width );
    update_post_meta( $post_id, 'ad_height', $ad_height );
    update_post_meta( $post_id, 'collapse_empty_div', $collapse_empty_div );
    update_post_meta( $post_id, 'dfp_network_code', $dfp_network_code );
    update_post_meta( $post_id, 'admap_to_use', $admap_to_use );
    update_post_meta( $post_id, 'operator', $logical_operator );
    update_post_meta( $post_id, 'tag_id', $tag_id );
    update_post_meta( $post_id, 'targeting_position', $_POST['targeting_position'] );

    // Loop through the conditionals and save them
    $new_conditionals = array();
    $unsafe_conditionals = ( isset( $_POST['ad_unit_conditionals'] ) ) ?  $_POST['ad_unit_conditionals'] : array();

    foreach( $unsafe_conditionals as $index => $unsafe_conditional ) {
      $index = (int)$index;
      $arugment = ( isset( $_POST['ad_unit_arguments'][$index] ) ) ? $_POST['ad_unit_arguments'][$index] : '';

      $conditional = array(
        'function' => sanitize_key( $unsafe_conditional ),
        'arguments' => array( $arugment )
      );

      if( !empty( $conditional['function'] ) )
        $new_conditionals[] = $conditional;
    }

    update_post_meta( $post_id, 'conditionals', $new_conditionals );
  }

  // A callback function to add a custom field to our "presenters" taxonomy
  function display_admap_custom_fields($tag) {
    // Check for existing taxonomy meta for the term you're editing
    $t_id = $tag->term_id; // Get the ID of the term you're editing
    $term_meta = get_option( "taxonomy_term_$t_id" ); // Do the check
    $admap_sizes_count = ( 0 == absint( count( $term_meta['admap_sizes'] ) ) ) ? 2 : count( $term_meta['admap_sizes'] );
    ?>
    </table>
    <p>
      <label>How many resolution sizes do you need?</label>
      <select id="admap_size" name="admap_size">
        <option <?php selected( $admap_sizes_count, 2 ); ?> value="2">2</option>
        <option <?php selected( $admap_sizes_count, 3 ); ?> value="3">3</option>
        <option <?php selected( $admap_sizes_count, 4 ); ?> value="4">4</option>
        <option <?php selected( $admap_sizes_count, 5 ); ?> value="5">5</option>
        <option <?php selected( $admap_sizes_count, 6 ); ?> value="6">6</option>
        <option <?php selected( $admap_sizes_count, 7 ); ?> value="7">7</option>
        <option <?php selected( $admap_sizes_count, 8 ); ?> value="8">8</option>
        <option <?php selected( $admap_sizes_count, 9 ); ?> value="9">9</option>
        <option <?php selected( $admap_sizes_count, 10 ); ?> value="10">10</option>
      </select>
    </p>
    <template id="admap_row">
      <tr class="form-field">
        <td><input class="browser_width" type="text" name="" value="0" /></td>
        <td><input class="browser_height" type="text" name="" value="0" /></td>
        <td><input class="ad_width" type="text" name="" value="0" /></td>
        <td><input class="ad_height" type="text" name="" value="0" /></td>
      </tr>
    </template>
    <table id="admap_table">
      <thead>
        <tr>
          <th>Browser Width</th>
          <th>Browser Height</th>
          <th>Ad Width</th>
          <th>Ad Height</th>
        </tr>
      </thead>
      <tbody>
        <?php
        if( $term_meta['admap_sizes'] ) : ?>
          <?php foreach( $term_meta['admap_sizes'] as $admap_id => $admap_size ) : ?>
          <tr class="form-field">
            <td><input class="browser_width" type="text" name="term_meta[admap_sizes][<?php echo $admap_id; ?>][browser_width]" value="<?php echo $admap_size['browser_width']; ?>" /></td>
            <td><input class="browser_height" type="text" name="term_meta[admap_sizes][<?php echo $admap_id; ?>][browser_height]" value="<?php echo $admap_size['browser_height']; ?>" /></td>
            <td><input class="ad_width" type="text" name="term_meta[admap_sizes][<?php echo $admap_id; ?>][ad_width]" value="<?php echo $admap_size['ad_width']; ?>" /></td>
            <td><input class="ad_height" type="text" name="term_meta[admap_sizes][<?php echo $admap_id; ?>][ad_height]" value="<?php echo $admap_size['ad_height']; ?>" /></td>
          </tr>
          <?php endforeach;
        else: ?>
          <tr class="form-field">
            <td><input class="browser_width" type="text" name="term_meta[admap_sizes][0][browser_width]" value="0" /></td>
            <td><input class="browser_height" type="text" name="term_meta[admap_sizes][0][browser_height]" value="0" /></td>
            <td><input class="ad_width" type="text" name="term_meta[admap_sizes][0][ad_width]" value="0" /></td>
            <td><input class="ad_height" type="text" name="term_meta[admap_sizes][0][ad_height]" value="0" /></td>
          </tr>
          <tr class="form-field">
            <td><input class="browser_width" type="text" name="term_meta[admap_sizes][1][browser_width]" value="0" /></td>
            <td><input class="browser_height" type="text" name="term_meta[admap_sizes][1][browser_height]" value="0" /></td>
            <td><input class="ad_width" type="text" name="term_meta[admap_sizes][1][ad_width]" value="0" /></td>
            <td><input class="ad_height" type="text" name="term_meta[admap_sizes][1][ad_height]" value="0" /></td>
          </tr>
        <?php endif; ?>
      </tbody>
      <?php
  }
    
  // A callback function to save our extra taxonomy field(s)
  function save_admap_custom_fields( $term_id ) {
    if ( isset( $_POST['term_meta'] ) ) {
      $t_id = $term_id;
      $term_meta = get_option( "taxonomy_term_$t_id" );
      $cat_keys = array_keys( $_POST['term_meta'] );
        foreach ( $cat_keys as $key ){
        if ( isset( $_POST['term_meta'][$key] ) ){
          $term_meta[$key] = $_POST['term_meta'][$key];
        }
      }
      //save the option array
      update_option( "taxonomy_term_$t_id", $term_meta );
    }
  }
    
  function set_custom_ad_unit_columns($columns) {
    unset($columns['date']);
    $columns['ad_size'] = __( 'Ad Size' );
    $columns['priority'] = __( 'Priority' );
    $columns['conditionals'] = __( 'Conditionals' );
    $columns['targeting'] = __( 'Targeting' );

    return $columns;
  }
    
  function custom_ad_unit_column( $column, $post_id ) {
    switch( $column ) {
      case 'ad_size':
        echo esc_attr( get_post_meta( $post_id, 'ad_width', TRUE ) ) . 'x' . esc_attr( get_post_meta( $post_id, 'ad_height', TRUE ) );
        break;
      case 'priority':
        $priority = get_post_meta( $post_id, 'logical-priority', TRUE );
        echo ( !empty( $priority ) ) ? esc_attr( $priority ) : 10;
        break;
      case 'conditionals':
        $conditionals = get_post_meta( $post_id, 'conditionals', TRUE );
        if( !empty( $conditionals ) ) :
          foreach( $conditionals as $conditional ) :
            echo "<strong>" . str_replace( '_', ' ', $conditional['function'] ) . "</strong>";
            echo " ";
            echo ( isset( $conditional['arguments'][0] ) ) ? $conditional['arguments'][0] : '';
            echo '<br />';
          endforeach;
        endif;
          break;
      case 'targeting':
        echo esc_attr( get_post_meta( $post_id, 'targeting', TRUE ) );
        break;
    }
  }

  function hbi_ad_manager_takeover_meta_box( $post ) {
    wp_nonce_field( basename( __FILE__ ), 'takeover_details' );
    require_once plugin_dir_path( dirname( __FILE__ ) ) . 'admin/partials/takeover-metabox.php';
  }

  /**
   * Save the takeover metabox details when a user saves the post
   *
   * @since 3.0.0
   */
  function hbi_ad_manager_save_takeover_metabox( $post_id ) {
    if ( ! isset( $_POST['takeover_details'] ) )
      return $post_id;

    $nonce = $_POST['takeover_details'];

    // Well .. Sure, it's set. But anyone could have done that, and it's probably not even the right value
    if ( ! wp_verify_nonce( $nonce, basename( __FILE__ ) ) )
      return $post_id;

    // Huh .. It's an old code, but it checks out. Probably just an autosave - Ain't nobody got time for autosaves
    if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE )
      return $post_id;

    // I'll be dang, that checks out too. One last question - Bro, do you even edit?
    if ( ! current_user_can( 'edit_post', $post_id ) )
      return $post_id;

    if( !empty( $_POST['takeover_start'] ) ) {
      update_post_meta( $post_id, 'takeover_start', sanitize_text_field( $_POST['takeover_start'] ) );
    }

    if( !empty( $_POST['takeover_end'] ) ) {
      update_post_meta( $post_id, 'takeover_end', sanitize_text_field( $_POST['takeover_end'] ) );
    }

    if( !empty( $_POST['takeover_background'] ) && 0 == validate_file( str_replace( get_site_url() . "/", '', $_POST['takeover_background'] ) ) ) {
      update_post_meta( $post_id, 'takeover_background', $_POST['takeover_background'] );
    }

    if( !empty( $_POST['takeover_ad_targeting'] ) ) {
      update_post_meta( $post_id, 'takeover_ad_targeting', absint( $_POST['takeover_ad_targeting'] ) );
    }

    

  }

  public function hbi_ad_manager_takeover_location_meta_box( $post ) {

    $bb_location_rules = new FLThemeBuilderRulesLocation();
    global $post;

    $type     = 'takeover';
    $order    = get_post_meta( $post->ID, '_fl_theme_layout_order', true );
    $hook     = get_post_meta( $post->ID, '_fl_theme_layout_hook', true );
    $settings = FLThemeBuilderLayoutData::get_settings( $post->ID );
    $hooks    = FLThemeBuilderLayoutData::get_part_hooks();

    include FL_THEME_BUILDER_DIR . 'includes/layout-admin-edit-settings.php';

    $bb_location_rules::render_admin_edit_settings();
    FLThemeBuilderRulesUser::render_admin_edit_settings();
  }
}