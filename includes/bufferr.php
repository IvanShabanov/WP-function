<?

function add_buffer_start($functionname) {
	$_GLOBAL['buffer_start'][] = $functionname;
}

function add_buffer_end($functionname) {
	$_GLOBAL['buffer_end'][] = $functionname;
}

function buffer_start() {
	if (is_array($_GLOBAL['buffer_start'])) {
		foreach ($_GLOBAL['buffer_start'] as $functionname) {
			if (function_exists($functionname)) {
				$functionname();
			}
		}
	}
	ob_start('buffer_content');
}

function buffer_content($content) {
	if (is_array($_GLOBAL['buffer_end'])) {
		foreach ($_GLOBAL['buffer_end'] as $functionname) {
			if (function_exists($functionname)) {
				$content = $functionname($content);
			}
		}
	}
	return $content;
}

function buffer_end() {
	ob_end_flush();
}

add_action('wp_head', 'buffer_start');
add_action('wp_footer', 'buffer_end');
