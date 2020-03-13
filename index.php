<?php get_header(); ?>
<div class="content">
<?php
    if (isset( $GLOBALS['SHOWTHIS'])){
        echo apply_filters('the_content', $GLOBALS['SHOWTHIS']['post_content']);
    } else if (strpos($current_slug, 'catalog') !== false) {
        the_post();
        the_content();
        echo showChildren( array('template'=>"catalog", 'post_type'=>'catalog', 'id'=>$post->ID ) );
    } else {
        if (have_posts()) {
            the_post();
            the_content();
        };
    };


?>
</div>

<?php get_footer(); ?>