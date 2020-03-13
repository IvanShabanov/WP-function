<?php
/**
 * Simple SEO class to create page metatags: title, description, robots, keywords, Open Graph.
 *
 * @author Kama
 * @version 1
 */
class Kama_SEO_Tags {

	function __construct(){}

	static function init(){

		// remove basic title call
		remove_action( 'wp_head', '_wp_render_title_tag', 1 );
		add_action( 'wp_head', [ __CLASS__, 'render_seo_tags' ], 1 );
	}

	static function render_seo_tags(){
		//remove_theme_support( 'title-tag' ); // не обязательно

		echo '<title>'. self::meta_title(' — ') .'</title>'."\n\n";

		echo self::meta_description();
		echo self::meta_keywords();
		echo self::meta_robots('cpage');

		echo self::og_meta(); // Open Graph, twitter данные
	}

	/**
	 * Open Graph, twitter data in `<head>`.
	 *
	 * See docs: http://ogp.me/
	 *
	 * @version 8
	 */
	static function og_meta(){

		$obj = get_queried_object();

		if( isset($obj->post_type) )   $post = $obj;
		elseif( isset($obj->term_id) ) $term = $obj;

		$is_post = isset( $post );
		$is_term = isset( $term );

		$title = self::meta_title( '–' );
		$desc  = preg_replace( '/^.+content="([^"]*)".*$/s', '$1', self::meta_description() );

		// Open Graph
		$els = [];
		$els['og:locale']      = '<meta property="og:locale" content="'     . get_locale()                           .'" />';
		$els['og:site_name']   = '<meta property="og:site_name" content="'  . esc_attr( get_bloginfo('name') )       .'" />';
		$els['og:title']       = '<meta property="og:title" content="'      . esc_attr( $title )                     .'" />';
		$els['og:description'] = '<meta property="og:description" content="'. esc_attr( $desc )                      .'" />';
		$els['og:type']        = '<meta property="og:type" content="'       .( is_singular() ? 'article' : 'object' ).'" />';

		if( $is_post ) $pageurl = get_permalink( $post );
		if( $is_term ) $pageurl = get_term_link( $term );
		if( isset($pageurl) )
			$els['og:url'] = '<meta property="og:url" content="'. esc_attr( $pageurl ) .'" />';

		/**
		 * Allow to disable `article:section` property.
		 *
		 * @param bool $is_on
		 */
		if( apply_filters( 'kama_og_meta_show_article_section', true ) ){

			if( is_singular() && $post_taxname =  get_object_taxonomies($post->post_type) ){

				$post_terms = get_the_terms( $post, reset($post_taxname) );
				if( $post_terms && $post_term = array_shift($post_terms) )
					$els['article:section'] = '<meta property="article:section" content="'. esc_attr( $post_term->name ) .'" />';
			}
		}

		// image
		if( 'image' ){

			$fn__get_thumb_id_from_text = function( $text ){
				if(
					preg_match( '/<img +src *= *[\'"]([^\'"]+)[\'"]/', $text, $mm ) &&
					( $mm[1]{0} === '/' || strpos($mm[1], $_SERVER['HTTP_HOST']) )
				){
					$name = basename( $mm[1] );
					$name = preg_replace('~-[0-9]+x[0-9]+(?=\..{2,6})~', '', $name ); // удалим размер (-80x80)
					$name = preg_replace('~\.[^.]+$~', '', $name );                   // удалим расширение
					$name = sanitize_title( sanitize_file_name( $name ) );            // приведем к стандартному виду

					global $wpdb;
					$thumb_id = $wpdb->get_var(
						$wpdb->prepare( "SELECT ID FROM $wpdb->posts WHERE post_name = %s AND post_type = 'attachment'", $name )
					);
				}

				return empty($thumb_id) ? 0 : $thumb_id;
			};

			$thumb_id = 0;
			if( $is_post ){

				if( ! $thumb_id = get_post_thumbnail_id( $post ) ){

					/**
					 * Allows to turn off the image search in post content.
					 *
					 * @param bool $is_on
					 */
					if( apply_filters( 'kama_og_meta_thumb_id_find_in_content', true ) ){

						if( ! $thumb_id = $fn__get_thumb_id_from_text( $post->post_content ) ) {
							// первое вложение поста
							$attach = get_children(
								[ 'numberposts'=>'1', 'post_mime_type'=>'image', 'post_type'=>'attachment', 'post_parent'=>$post->ID ]
							);
							if( $attach && $attach = array_shift( $attach ) )
								$thumb_id = $attach->ID;
						}
					}
				}
			}
			elseif( $is_term ){
				if( ! $thumb_id = get_term_meta( $term->term_id, '_thumbnail_id', 1 ) ){
					$thumb_id = $fn__get_thumb_id_from_text( $term->description );
				}
			}

			/**
			 * Allow to set custom `og:image` data (URL).
			 *
			 * @param int|string|array  $thumb_id  Attachment ID. Image URL. Array of [ image_url, width, height ].
			 */
			$thumb_id = apply_filters( 'kama_og_meta_thumb_id', $thumb_id );

			if( $thumb_id ){

				if( is_numeric($thumb_id) )
					list( $image_url, $img_width, $img_height ) = image_downsize( $thumb_id, 'full' );
				elseif( is_array($thumb_id) )
					list( $image_url, $img_width, $img_height ) = $thumb_id;
				else
					$image_url = $thumb_id;

				// Open Graph image
				$els['og:image'] = '<meta property="og:image" content="'. esc_url($image_url) .'" />';
				if( isset($img_width) )
					$els['og:image:width']  = '<meta property="og:image:width" content="'. (int) $img_width .'" />';
				if( isset($img_height) )
					$els['og:image:height'] = '<meta property="og:image:height" content="'. (int) $img_height .'" />';
			}

		}

		// twitter
		$els['twitter:card']        = '<meta name="twitter:card" content="summary" />';
		$els['twitter:description'] = '<meta name="twitter:description" content="'. esc_attr( $desc ) .'" />';
		$els['twitter:title']       = '<meta name="twitter:title" content="'. esc_attr( $title ) .'" />';
		if( isset($image_url) )
			$els['twitter:image'] = '<meta name="twitter:image" content="'. esc_url($image_url) .'" />';

		/**
		 * Filter resulting properties. Allows to add or remove any og/twitter properties.
		 *
		 * @param array  $els
		 */
		$els = apply_filters( 'kama_og_meta_elements', $els );

		return "\n\n". implode("\n", $els ) ."\n\n";
	}

