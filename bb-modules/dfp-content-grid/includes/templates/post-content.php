<div class="pp-content-grid-content pp-post-content">
    <?php
    if ( $settings->content_type == 'excerpt' ) :
        the_excerpt();
    endif;
    if ( $settings->content_type == 'content' ) :
        $more = '...';
        echo wp_trim_words( get_the_content(), $settings->content_length, apply_filters( 'pp_cg_content_limit_more', $more ) );
    endif;
    if ( $settings->content_type == 'full' ) :
        the_content();
    endif;
    ?>
</div>
