<?php

class DFP_Ad_Unit extends WP_Widget {

    /**
     * Register widget with WordPress.
     */
    function __construct() {
        parent::__construct(
            'dfp_ad_unit', // Base ID
            __('DFP Ad Unit'), // Name
            array( 'description' => __( 'Display a DFP Ad' ), ) // Args
        );
    }

    /**
     * Front-end display of widget.
     *
     * @see WP_Widget::widget()
     *
     * @param array $args     Widget arguments.
     * @param array $instance Saved values from database.
     */
    public function widget( $args, $instance ) {
        
        $this->options = get_option('hbi_ad_manager_settings');
        
        $ad_unit = get_page_by_title( $instance['ad_zone'], 'OBJECT', 'ad_unit' );
        
        if( 1 === $this->options['async_rendering'] ) 
            $ad_code = HBI_Ad_Manager_Public::generate_asynchronous_ad_code_for_display( $ad_unit );
        else
            $ad_code = HBI_Ad_Manager_Public::generate_synchronous_ad_code_for_display( $ad_unit );
        
        if( $ad_code )
            echo "<div class='widget dfp_ad_unit_widget'>$ad_code</div>";
        
    }

    /**
     * Back-end widget form.
     *
     * @see WP_Widget::form()
     *
     * @param array $instance Previously saved values from database.
     */
    public function form( $instance ) {
        $ad_unit_args = array(
            'posts_per_page' => '-1',
            'nopaging' => true,
            'post_type' => 'ad_unit',
            'orderby' => 'post_title',
            'order' => 'ASC'
        );
        
        $ad_units = get_posts( $ad_unit_args );
        
        if( empty( $ad_units ) ) {
            echo "Please create ad units before trying to select an ad unit to display.";
            return;
        }
        
        $title = ( isset( $instance['title'] ) ) ? $instance['title'] : '';
        $ad_zone = ( isset( $instance['ad_zone'] ) ) ? $instance['ad_zone'] : '';
        ?>
        <p>
            <label for="<?php echo $this->get_field_id( 'title' ); ?>"><?php _e( 'Title:' ); ?></label> 
            <input class="widefat" id="<?php echo $this->get_field_id( 'title' ); ?>" name="<?php echo $this->get_field_name( 'title' ); ?>" type="text" value="<?php echo esc_attr( $title ); ?>">
        </p>
        <p>
            <label for="<?php echo $this->get_field_id( 'title' ); ?>"><?php _e( 'Select an Ad Unit:' ); ?></label>
            <select id="<?php echo $this->get_field_id( 'title' ); ?>" name="<?php echo $this->get_field_name( 'ad_zone' ); ?>">
                <option value=""></option>
                <?php foreach( $ad_units as $ad_unit ) : ?>
                <option <?php selected( $ad_zone, $ad_unit->post_title ); ?> value="<?php echo $ad_unit->post_title; ?>"><?php echo $ad_unit->post_title; ?></option>
                <?php endforeach; ?>
            </select> 
        </p>
        <?php 
    }

    /**
     * Sanitize widget form values as they are saved.
     *
     * @see WP_Widget::update()
     *
     * @param array $new_instance Values just sent to be saved.
     * @param array $old_instance Previously saved values from database.
     *
     * @return array Updated safe values to be saved.
     */
    public function update( $new_instance, $old_instance ) {
        $instance = array();
        $instance['title'] = ( ! empty( $new_instance['title'] ) ) ? strip_tags( $new_instance['title'] ) : '';
        $instance['ad_zone'] = ( absint( $new_instance['ad_zone'] ) == $new_instance['ad_zone'] ) ? $new_instance['ad_zone'] : null;

        return $instance;
    }

}