<?php

/**
 * @class FLAudioModule
 */
class FLAdUnitModule extends FLBuilderModule {

  /**
   * @method __construct
   */
  public function __construct() {
    parent::__construct(array(
      'name'          	=> __( 'Ad Unit', 'fl-builder' ),
      'description'   	=> __( 'Render an ad unit.', 'fl-builder' ),
      'category'      	=> __( 'Advertising Modules', 'fl-builder' ),
    ));
  }

  /**
   * @return array
   */
  public static function get_ad_units() {
    $ad_units = HBI_Ad_Manager_Public::get_ad_units();
    $filtered_ad_units = array();

    foreach( $ad_units as $ad_unit ) {
      $ad_unit_id = $ad_unit['post_id'];
      $filtered_ad_units[ $ad_unit_id ] = __( $ad_unit['post_title'], 'fl-builder' );
    }

    return $filtered_ad_units;
  }
}

/**
 * Register the module and its form settings.
 */
FLBuilder::register_module('FLAdUnitModule', array(
  'general'       => array(
    'title'         => __( 'General', 'fl-builder' ),
    'sections'      => array(
      'general'       => array(
        'title'         => '',
        'fields'        => array(
          'ad_unit'       => array(
            'type'          => 'select',
            'label'         => __( 'Ad Unit', 'fl-builder' ),
            'default'       => '',
            'options'       => FLAdUnitModule::get_ad_units()
          ),
          'alignment'       => array(
            'type'          => 'select',
            'label'         => __( 'Alignment', 'fl-builder' ),
            'default'       => '',
            'options'       => array(
              'left'        => __( 'Left', 'fl-builder' ),
              'center'      => __( 'Center', 'fl-builder' ),
              'right'       => __( 'Right', 'fl-builder' ),
            ),
          ),
        ),
      ),
    ),
  ),
));