<?php
if (is_array($pages)) {
    $result = '';
    foreach ($pages as $page) {
        $link = get_permalink($page);
        $title = $page->post_title;
        $text = $page->post_content;
        $result .= '<div>';
        $result .= '<a href="'.$link.'">';
        $result .= '<div class="title">'.$title.'</div>';
        $result .= '</a>';
        $result .= '<div class="text">'.$text.'</div>';
        $result .= '</div>';
    };
    echo $result;
};
?>
