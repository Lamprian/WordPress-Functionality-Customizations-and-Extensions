<?php

/**
 * WordPress Functionality Customizations and Extensions
 *
 * Description:
 * This file is a dynamic script designed to customize and extend the 
 * functionality of WordPress. Its purpose is to continuously improve 
 * and adapt features based on project requirements.
 *
 * Key Features:
 * - The file will be frequently updated with new features and improvements.
 * - Designed to provide flexibility and scalability for WordPress installations.
 * - Includes well-documented code for easy maintenance and understanding.
 *
 * Usage:
 * - Place this file in your WordPress theme's directory (`wp-content/themes/your-theme/functions.php`).
 * - Customize the code as per your requirements.
 *
 * Author: Anastasios Lamprianidis
 * Site: https://alamprianidis.gr/
 *
 * License: GPL-3.0+
 *
 * Copyright (C) 2024
 *
 * This program is distributed under the GNU General Public License, version 3 (GPL-3.0+).
 * See the full license at https://www.gnu.org/licenses/gpl-3.0.html
 */


/**
 * Function to regenerate thumbnails for all images in the WordPress media library.
 * Iterates through all image attachments in the database, checks if the file exists,
 * and regenerates thumbnails using WordPress image processing functions.
 */
function regenerateThumbnails() {
    // Access the global $wpdb object for database interactions
    global $wpdb;

    // Retrieve all image attachments from the database
    $images = $wpdb->get_results("SELECT ID FROM $wpdb->posts WHERE post_type = 'attachment' AND post_mime_type LIKE 'image/%'");

    // Loop through each image attachment
    foreach ($images as $image) {
        // Get the attachment ID
        $id = $image->ID;

        // Retrieve the full file path of the attachment
        $fullsizepath = get_attached_file($id);

        // Check if the file path is invalid or the file does not exist
        if (false === $fullsizepath || !file_exists($fullsizepath)) {
            continue; // Skip this image if the file is missing
        }

        // Include the image processing functions if not already loaded
        require_once(ABSPATH . 'wp-admin/includes/image.php');

        // Regenerate the thumbnails and update the metadata
        $metadata = wp_generate_attachment_metadata($id, $fullsizepath);
        if (!wp_update_attachment_metadata($id, $metadata)) {
            // Log or handle errors if thumbnail regeneration fails for this image
            error_log("Failed to update metadata for attachment ID: $id");
        }
    }
}

/**
 * Note:
 * This function does not return a specific value; it processes all images silently.
 * It can be extended to provide feedback or logging for improved monitoring.
 */

/**
 * Function to replace the default WordPress admin logo with a custom logo.
 * The custom logo is loaded from the "images" folder of the active theme.
 * This is achieved by injecting custom CSS into the admin page header.
 */
function custom_admin_logo() {
    echo '<style type="text/css"> 
    #header-logo { 
        background-image: url(' . get_bloginfo('template_directory') . '/images/admin_logo.png) !important; 
    } 
    </style>';

    /**
     * The CSS targets the element with the ID "header-logo" and sets the custom logo as the background image.
     */
}

// Hook the function into 'admin_head' to ensure it runs when the admin header is loaded.
add_action('admin_head', 'custom_admin_logo');

/**
 * Note:
 * Ensure that the "admin_logo.png" file exists in the "images" folder of the active theme.
 */

<?php
/**
 * Add Image to RSS Feed from Post Content
 *
 * This function adds the first image found in the post content
 * or the featured image (if no image is found in the content)
 * to the RSS feed.
 *
 * @param string $content The content of the post.
 * @return string Updated content with the image included.
 */
function mcw_featured_image_in_feeds($content) {

    global $post;

    // Extract the first image from the post content
    $output = preg_match('/< *img[^>]*src *= *["\']?([^"\']*)/i', $post->post_content, $matches);
    $first_img = $matches[1] ?? '';

    if ($first_img == '') {
        // If no image is found in the content, get the featured image
        $attachment_id = get_post_thumbnail_id($post->ID);
    } else {
        // Retrieve the attachment ID using the URL of the first image
        $attachment_id = attachment_url_to_postid($first_img);

        // If attachment ID is not found, process the image URL to refine it
        if ($attachment_id == '') {
            $first_img_parts = explode(".", $first_img);
            $ext = array_pop($first_img_parts); // Extract the file extension

            $first_img = implode('.', $first_img_parts); 
            $first_img_parts = explode('-', $first_img);
            array_pop($first_img_parts); // Remove the size suffix
            $first_img = implode('-', $first_img_parts) . '.' . $ext;

            $attachment_id = attachment_url_to_postid($first_img);
        }
    }

    // If an attachment ID exists, prepend the image to the content
    if ($attachment_id != '') {
        $content = wp_get_attachment_image($attachment_id, 'full') . $content;
    }

    return $content;
}

