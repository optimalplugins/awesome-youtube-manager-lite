<?php
/*
  Plugin Name: Awesome YouTube Embed Manager Lite
  Plugin URI: http://www.OptimalPlugins.com/
  Description: Hide Annoying YouTube suggested, recommended, related videos
  Version: 1.0
  Author: OptimalPlugins.com
  Author URI: http://www.OptimalPlugins.com/
  License: GPLv2 or later
*/

add_filter('oembed_result', 'aym_set_video_params', 10, 3);

function aym_set_video_params($data, $url, $args = array()) {

	$params = 'wmode=transparent&amp;rel=0';

	if (strpos($data, "feature=oembed") !== false) {
		$data = str_replace('feature=oembed', $params . '&amp;feature=oembed', $data);
	} elseif (strpos($data, "list=") !== false) {
		$data = str_replace('list=', $params . '&amp;list=', $data);
	}

	return $data;
}

// Show clear cache link
add_filter('plugin_action_links', 'aym_show_clear_cache_link', 10, 2);
function aym_show_clear_cache_link($links, $file) {
    static $this_plugin;

    if (!$this_plugin) {
        $this_plugin = plugin_basename(__FILE__);
    }

    if ($file == $this_plugin) {
        $custom_link = '<a href="' . admin_url('plugins.php?aym_clear_cache=1') . '">Clear YouTube oEmbed Cache</a>';
        array_unshift($links, $custom_link);
    }
    return $links;
}

// Clear youtube embed cache
register_activation_hook(__FILE__, 'aym_clear_cache');
// Clear Cache when admin page reload
add_action('admin_notices', 'aym_clear_cache');

function aym_clear_cache() {
	global $wpdb;

	$post_ids = $wpdb->get_col("SELECT DISTINCT post_id FROM $wpdb->postmeta WHERE meta_key LIKE '_oembed_%'");

	if ($post_ids) {
		$postmetaids = $wpdb->get_col("SELECT meta_id FROM $wpdb->postmeta WHERE meta_key LIKE '_oembed_%'");
		$in = implode(',', array_fill(1, count($postmetaids), '%d'));
		do_action('delete_postmeta', $postmetaids);

		$wpdb->query($wpdb->prepare("DELETE FROM $wpdb->postmeta WHERE meta_id IN ($in)", $postmetaids));
		do_action('deleted_postmeta', $postmetaids);

		foreach ($post_ids as $post_id) {
			wp_cache_delete($post_id, 'post_meta');
		}

        if (isset($_GET['aym_clear_cache'])) {
            echo '<div class="updated"><p><strong>Success!</strong> YouTube oEmbed cache has been cleared.</p></div>';
        }

		return true;
	}
}

add_filter('jetpack_shortcodes_to_include', 'aym_remove_jetpack_youtube_shortcode');

// remove jetpack youtube shortcode
function aym_remove_jetpack_youtube_shortcode( $shortcodes ) {
    $jetpack_shortcodes_dir = WP_CONTENT_DIR . '/plugins/jetpack/modules/shortcodes/';
    $shortcodes_to_unload = array('youtube.php');

    foreach ($shortcodes_to_unload as $shortcode) {
        if ($key = array_search($jetpack_shortcodes_dir . $shortcode, $shortcodes)) {
            unset($shortcodes[$key]);
        }
    }
    return $shortcodes;
}
