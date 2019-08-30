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
            } else {
                $filter['numberposts'] = 999;
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
            
            $pages = get_posts($filter);
            ob_start();
            include('templates/'.$template.'.php');
            $result = ob_get_contents();
            ob_end_clean();
        };
        return $result;
    };

    add_shortcode( 'showChildren' , 'showChildren' );

?>
