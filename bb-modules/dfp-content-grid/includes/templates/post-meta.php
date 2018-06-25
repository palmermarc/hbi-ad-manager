<div class="pp-content-category-list pp-post-meta">
    <?php $i = 1;
    foreach ($terms_list as $term):
        ?>
    <?php if( $i == count($terms_list) ) { ?>
        <a href="<?php echo get_term_link($term); ?>"><?php echo $term->name; ?></a>
    <?php } else { ?>
        <a href="<?php echo get_term_link($term); ?>"><?php echo $term->name; ?></a> /
    <?php } ?>
    <?php $i++; endforeach; ?>
</div>