	/**
	 * Выводит заголовок страницы <title>
	 *
	 * Для меток и категорий указывается в настройках, в описании: [title=Заголовок].
	 * Для записей, если нужно, чтобы заголовок страницы отличался от заголовка записи,
	 * создайте произвольное поле title и впишите туда произвольный заголовок.
	 *
	 * @version 4.9
	 *
	 * @param string     $sep            разделитель
	 * @param true|false $add_blog_name  добавлять ли название блога в конец заголовка для архивов.
	 */
	static function meta_title( $sep = '»', $add_blog_name = true ){
		static $cache; if( $cache ) return $cache;

		global $post;

		$l10n = apply_filters( 'kama_meta_title_l10n', array(
			'404'     => 'Ошибка 404: такой страницы не существует',
			'search'  => 'Результаты поиска по запросу: %s',
			'compage' => 'Комментарии %s',
			'author'  => 'Статьи автора: %s',
			'archive' => 'Архив за',
			'paged'   => '(страница %d)',
		) );

		$parts = array(
			'prev'  => '',
			'title' => '',
			'after' => '',
			'paged' => '',
		);
		$title = & $parts['title']; // упростим
		$after = & $parts['after']; // упростим



        if(0){}
        //Страница принудительного показа
        elseif (isset( $GLOBALS['SHOWTHIS'])){
            $title = get_post_meta( $GLOBALS['SHOWTHIS']['ID'], 'title', 1 );
            if (!$title) {
                $title = $GLOBALS['SHOWTHIS']['post_title'];
            }
        }
		// 404
		elseif ( is_404() ){
			$title = $l10n['404'];
		}
		// поиск
		elseif ( is_search() ){
			$title = sprintf( $l10n['search'], get_query_var('s') );
		}
		// главная
		elseif( is_front_page() ){
			if( is_page() && $title = get_post_meta( $post->ID, 'title', 1 ) ){
				// $title определен
			} else {
				$title = get_bloginfo('name');
				$after = get_bloginfo('description');
			}
		}
		// отдельная страница
		elseif( is_singular() || ( is_home() && ! is_front_page() ) || ( is_page() && ! is_front_page() ) ){
			$title = get_post_meta( $post->ID, 'title', 1 ); // указанный title у записи в приоритете

			if( ! $title ) $title = apply_filters( 'kama_meta_title_singular', '', $post );
			if( ! $title ) $title = single_post_title( '', 0 );

			if( $cpage = get_query_var('cpage') )
				$parts['prev'] = sprintf( $l10n['compage'], $cpage );
		}
		// архив типа поста
		elseif ( is_post_type_archive() ){
			$title = post_type_archive_title('', 0 );
			$after = 'blog_name';
		}
		// таксономии
		elseif( is_category() || is_tag() || is_tax() ){
			$term = get_queried_object();

			$title = get_term_meta( $term->term_id, 'title', 1 );

			if( ! $title ){
				$title = single_term_title('', 0 );

				if( is_tax() )
					$parts['prev'] = get_taxonomy($term->taxonomy)->labels->name;
			}

			$after = 'blog_name';
		}
		// архив автора
		elseif ( is_author() ){
			$title = sprintf( $l10n['author'], get_queried_object()->display_name );
			$after = 'blog_name';
		}
		// архив даты
		elseif ( ( get_locale() === 'ru_RU' ) && ( is_day() || is_month() || is_year() ) ){
			$rus_month  = array('', 'январь', 'февраль', 'март', 'апрель', 'май', 'июнь', 'июль', 'август', 'сентябрь', 'октябрь', 'ноябрь', 'декабрь');
			$rus_month2 = array('', 'января', 'февраля', 'марта', 'апреля', 'мая', 'июня', 'июля', 'августа', 'сентября', 'октября', 'ноября', 'декабря');
			$year       = get_query_var('year');
			$monthnum   = get_query_var('monthnum');
			$day        = get_query_var('day');

			if( is_year() )      $dat = "$year год";
			elseif( is_month() ) $dat = "$rus_month[$monthnum] $year года";
			elseif( is_day() )   $dat = "$day $rus_month2[$monthnum] $year года";

			$title = sprintf( $l10n['archive'], $dat );
			$after = 'blog_name';
		}
		// остальные архивы
		else {
			$title = get_the_archive_title();
			$after = 'blog_name';
		}

		// номера страниц для пагинации и деления записи
		$pagenum = get_query_var('paged') ?: get_query_var('page');
		if( $pagenum )
			$parts['paged'] = sprintf( $l10n['paged'], $pagenum );

		// позволяет фильтровать title как угодно. Сам заголово
		// $parts содержит массив с элементами: prev - текст до, title - заголовок, after - текст после
		$parts = apply_filters_ref_array( 'kama_meta_title_parts', array($parts, $l10n) );

		if( $after == 'blog_name' )
			$after = $add_blog_name ? get_bloginfo('name') : '';

		// добавим пагинацию в title
		if( $parts['paged'] ){
			$parts['title'] .=  " {$parts['paged']}";
			unset( $parts['paged'] );
		}

		$title = implode( ' '. trim($sep) .' ', array_filter($parts) );

		$title = apply_filters( 'kama_meta_title', $title );

		$title = wptexturize( $title );
		$title = esc_html( $title );

		return $cache = $title;
	}

