<?
   /****** Показать потомков страницы ************
    * $attr=array(
    *   'id' - id родителя
    *   'template' - шаблон файл в папке templates файл .php
    *                   в файле foreach для массива pages
    *   'numberposts' - кол-во постов/страниц, по умолчанию 999
    *   'orderby' - поле сортировки, по умолчанию menu_order
    *   'iscat' - если родитель это категория по ставим "Y", тогда найдет все записи в категории
    *   'post_type' - тип постов которые нам нужны, по умолчанию page
    *
    *   Использовать в страницах так
    *   [showChildren id=1 template="sidemenu"][/showChildren]
    */
    function showChildren ( $attr ) {
        $result = '';
        if (is_numeric($attr['id'])) {
            $class = $attr['class'];
            $template = $attr['template'];
            if ($template == '') {
                $template = 'pages';
            };
            $filter = array(
                        'post_status' => 'publish',
                    );
            if (is_numeric($attr['numberposts'])) {
                $filter['numberposts'] = $attr['numberposts'];
            };

            if ($attr['orderby']!= '') {
                $filter['orderby'] = $attr['orderby'];
            } else {
                $filter['orderby'] = 'menu_order';
            };
            if ($attr['order']!= '') {
                $filter['order'] = 'DESC';
            } else {
                $filter['order'] = 'ASC';
            };
            if ($attr['iscat'] == 'Y') {
                $filter['category'] = $attr['id'];
              } else {
                $filter['post_parent'] = $attr['id'];
            };
            if ($attr['post_type'] != '') {
                $filter['post_type'] = $attr['post_type'];
            } else {
                $filter['post_type'] = 'page';
            };

            if (is_numeric($attr['posts_per_page'])) {
                $total_items = count(get_posts($filter));
                $max_pages = max(1, ceil($total_items / $attr['posts_per_page']));
                $filter['posts_per_page'] = $attr['posts_per_page'];
                if (is_front_page()) {
                    $currentPage = (get_query_var('page')) ? get_query_var('page') : 1;
                } else {
                    $currentPage = (get_query_var('paged')) ? get_query_var('paged') : 1;
                }
                if (is_numeric($currentPage)) {
                    $filter['offset'] = ($currentPage -1) * $filter['posts_per_page'];
                }
            };

            $pages = get_posts($filter);
            ob_start();
            include('templates/'.$template.'.php');
            $result = ob_get_contents();
            ob_end_clean();
        };
        return $result;
    };

    add_shortcode( 'showChildren' , 'showChildren' );


    function show_pagination($max_pages = null)
    {
        global $wp_query;
        if (!is_numeric($max_pages)) {
            $max_pages =  $wp_query->max_num_pages;
        }


        if (is_front_page()) {
            $currentPage = (get_query_var('page')) ? get_query_var('page') : 1;
        } else {
            $currentPage = (get_query_var('paged')) ? get_query_var('paged') : 1;
        }

        $pagination = paginate_links([
                                         'base'      => str_replace(999999999, '%#%', get_pagenum_link(999999999)),
                                         'format'    => '',
                                         'current'   => max(1, $currentPage),
                                         'total'     => $max_pages,
                                         'type'      => 'list',
                                         'prev_text' => '«',
                                         'next_text' => '»',
                                     ]);

        return str_replace('page-numbers', 'pagination', $pagination);
    }
?>
