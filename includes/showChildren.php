<?
 /****** Показать потомков страницы ************
 * $attr=array(
 *   'id' - id родителя
 *   'template' - шаблон в папке /templates/ файл .php
 *                   в файле foreach для массива pages
 *   'numberposts' - кол-во постов/страниц, по умолчанию 999
 *   'orderby' - поле сортировки, по умолчанию menu_order
 *   'iscat' - если родитель это категория по ставим "Y", тогда найдет все записи в категории
 *   'post_type' - тип постов которые нам нужны, по умолчанию page
 *	 'posts_per_page' -
 *   'ids' - строчка с id элементов через запятую
 *
 *   'params' - строчка json дополнительных параметров используемых в шаблоне,
 * 				"filter":{"ИмяПоля":"Значение"}  - если нужна фильтрация элементов по полю
 *
 *   Использовать в страницах так
 *   [showChildren id=1 template="sidemenu"][/showChildren]
 *   [showChildren id=0 template="materials_slider" post_type="materials" params='{"filter":{"pokazyvat_na_glavnoj":"Да"}}'][/showChildren]
 */

if (!(defined('SHOW_CHILDREN_TEMPLATES_PATH'))) {
	define('SHOW_CHILDREN_TEMPLATES_PATH', 'templates/');
}
add_shortcode('showChildren', 'showChildren');
function showChildren($attr)
{
	$result = '';
	if (isset($attr['template'])) {
		$template = $attr['template'];
	}
	if ((isset($attr['id'])) && (is_numeric($attr['id']))) {

		$filter = array(
			'post_status' => 'publish',
		);
		if ((isset($attr['numberposts'])) && (is_numeric($attr['numberposts']))) {
			$filter['numberposts'] = $attr['numberposts'];
		} else {
			$filter['numberposts'] = 999;
		};

		if (isset($attr['orderby'])) {
			$filter['orderby'] = $attr['orderby'];
		} else {
			$filter['orderby'] = 'menu_order';
		};
		if (isset($attr['order'])) {
			$filter['order'] = 'DESC';
		} else {
			$filter['order'] = 'ASC';
		};
		if (isset($attr['iscat'])) {
			$filter['category'] = $attr['id'];
		} else {
			$filter['post_parent'] = $attr['id'];
		};
		if (isset($attr['post_type'])) {
			$filter['post_type'] = $attr['post_type'];
		} else {
			$filter['post_type'] = 'page';
		};

		if (is_numeric($attr['posts_per_page'])) {
			$total_items = count(get_posts($filter));
			$max_pages = max(1, ceil($total_items / $attr['posts_per_page']));
			$filter['posts_per_page'] = $attr['posts_per_page'];
			$currentPage = (get_query_var('page')) ? get_query_var('page') : 1;
			if ((is_numeric($currentPage)) && ($currentPage <= $max_pages)) {
				$filter['offset'] = ($currentPage -1) * $filter['posts_per_page'];
			}
		};

		$pages = get_posts($filter);
	};

	if (isset($attr['ids'])) {
		$ids = explode(',', $attr['ids']);
		$pages = [];
		if (is_array($ids)) {
			foreach ($ids as $id) {
				if (is_numeric(trim($id))) {
					$page = get_post($id);
					if (!is_null($page)) {
						$pages[] = $page;
					}
				}
			}
		}
	}
	if (is_array($pages)) {

		if (isset($attr['params'])) {
			$params = @json_decode($attr['params'], true);
			$attr['params'] = $params;
		}

		if ((isset($attr['params']['filter'])) && (is_array($attr['params']['filter']))) {
			$new_pages = [];
			foreach( $pages as $page ) {
				$show = true;
				if (is_array($attr['params']['filter'])) {
					foreach ($attr['params']['filter'] as $key=>$val) {
						if ($key != '') {
							$page_val = get_field($key, $page->ID);
							if ($page_val != $val) {
								$show = false;
							}
						}
					}
				}
				if ($show) {
					$new_pages[] = $page;
				}
			}
			$pages = $new_pages;
		}

		ob_start();
		@include(SHOW_CHILDREN_TEMPLATES_PATH . $template . '.php');
		$result = ob_get_contents();
		ob_end_clean();
	}

	return $result;
};