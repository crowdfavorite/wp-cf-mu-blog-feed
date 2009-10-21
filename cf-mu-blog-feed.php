<?php
/*
Plugin Name: CF Mu Blog Feed 
Plugin URI: cf-mu-blog-feed 
Description: This plugin returns a list of blogs with the one with the most recent post placed at top, and has links to the latest 3 posts in that blog.  Post's featured image, post categories, post permalink are also returned.
Version: 1.0 
Author: Crowd Favorite
Author URI: http://crowdfavorite.com
*/

// ini_set('display_errors', '1'); ini_set('error_reporting', E_ALL);

if (!defined('PLUGINDIR')) {
	define('PLUGINDIR','wp-content/plugins');
}

load_plugin_textdomain('cf-mu-blog-feed');

function cf_get_mu_blog_feed($num_posts = 3) {
	/* Get array of blogs */
	$blog_list = apply_filters('cf_mu_blog_feed_blog_list', get_blog_list(null, 'all')); // Get all blogs

	/* Set up the number of posts per blog to retrieve */
	$num_posts = apply_filters('cf_mu_blog_feed_num_posts', $num_posts);

	/* Set up the array var that we'll return in the end */
	$blog_feed = array();
	
	/* Loop through latest blogs and grab last X number of posts to add to the blog_feed array*/
	foreach ($blog_list as $blog) {
		switch_to_blog($blog['blog_id']);
		$blog_feed[$blog['blog_id']]['description'] = get_bloginfo('description');
		$blog_feed[$blog['blog_id']] = apply_filters('cf_mu_blog_feed_blog_info_hook', $blog_feed[$blog['blog_id']], $blog['blog_id']);
		$args = array(
			'numberposts' => $num_posts,
		);
		$blog_feed[$blog['blog_id']]['posts'] = get_posts($args);

		/* Loop through each of the blogs' posts and get the specific extra details about each post */
		foreach ($blog_feed[$blog['blog_id']]['posts'] as $key => $post) {
			/* Get the permalink into variable */
			$blog_feed[$blog['blog_id']]['posts'][$key]->permalink = get_permalink($post->ID);
			
			/* Attach featured image */
			if (function_exists('cffp_get_img')) {
				$blog_feed[$blog['blog_id']]['posts'][$key]->image = apply_filters('cf_mu_blog_feed_post_image', cffp_get_img($post->ID, 'thumbnail', 'featured_image'), $post->ID);
			}
			else {
				/* Put this in there in case there's a different service 
				* 	to get an image attached to the post down the road */
				$image_url = ''; // Default image_url to blank
				$blog_feed[$blog['blog_id']]['posts'][$key]->image = apply_filters('cf_mu_blog_feed_post_image', $image_url, $post->ID);
			}
			
			/* Get post categories */
			$blog_feed[$blog['blog_id']]['posts'][$key]->cats = get_the_category($post->ID);
		}
		restore_current_blog();
	}
	
	/* Get latest posts' date into array with a blog_id value that we
	*	can use later */
	foreach ($blog_feed as $key => $blog) {
		$sortorder[] = array(
			'latest_post' => $blog['posts'][0]->post_date,
			'blog_id' => $key
		);
	}

	/* Sort array desc according to the latest post of each */
	arsort($sortorder);
	
	/* Loop through each to get blogs in right order, and pull associated posts 
	* 	from original blog_feed array, so we don't use all the processing power
	* 	to do the same work over again. */
	$sorted_blog_feed = array();
	foreach ($sortorder as $blog) {
		$sorted_blog_feed[$blog['blog_id']] = $blog_feed[$blog['blog_id']];
	}
	
	return apply_filters('cf_mu_blog_feed_results', $sorted_blog_feed);
}
?>