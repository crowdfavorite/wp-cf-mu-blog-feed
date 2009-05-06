<?php
/*
Plugin Name: Mu Blog Feed 
Plugin URI: cf-mu-blog-feed 
Description: This plugin outputs a list of blogs with the one with the most recent post placed at top, and has links to the latest 3 posts in that blog
Version: 1.0 
Author: Crowd Favorite
Author URI: http://crowdfavorite.com
*/

// ini_set('display_errors', '1'); ini_set('error_reporting', E_ALL);

if (!defined('PLUGINDIR')) {
	define('PLUGINDIR','wp-content/plugins');
}


load_plugin_textdomain('cf-mu-blog-feed');
wp_enqueue_script('jquery');


function cfmbf_request_handler() {
	if (!empty($_GET['cf_action'])) {
		switch ($_GET['cf_action']) {

			case 'cfmbf_admin_js':
				cfmbf_admin_js();
				break;
			case 'cfmbf_admin_css':
				cfmbf_admin_css();
				die();
				break;
		}
	}
	if (!empty($_POST['cf_action'])) {
		switch ($_POST['cf_action']) {

			case 'cfmbf_update_settings':
				cfmbf_save_settings();
				wp_redirect(trailingslashit(get_bloginfo('wpurl')).'wp-admin/options-general.php?page='.basename(__FILE__).'&updated=true');
				die();
				break;
		}
	}
}
add_action('init', 'cfmbf_request_handler');


function get_cf_mu_blog_feed() {
	/* Get array of latest updated blogs */
	$blog_list = get_last_updated();

	/* Set up the number of posts per blog to retrieve */
	$num_posts = 3; // default to 3 posts
	$num_posts = apply_filters('cf_mu_blog_feed_num_posts', $num_posts);

	/* Set up the array var that we'll return in the end */
	$blog_feed = array();
	
	/* Loop through latest blogs and grab last X number of posts to add to the blog_feed array*/
	foreach ($blog_list as $blog) {
		switch_to_blog($blog['blog_id']);
		$args = array(
			'numberposts' => $num_posts,
		);
		$blog_feed[$blog['blog_id']]['posts'] = get_posts($args);

		/* Loop through each of the blogs' posts and get the permalink and featured image for each post */
		foreach ($blog_feed[$blog['blog_id']]['posts'] as $post) {
			$post->permalink = get_permalink($post->ID);
			
			/* Only attach featured image if plugin exists */
			if (function_exists('cffp_get_img')) {
				$post->featured_image = cffp_get_img($post->ID, 'thumbnail', 'featured_image');
			}
		}
		restore_current_blog();
	}
	return apply_filters('cf_mu_blog_feed_results', $blog_feed);
}









function cfmbf_admin_js() {
	header('Content-type: text/javascript');
// TODO
	die();
}

wp_enqueue_script('cfmbf_admin_js', trailingslashit(get_bloginfo('url')).'?cf_action=cfmbf_admin_js', array('jquery'));


function cfmbf_admin_css() {
	header('Content-type: text/css');
?>
fieldset.options div.option {
	background: #EAF3FA;
	margin-bottom: 8px;
	padding: 10px;
}
fieldset.options div.option label {
	display: block;
	float: left;
	font-weight: bold;
	margin-right: 10px;
	width: 150px;
}
fieldset.options div.option span.help {
	color: #666;
	font-size: 11px;
	margin-left: 8px;
}
<?php
	die();
}

function cfmbf_admin_head() {
	echo '<link rel="stylesheet" type="text/css" href="'.trailingslashit(get_bloginfo('url')).'?cf_action=cfmbf_admin_css" />';
}
add_action('admin_head', 'cfmbf_admin_head');


/*
$example_settings = array(
	'key' => array(
		'type' => 'int',
		'label' => 'Label',
		'default' => 5,
		'help' => 'Some help text here',
	),
	'key' => array(
		'type' => 'select',
		'label' => 'Label',
		'default' => 'val',
		'help' => 'Some help text here',
		'options' => array(
			'value' => 'Display'
		),
	),
);
*/
$cfmbf_settings = array(
	'cfmbf_' => array(
		'type' => 'string',
		'label' => '',
		'default' => '',
		'help' => '',
	),
	'cfmbf_' => array(
		'type' => 'int',
		'label' => '',
		'default' => 5,
		'help' => '',
	),
	'cfmbf_' => array(
		'type' => 'select',
		'label' => '',
		'default' => '',
		'help' => '',
		'options' => array(
			'' => ''
		),
	),
	'cfmbf_cat' => array(
		'type' => 'select',
		'label' => 'Category:',
		'default' => '',
		'help' => '',
		'options' => array(),
	),
	'cfmbf_author' => array(
		'type' => 'select',
		'label' => 'Author:',
		'default' => 1,
		'help' => '',
		'options' => array(),
	),

);

function cfmbf_setting($option) {
	$value = get_option($option);
	if (empty($value)) {
		global $cfmbf_settings;
		$value = $cfmbf_settings[$option]['default'];
	}
	return $value;
}