	/**
	 * Выводит метатег description.
	 *
	 * Для элементов таксономий: метаполе description или в описании такой шоткод [description = текст описания]
	 * У постов сначала проверяется, метаполе description, или цитата, или начальная часть контента.
	 * Цитата или контент обрезаются до указанного в $maxchar символов.
	 *
	 * @param string $home_description Указывается описание для главной страницы сайта.
	 * @param int    $maxchar          Максимальная длина описания (в символах).
	 *
	 * @version 2.2.2
	 */
	static function meta_description( $home_description = '', $maxchar = 260 ){
		static $cache; if( $cache ) return $cache;

		global $post;

		$cut   = true;
		$desc  = '';
        //Страница принудительного показа
        if (isset( $GLOBALS['SHOWTHIS'])){
            $cut = false;
            $desc = get_post_meta( $GLOBALS['SHOWTHIS']['ID'], 'description', 1 );
        }            
        // front
        elseif( is_front_page() ){
			// когда для главной установлена страница
			if( is_page() && $desc = get_post_meta($post->ID, 'description', true )  ){
				$cut = false;
			}

			if( ! $desc )
				$desc = $home_description ?: get_bloginfo( 'description', 'display' );
		}
		// singular
		elseif( is_singular() ){
			if( $desc = get_post_meta($post->ID, 'description', true ) )
				$cut = false;

			if( ! $desc ) $desc = $post->post_excerpt ?: $post->post_content;

			$desc = trim( strip_tags( $desc ) );
		}
		// term
		elseif( is_category() || is_tag() || is_tax() ){
			$term = get_queried_object();

			$desc = get_term_meta( $term->term_id, 'meta_description', true );
			if( ! $desc )
				$desc = get_term_meta( $term->term_id, 'description', true );

			$cut = false;
			if( ! $desc && $term->description ){
				$desc = strip_tags( $term->description );
				$cut = true;
			}
		}

		$origin_desc = $desc;

		if( $desc = apply_filters( 'kama_meta_description_pre', $desc ) ){

			$desc = str_replace( array("\n", "\r"), ' ', $desc );
			$desc = preg_replace( '~\[[^\]]+\](?!\()~', '', $desc ); // удаляем шоткоды. Оставляем маркдаун [foo](URL)

			if( $cut ){
				$char = mb_strlen( $desc );
				if( $char > $maxchar ){
					$desc     = mb_substr( $desc, 0, $maxchar );
					$words    = explode(' ', $desc );
					$maxwords = count($words) - 1; // убираем последнее слово, оно в 90% случаев неполное
					$desc     = join(' ', array_slice($words, 0, $maxwords)).' ...';
				}
			}

			$desc = preg_replace( '/\s+/s', ' ', $desc );
		}

		if( $desc = apply_filters( 'kama_meta_description', $desc, $origin_desc, $cut, $maxchar ) )
			return $cache = '<meta name="description" content="'. esc_attr( trim($desc) ) .'" />'."\n";

		return $cache = '';
	}

