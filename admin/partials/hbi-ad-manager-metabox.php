<?php 
$admaps = get_terms( 'admap', array( 'hide_empty' => 0 ) ); 
$ad_unit_conditionals = get_post_meta( $post->ID, 'conditionals', TRUE );
$operator_class = ( empty( $ad_unit_conditionals ) ) ? 'hide' : 'show';
$hbi_ad_manager_settings = get_option( 'hbi_ad_manager_settings' );
$active_conditionals = $hbi_ad_manager_settings['active_conditionals'];
$priority = get_post_meta( $post->ID, 'logical_priority', TRUE );
$priority = ( !empty( $priority ) ) ? $priority : 10;
?>
<table class="form-table">
    <tbody>
        <tr>
            <th><label for="dfp_network_code">DFP Network Code</label></th>
            <td>
                <input type="text" name="dfp_network_code" value="<?php echo esc_attr( get_post_meta( $post->ID, 'dfp_network_code', TRUE ) ); ?>" />
                <p class="description"><a href="https://support.google.com/dfp_premium/answer/1115782?hl=en" target="_blank">Click here for help finding your DFP Network Code.</a></p>
            </td>
        </tr>
        <tr>
            <th scope="row"><label for="tag_id">Tag ID</label></th>
            <td>
                <input type="widefat" type="text" name="tag_id" value="<?php echo esc_attr( get_post_meta( $post->ID, 'tag_id', TRUE ) ); ?>" /> <br/>
                <span class="description">The Tag ID simply replaces the ID that is called when displaying the ad. If this field is left blank, the plugin will fall back to the post slug.</span>
            </td>
        </tr>
        <tr>
            <th scope="row"><label for="dfp_ad_unit">DFP Ad Unit</label></th>
            <td>
                <input class="widefat" type="text" name="dfp_ad_unit" value="<?php echo esc_attr( get_post_meta( $post->ID, 'dfp_ad_unit', TRUE ) ); ?>" />
            </td>
        </tr>
        <tr>
            <th scope="row"><label for="ad_width">Ad Width</label></th>
            <td>
                <input style="width: 50px;" type="text" name="ad_width" value="<?php echo esc_attr( get_post_meta( $post->ID, 'ad_width', TRUE ) ); ?>"/> px
            </td>
        </tr>
        <tr>
            <th scope="row"><label for="ad_height">Ad Height</label></th>
            <td>
                <input style="width: 50px;" type="text" name="ad_height" value="<?php echo esc_attr( get_post_meta( $post->ID, 'ad_height', TRUE ) ); ?>"/> px
            </td>
        </tr>
        <tr>
            <th scope="row"><label for="position_in_feed">Position In Feed</label></th>
            <td>
                <input id="position_in_feed" style="" type="text" name="position_in_feed" value="<?php esc_attr( get_post_meta( $post->ID, 'position_in_feed', TRUE ) ); ?>" />
            </td>
        </tr>
        <tr>
            <th scope="row"><label for="targeting_position">Targeting Position</label></th>
            <td>
                <input id="targeting_position" style="" type="text" name="targeting_position" value="<?php echo esc_attr( get_post_meta( $post->ID, 'targeting_position', TRUE ) ); ?>" /><br />
                <span class="description">(Allows you to target pages using the pos setting in DFP, THESE MUST MATCH EXACTLY!):</span>
            </td>
        </tr>
        
                
        <tr>
            <th scope="row"><label for="collapse_empty_div">Collapse Div When Empty</label></th>
            <td>
                <input type="checkbox" name="collapse_empty_div" <?php checked( get_post_meta( $post->ID, 'collapse_empty_div', TRUE ), 1 ) ; ?> value="1"/>
            </td>
        </tr>
        <tr>
            <th scope="row"><label for="admap">Ad Mapping</label></th>
            <td>
                <?php if( !empty( $admaps ) ) : ?> 
                    <select name="admap_to_use">
                        <option value="0"></option>
                    <?php foreach( $admaps as $admap ) : ?>
                        <option <?php selected( $admap->term_id, get_post_meta( $post->ID, 'admap_to_use', TRUE ) ); ?> value="<?php echo $admap->term_id; ?>"><?php echo $admap->name; ?></option>
                    <?php endforeach; ?> 
                    </select>
                <?php endif; ?>
            </td>
        </tr>
    </tbody>
</table>

<hr />
<h3>Ad Unit Conditionals</h3>

<template id="ad-conditional-template">
    <div class="single-ad-unit-conditional">
        <p>
            <label for="ad-unit-conditionals">Use Conditional: </label>
            <select name="ad_unit_conditionals[]">
                <option value="">Select conditional</option>
                <?php foreach( $active_conditionals as $function => $use ) : 
                    if( $use ) echo "<option value='$function'>$function</option>";
                endforeach; ?>
            </select>
            <input type="text" name="ad_unit_arguments[]" value="" />
            <span class="remove_ad_conditional">Remove</span>
        </p>
    </div>
</template>

<div id="ad-conditionals-bin">
    <div id="logical-operator" class="<?php echo $operator_class; ?>">
        <?php if( !empty( $ad_unit_conditionals ) ) : ?>
            <?php foreach( $ad_unit_conditionals as $conditional ) : ?>
        <div class="single-ad-unit-conditional">
            <p>
                <label for="ad-unit-conditionals">Use Conditional: </label>
                <select name="ad_unit_conditionals[]">
                    <option value="">Select conditional</option>
                    <?php foreach( $active_conditionals as $function => $use ) : 
                        if( $use ) 
                            echo "<option " . selected( $function, $conditional['function'], false ). " value='$function'>$function</option>";
                    endforeach; ?>
                </select>
                <input type="text" name="ad_unit_arguments[]" value="<?php echo $conditional['arguments'][0]; ?>" />
                <span class="remove_ad_conditional">Remove</span>
            </p>
        </div>
            <?php endforeach; ?>
        <?php endif; ?>
        <p>
            <label>Logical Operator</label>
            <select name="logical-operator">
                <option value="OR">OR</option>
                <option value="AND">AND</option>
            </select>
        </p>
        <p>
            <label>Priority</label>
            <select name="logical-priority">
                <option <?php selected( $priority, 1 ); ?> value="1">1</option>
                <option <?php selected( $priority, 2 ); ?> value="2">2</option>
                <option <?php selected( $priority, 3 ); ?> value="3">3</option>
                <option <?php selected( $priority, 4 ); ?> value="4">4</option>
                <option <?php selected( $priority, 5 ); ?> value="5">5</option>
                <option <?php selected( $priority, 6 ); ?> value="6">6</option>
                <option <?php selected( $priority, 7 ); ?> value="7">7</option>
                <option <?php selected( $priority, 8 ); ?> value="8">8</option>
                <option <?php selected( $priority, 9 ); ?> value="9">9</option>
                <option <?php selected( $priority, 10 ); ?> value="10">10</option>
            </select>
            <br />
            <span class="description">When figuring out how to pull in priority, 1 is considered the highest priority and 10 is the lowest.</span>
        </p>
    </div>
</div>

<p id="add_another_conditional">Create a New Conditional</p>