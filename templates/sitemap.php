<?php
if (is_array($pages)) {
    $result = '';
    $result .= '<ul class="sitemap">';
    foreach ($pages as $page) {
        $curclass = '';
        $link = get_permalink($page);
        $title = $page->post_title;
        $result .= '<li>';
        $result .= '<a href="'.$link.'" class="'.$curclass.'">'.$title.'</a>';
        $result .= showChildren(['id'=>$page->ID,'template'=>'sitemap']);
        $result .= '</li>';
    }
    $result .= '</ul>';
    echo $result;
}

?>