	/**
	 * Метатег robots
	 *
	 * Чтобы задать свои атрибуты метатега robots записи, создайте произвольное поле с ключом robots
	 * и необходимым значением, например: noindex,nofollow
	 *
	 * Укажите параметр $allow_types, чтобы разрешить индексацию типов страниц.
	 *
	 * @ $allow_types Какие типы страниц нужно индексировать (через запятую):
	 *                cpage, is_category, is_tag, is_tax, is_author, is_year, is_month,
	 *                is_attachment, is_day, is_search, is_feed, is_post_type_archive, is_paged
	 *                (можно использовать любые условные теги в виде строки)
	 *                cpage - страницы комментариев
	 * @ $robots      Как закрывать индексацию: noindex,nofollow
	 *
	 * version 0.8
	 */
	static function meta_robots( $allow_types = null, $robots = 'noindex,nofollow' ){
		global $post;

        if (null === $allow_types) {
            $allow_types = 'cpage, is_attachment, is_category, is_tag, is_tax, is_paged, is_post_type_archive';
        }
        
        if (isset( $GLOBALS['SHOWTHIS'])){
            $robots = get_post_meta( $GLOBALS['SHOWTHIS']['ID'], 'robots', true );
        }            
		elseif( ( is_home() || is_front_page() ) && ! is_paged() ) {
			return '';
        }
		elseif( is_singular() ){
			// если это не вложение или вложение но оно разрешено
			if( ! is_attachment() || false !== strpos($allow_types,'is_attachment') )
				$robots = get_post_meta( $post->ID, 'robots', true );
		}
		else {
			$types = preg_split('~[, ]+~', $allow_types );
			$types = array_filter( $types );

			foreach( $types as $type ){
				if( $type == 'cpage' && strpos($_SERVER['REQUEST_URI'], '/comment-page') ) $robots = false;
				elseif( function_exists($type) && $type() )                                $robots = false;
			}
		}

		$robots = apply_filters( 'kama_meta_robots_close', $robots );

        return $robots ? "<meta name=\"robots\" content=\"$robots\" />\n" : '';
	}