function cfmbf_admin_menu() {
	if (current_user_can('manage_options')) {
		add_options_page(
			__('CF Mu BLog Feed Options', 'cf-mu-blog-feed')
			, __('CF Mu Blog Feed', 'cf-mu-blog-feed')
			, 10
			, basename(__FILE__)
			, 'cfmbf_settings_form'
		);
	}
}
add_action('admin_menu', 'cfmbf_admin_menu');

function cfmbf_plugin_action_links($links, $file) {
	$plugin_file = basename(__FILE__);
	if (basename($file) == $plugin_file) {
		$settings_link = '<a href="options-general.php?page='.$plugin_file.'">'.__('Settings', 'cf-mu-blog-feed').'</a>';
		array_unshift($links, $settings_link);
	}
	return $links;
}
add_filter('plugin_action_links', 'cfmbf_plugin_action_links', 10, 2);

if (!function_exists('cf_settings_field')) {
	function cf_settings_field($key, $config) {
		$option = get_option($key);
		if (empty($option) && !empty($config['default'])) {
			$option = $config['default'];
		}
		$label = '<label for="'.$key.'">'.$config['label'].'</label>';
		$help = '<span class="help">'.$config['help'].'</span>';
		switch ($config['type']) {
			case 'select':
				$output = $label.'<select name="'.$key.'" id="'.$key.'">';
				foreach ($config['options'] as $val => $display) {
					$option == $val ? $sel = ' selected="selected"' : $sel = '';
					$output .= '<option value="'.$val.'"'.$sel.'>'.htmlspecialchars($display).'</option>';
				}
				$output .= '</select>'.$help;
				break;
			case 'textarea':
				$output = $label.'<textarea name="'.$key.'" id="'.$key.'">'.htmlspecialchars($option).'</textarea>'.$help;
				break;
			case 'string':
			case 'int':
			default:
				$output = $label.'<input name="'.$key.'" id="'.$key.'" value="'.htmlspecialchars($option).'" />'.$help;
				break;
		}
		return '<div class="option">'.$output.'<div class="clear"></div></div>';
	}
}

function cfmbf_settings_form() {
	global $cfmbf_settings;


	$cat_options = array();
	$categories = get_categories('hide_empty=0');
	foreach ($categories as $category) {
		$cat_options[$category->term_id] = htmlspecialchars($category->name);
	}
	$cfmbf_settings['cfmbf_cat']['options'] = $cat_options;


	$author_options = array();
	$authors = get_users_of_blog();
	foreach ($authors as $user) {
		$usero = new WP_User($user->user_id);
		$author = $usero->data;
		// Only list users who are allowed to publish
		if (! $usero->has_cap('publish_posts')) {
			continue;
		}
		$author_options[$author->ID] = htmlspecialchars($author->user_nicename);
	}
	$cfmbf_settings['cfmbf_author']['options'] = $author_options;


	print('
<div class="wrap">
	<h2>'.__('CF Mu BLog Feed Options', 'cf-mu-blog-feed').'</h2>
	<form id="cfmbf_settings_form" name="cfmbf_settings_form" action="'.get_bloginfo('wpurl').'/wp-admin/options-general.php" method="post">
		<input type="hidden" name="cf_action" value="cfmbf_update_settings" />
		<fieldset class="options">
	');
	foreach ($cfmbf_settings as $key => $config) {
		echo cf_settings_field($key, $config);
	}
	print('
		</fieldset>
		<p class="submit">
			<input type="submit" name="submit" value="'.__('Save Settings', 'cf-mu-blog-feed').'" />
		</p>
	</form>
</div>
	');
}

function cfmbf_save_settings() {
	if (!current_user_can('manage_options')) {
		return;
	}
	global $cfmbf_settings;
	foreach ($cfmbf_settings as $key => $option) {
		$value = '';
		switch ($option['type']) {
			case 'int':
				$value = intval($_POST[$key]);
				break;
			case 'select':
				$test = stripslashes($_POST[$key]);
				if (isset($option['options'][$test])) {
					$value = $test;
				}
				break;
			case 'string':
			case 'textarea':
			default:
				$value = stripslashes($_POST[$key]);
				break;
		}
		update_option($key, $value);
	}
}

//a:21:{s:11:"plugin_name";s:12:"Mu Blog Feed";s:10:"plugin_uri";s:15:"cf-mu-blog-feed";s:18:"plugin_description";s:138:"This plugin outputs a list of blogs with the one with the most recent post placed at top, and has links to the latest 3 posts in that blog";s:14:"plugin_version";s:3:"1.0";s:6:"prefix";s:5:"cfmbf";s:12:"localization";s:15:"cf-mu-blog-feed";s:14:"settings_title";s:23:"CF Mu BLog Feed Options";s:13:"settings_link";s:15:"CF Mu Blog Feed";s:4:"init";b:0;s:7:"install";b:0;s:9:"post_edit";b:0;s:12:"comment_edit";b:0;s:6:"jquery";s:1:"1";s:6:"wp_css";b:0;s:5:"wp_js";b:0;s:9:"admin_css";s:1:"1";s:8:"admin_js";s:1:"1";s:15:"request_handler";s:1:"1";s:6:"snoopy";b:0;s:11:"setting_cat";s:1:"1";s:14:"setting_author";s:1:"1";}

?>