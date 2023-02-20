<?
    require_once 'config.php';
    require_once 'includes/seo.php';

    /* default constants */
    if (!defined('IS_enable_subdomain')) {
        define('IS_enable_subdomain', false);
    };
    if (!defined('IS_enable_catalog')) {
        define('IS_enable_catalog', false);
    };
    if (!defined('IS_catalog_slug')) {
        if (IS_enable_catalog) {
            define('IS_catalog_slug', 'catalog');
        } else {
            define('IS_catalog_slug', '');
        };
    };
    if ((!defined('IS_use_catalog_page')) || (IS_catalog_slug == '')) {
        define('IS_use_catalog_page', false);
    };


    /*********************************** */
    /* SVG */
    add_filter( 'upload_mimes', 'svg_upload_allow' );

    /* Добавляет SVG в список разрешенных для загрузки файлов. */
    function svg_upload_allow( $mimes ) {
        $mimes['svg']  = 'image/svg+xml';

        return $mimes;
    }

    add_filter( 'wp_check_filetype_and_ext', 'fix_svg_mime_type', 10, 5 );

    /* Исправление MIME типа для SVG файлов. */
    function fix_svg_mime_type( $data, $file, $filename, $mimes, $real_mime = '' ){

        /*  WP 5.1 + */
        if( version_compare( $GLOBALS['wp_version'], '5.1.0', '>=' ) ){
            $dosvg = in_array( $real_mime, [ 'image/svg', 'image/svg+xml' ] );
        }
        else {
            $dosvg = ( '.svg' === strtolower( substr( $filename, -4 ) ) );
        }

        // mime тип был обнулен, поправим его
        // а также проверим право пользователя
        if( $dosvg ){

            // разрешим
            if( current_user_can('manage_options') ){

                $data['ext']  = 'svg';
                $data['type'] = 'image/svg+xml';
            }
            // запретим
            else {
                $data['ext']  = false;
                $data['type'] = false;
            }

        }

        return $data;
    }

    add_filter( 'wp_prepare_attachment_for_js', 'show_svg_in_media_library' );

    /* Формирует данные для отображения SVG как изображения в медиабиблиотеке. */
    function show_svg_in_media_library( $response ) {

        if ( $response['mime'] === 'image/svg+xml' ) {

            // С выводом названия файла
            $response['image'] = [
                'src' => $response['url'],
            ];
        }

        return $response;
    }

    /************************ */
    /* ПОДДОМЕНЫ / SUBDAMAINS */
    if (IS_enable_subdomain) {

        /****************************************** */
        /* Содадим свои типы записей для поддоменов */
        add_action( 'init', 'subdomain_register_post_type_init' );
        function subdomain_register_post_type_init() {
            /* Города */
            $labels = array(
                'name' => 'Поддомены',
                'singular_name' => 'Поддомен', // админ панель Добавить->Функцию
                'add_new' => 'Добавить',
                'add_new_item' => 'Добавить новый поддомен', // заголовок тега <title>
                'edit_item' => 'Редактировать',
                'new_item' => 'Новый',
                'all_items' => 'Все',
                'view_item' => 'Просмотр на сайте',
                'search_items' => 'Искать',
                'not_found' =>  'не найдено.',
                'not_found_in_trash' => 'В корзине пусто.',
                'menu_name' => 'Поддомены' // ссылка в меню в админке
            );
            $args = array(
                'labels' => $labels,
                'public' => true,
                'show_ui' => true, // показывать интерфейс в админке
                'has_archive' => false,
                'menu_position' => 20, // порядок в меню
                'supports' => array( 'title')
            );
            register_post_type('domains', $args);
        }

        /*************************************************** */
        /* Помощь в панели управления (замена на поддоменах) */
        add_action('admin_head', 'subdomain_post_type_help_tab');
        function subdomain_post_type_help_tab() {
            $screen = get_current_screen();
            // Прекращаем выполнение функции, если находимся на страницах других типов постов
            /*
            if (!in_array($screen->post_type , array('Domains', 'page', 'post'))) {
                return;
            }
            */
            $arDomain = get_avalable_domain();

            $content = '';
            if (is_array($arDomain['www'])) {
                $content = '<table border = "1" cellpadding="5">';
                $content .= '<tr><th>Символьный код</th><th>Пример замены</th></tr>';
                foreach ($arDomain['www'] as $key=>$value) {
                    $content .= '<tr><td>%domain_'.$key.'%</td><td>'.$value.'</td></tr>';
                };
                $content .= '</table>';
            };

            // Массив параметров для первой вкладки
            $args = array(
                'id'      => 'Domain_tab_1',
                'title'   => 'Символьные коды',
                'content' => '<h3>Символьные коды, которые можно применить в тексте и заголовках</h3>'.$content
            );

            // Добавляем вкладку
            $screen->add_help_tab( $args );
        }

        /**************************************************************** */
        /* Функция подмены переменных на странице в зависимости от домена */
        function set_domain_values($text) {
            $domain = get_avalable_domain();
            $cursubdomen = 'www';
            list($curdomen, $port) = explode ( ':' , $_SERVER['HTTP_HOST']);
            $domenparts = explode('.', $curdomen);
            if (is_array($domain[$domenparts[0]])) {
                $cursubdomen = $domenparts[0];
            };

            if (is_array($domain[$cursubdomen])) {
                foreach ($domain[$cursubdomen] as $key=>$value) {
                    $text = str_replace('%domain_'.$key.'%', $value, $text);
                }
            };
            /* уберем в ссылках домен */
            $text = str_replace('"http://'.$curdomen, '"', $text);
            $text = str_replace('"https://'.$curdomen, '"', $text);
            $text = str_replace("'http://".$curdomen, "'", $text);
            $text = str_replace("'https://".$curdomen, "'", $text);

            $curdomen2 = 'www.'.$domenparts[count($domenparts) - 2].'.'.$domenparts[count($domenparts) - 1];
            $text = str_replace('"http://'.$curdomen2, '"', $text);
            $text = str_replace('"https://'.$curdomen2, '"', $text);
            $text = str_replace("'http://".$curdomen2, "'", $text);
            $text = str_replace("'https://".$curdomen2, "'", $text);

            $text = str_replace('"http://', '"//', $text);
            $text = str_replace('"https://', '"//', $text);
            $text = str_replace("'http://", "'//", $text);
            $text = str_replace("'https://", "'//", $text);

            return $text;
        }

        /***************************************************************** */
        /* редирект на поддомен www, если в адресе открыли не существующий */
        function redirect_right() {
            $domain = get_avalable_domain();
            $cursubdomen = '';
            list($curdomen, $port) = explode ( ':' , $_SERVER['HTTP_HOST']);
            $domenparts = explode('.', $curdomen);
            if (is_array($domain[$domenparts[0]])) {
                $cursubdomen = $domenparts[0];
            };
            if ($cursubdomen == '') {
                $curdomen = 'www.'.$domenparts[count($domenparts) - 2].'.'.$domenparts[count($domenparts) - 1];
                $isHttps = !empty($_SERVER['HTTPS']) && 'off' !== strtolower($_SERVER['HTTPS']);
                $http = 'http';
                if ($isHttps) {
                    $http .= 's';
                };
                $curdomen = $http.'://'.$curdomen.$_SERVER['REQUEST_URI'];
                header('Location: '.$curdomen);
                die();
            };
        }

        /************************************************************* */
        /* Функция вернет все доспутные поддомены и все данные к ним */
        function get_avalable_domain() {
            $result = array();
            $filter['post_type'] = 'domains';
            $filter['numberposts'] = 999;
            $filter['post_status'] = 'publish';
            $Domains = get_posts($filter);
            $arDomains = (array) $Domains;
            foreach ($Domains as $Domain) {
                $arDomain = (array) $Domain;
                $arDomainRight = array();
                $arDomainRight['ID'] = $arDomain['ID'];
                $arDomainRight['title'] = $arDomain['post_title'];
                $fields = get_fields($arDomain['ID']);
                if (is_array($fields)) {
                    foreach ($fields as $fkey=>$fvalue) {
                        $arDomainRight[$fkey] = $fvalue;
                    };
                    $result[$arDomain['post_title']] = $arDomainRight;
                };
            };
            return $result;
        }


        function subdomain_buffer_start() {
            redirect_right();
            ob_start("set_domain_values");
        }

        function subdomain_buffer_end() {
            ob_end_flush();
        }

        add_action('wp_head', 'subdomain_buffer_start');
        add_action('wp_footer', 'subdomain_buffer_end');
    }



    /************************ */
    /* КАТАЛОГ / CATALOG */
    if (IS_enable_catalog) {

        /****************************************** */
        /* Содадим свои типы записей для каталога */
        add_action('init', 'catalog_register_post_type_init');
        function catalog_register_post_type_init()
        {
            /* товары */
            $labels = array(
                'name' => 'Товарный каталог',
                'singular_name' => 'товар', // админ панель Добавить->Функцию
                'add_new' => 'Добавить',
                'add_new_item' => 'Добавить новый товар/раздел', // заголовок тега <title>
                'edit_item' => 'Редактировать',
                'new_item' => 'Новый',
                'all_items' => 'Все',
                'view_item' => 'Просмотр на сайте',
                'search_items' => 'Искать',
                'not_found' =>  'не найдено.',
                'not_found_in_trash' => 'В корзине пусто.',
                'menu_name' => 'Товарный каталог' // ссылка в меню в админке
            );
            $args = array(
                'labels' => $labels,
                'public' => true,
                'show_ui' => true, // показывать интерфейс в админке
                'has_archive' => IS_catalog_slug,
                'menu_position' => 20, // порядок в меню
                'hierarchical' => true,
                'publicly_queryable' => true,
                'supports' => array( 'title', 'editor', 'thumbnail', 'excerpt', 'custom-fields', 'page-attributes', 'comments'),
                'rewrite' => array(
                        'slug' => IS_catalog_slug,
                        'with_front' => false,
                        'pages' => true
                        )
            );
            register_post_type(IS_catalog_slug, $args);
            add_theme_support('post-thumbnails', array(IS_catalog_slug));
        }

        /********************************************** */
        /* Используется ли страница для показа каталога */
        if (IS_use_catalog_page) {
            /********************************************* */
            function is_catalog_page() {
                $current_slug = trim($_SERVER['REQUEST_URI'], '/');
                if ($current_slug == IS_catalog_slug) {
                    $page = get_page_by_path(IS_catalog_slug, 'ARRAY_A', 'page');
                    $GLOBALS['SHOWTHIS'] = $page;
                };
            };
            is_catalog_page();
        }
    }


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
    add_shortcode( 'showChildren' , 'showChildren' );
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

    /* Добавим менюшки */
    add_action( 'init', 'menu_type_init' );
    function menu_type_init() {
        register_nav_menus( array(
            'header_menu' => 'Меню в шапке',
            'footer_menu' => 'Меню в подвале',
            'footer_links' => 'Полезные ссылки',
        ) );
    }

?>