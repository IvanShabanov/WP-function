<?php

?>
<!DOCTYPE html>
<html>
<head>
    <meta http-equiv="Content-type" content="text/html; charset=<?php bloginfo('charset'); ?>">


    <?php
        wp_enqueue_style( 'font_opnesans', "//fonts.googleapis.com/css?family=Open+Sans" );
        wp_enqueue_style( 'fancybox', "//cdn.jsdelivr.net/gh/fancyapps/fancybox@3.5.7/dist/jquery.fancybox.min.css" );
        wp_enqueue_style( 'style', get_bloginfo('template_url')."/css/style.css");

        $ver = date('Ymd');
        wp_enqueue_script('jquery', '//yastatic.net/jquery/3.3.1/jquery.min.js', array(), $ver, true);
        wp_enqueue_script('fancybox', '//cdn.jsdelivr.net/gh/fancyapps/fancybox@3.5.7/dist/jquery.fancybox.min.js', array('jquery'), $ver, true);
        wp_enqueue_script('inputmask', '//cdnjs.cloudflare.com/ajax/libs/jquery.mask/1.14.15/jquery.mask.min.js', array('jquery'), $ver, true);
        wp_enqueue_script('script', get_bloginfo('template_url')."/js/script.js", array('jquery', 'fancybox', 'inputmask'), $ver, true);
    ?>
    <?php wp_head(); ?>
</head>
<body <?php body_class(); ?>>
    <header>
        <div class="mainmenu">
                <?php wp_nav_menu( array('menu' => 'header_menu' ) ); ?>
        </div>
    </header>
	<div class="wrap">