	/**
	 * Генерирует метатег keywords для head части старницы.
	 *
	 * Чтобы задать свои keywords для записи, создайте произвольное поле keywords и впишите в значения необходимые ключевые слова.
	 * Для постов (post) ключевые слова генерируются из меток и названия категорий, если не указано произвольное поле keywords.
	 *
	 * Для меток, категорий и произвольных таксономий, ключевые слова указываются в описании, в шоткоде: [ keywords=слово1, слово2, слово3 ]
	 *
	 * @ $home_keywords: Для главной, ключевые слова указываются в первом параметре: meta_keywords( 'слово1, слово2, слово3' );
	 * @ $def_keywords: сквозные ключевые слова - укажем и они будут прибавляться к остальным на всех страницах
	 *
	 * version 0.8
	 */
	static function meta_keywords( $home_keywords = '', $def_keywords = '' ){
		global $post;

		$out = '';
        //Страница принудительного показа
        if (isset($GLOBALS['SHOWTHIS'])) {
            $out = get_post_meta( $GLOBALS['SHOWTHIS']['ID'], 'keywords', 1 );
        }
		elseif ( is_front_page() ){
			$out = $home_keywords;
		}
		elseif( is_singular() ){
			$out = get_post_meta( $post->ID, 'keywords', true );

			// для постов указываем ключами метки и категории, если не указаны ключи в произвольном поле
			if( ! $out && $post->post_type == 'post' ){
				$res = wp_get_object_terms( $post->ID, [ 'post_tag', 'category' ], [ 'orderby' => 'none' ] ); // получаем категории и метки

				if( $res && ! is_wp_error($res) )
					foreach( $res as $tag )
						$out .= ", $tag->name";

				$out = ltrim( $out, ', ' );
			}
		}
		elseif ( is_category() || is_tag() || is_tax() ){
			$term = get_queried_object();

			// wp 4.4
			if( function_exists('get_term_meta') ){
				$out = get_term_meta( $term->term_id, "keywords", true );
			}
			else{
				preg_match( '!\[keywords=([^\]]+)\]!iU', $term->description, $match );
				$out = isset($match[1]) ? $match[1] : '';
			}
		}

		if( $out && $def_keywords )
			$out = $out .', '. $def_keywords;

		return $out ? "<meta name=\"keywords\" content=\"$out\" />\n" : '';
	}

}
Kama_SEO_Tags::init();