# WP-function
WordPress function

База для шаблона на wordpress

## Настройка

В файле config.php

IS_enable_subdomain - будут ли использоваться поддомены для регионов/городов

IS_enable_catalog - будет ли использоваться встроенный каталог товаров (Если собираетесь использовать woocommerce лучше не включать)

IS_catalog_slug - если используются встроенный каталог, то тут надо указать его slug

IS_use_catalog_page - показывать ли по адресу IS_catalog_slug, страницу с таким же адресом.
Т.е. если у вас IS_catalog_slug = 'catalog' и есть страница с адресом 'catalog', то показывать её.

SHOW_CHILDREN_TEMPLATES_PATH - путь шаблонов блоков для функции showChildren


## Функции


	add_buffer_start($functionname);

Добавляет вазов фунскции перед началом буферизации контента


....
.... будет время дальше напишу
....