// Apply the function to RSS Feeds
add_filter('the_excerpt_rss', 'mcw_featured_image_in_feeds');
add_filter('the_content_feed', 'mcw_featured_image_in_feeds');

/**
 * Note:
 * This function ensures that images are included in the RSS feed, improving its visual appeal.
 * It can be extended to support additional image processing or default images if no image is found.
 */

/**
 * Duplicate posts and pages without plugins
 *
 * Adds a "Duplicate" link to the WordPress admin panel for posts, pages,
 * and custom post types. The duplicated item is created as a draft.
 */

// Add the duplicate link to the action list for posts and custom post types
add_filter('post_row_actions', 'custom_duplicate_post_link', 10, 2);

// Add the duplicate link to the action list for pages
add_filter('page_row_actions', 'custom_duplicate_post_link', 10, 2);

/**
 * Add a "Duplicate" link to the post/page action list
 *
 * @param array $actions The existing row actions.
 * @param WP_Post $post The current post object.
 * @return array The updated row actions with the "Duplicate" link.
 */
function custom_duplicate_post_link($actions, $post) {
    if (!current_user_can('edit_posts')) {
        return $actions;
    }

    // Generate the duplicate link
    $url = wp_nonce_url(
        add_query_arg(
            array(
                'action' => 'custom_duplicate_post_as_draft',
                'post'   => $post->ID,
            ),
            'admin.php'
        ),
        basename(__FILE__),
        'duplicate_nonce'
    );

    $actions['duplicate'] = '<a href="' . esc_url($url) . '" title="' . esc_attr__('Duplicate this item') . '" rel="permalink">' . __('Duplicate') . '</a>';

    return $actions;
}

/**
 * Note:
 * This function modifies the row actions in the WordPress admin panel
 * by adding a "Duplicate" link for posts and pages.
 */

/**
 * Create a duplicate of the post/page as a draft
 */
add_action('admin_action_custom_duplicate_post_as_draft', 'custom_duplicate_post_as_draft');

function custom_duplicate_post_as_draft() {
    if (empty($_GET['post']) || !isset($_GET['duplicate_nonce']) || !wp_verify_nonce($_GET['duplicate_nonce'], basename(__FILE__))) {
        wp_die(__('No post to duplicate has been provided or the action is unauthorized.'));
    }

    $post_id = absint($_GET['post']);
    $post = get_post($post_id);

    if (!$post) {
        wp_die(__('Post creation failed, original post not found.'));
    }

    $current_user = wp_get_current_user();
    $new_post_author = $current_user->ID;

    // Prepare the duplicated post data
    $args = array(
        'comment_status' => $post->comment_status,
        'ping_status'    => $post->ping_status,
        'post_author'    => $new_post_author,
        'post_content'   => $post->post_content,
        'post_excerpt'   => $post->post_excerpt,
        'post_name'      => $post->post_name,
        'post_parent'    => $post->post_parent,
        'post_password'  => $post->post_password,
        'post_status'    => 'draft',
        'post_title'     => $post->post_title,
        'post_type'      => $post->post_type,
        'menu_order'     => $post->menu_order
    );

    $new_post_id = wp_insert_post($args);

    // Duplicate taxonomies
    $taxonomies = get_object_taxonomies(get_post_type($post));
    foreach ($taxonomies as $taxonomy) {
        $post_terms = wp_get_object_terms($post_id, $taxonomy, array('fields' => 'slugs'));
        wp_set_object_terms($new_post_id, $post_terms, $taxonomy, false);
    }

    // Duplicate post meta
    $post_meta = get_post_meta($post_id);
    foreach ($post_meta as $meta_key => $meta_values) {
        if ($meta_key === '_wp_old_slug') {
            continue;
        }
        foreach ($meta_values as $meta_value) {
            add_post_meta($new_post_id, $meta_key, $meta_value);
        }
    }

    // Redirect to the list of posts/pages with a success notice
    wp_safe_redirect(
        add_query_arg(
            array(
                'post_type' => get_post_type($post),
                'duplicated' => 'success'
            ),
            admin_url('edit.php')
        )
    );
    exit;
}

/**
 * Note:
 * This function duplicates a post or page as a draft, including its taxonomies
 * and metadata. It can be extended to duplicate additional post properties.
 */

/**
 * Display a success notice after duplication
 */
add_action('admin_notices', 'custom_duplicate_admin_notice');

function custom_duplicate_admin_notice() {
    if (isset($_GET['duplicated']) && $_GET['duplicated'] === 'success') {
        echo '<div class="notice notice-success is-dismissible"><p>' . __('Post duplicated successfully.') . '</p></div>';
    }
}

/**
 * Note:
 * This function adds an admin notice to confirm successful duplication of a post or page.
 */


?>
