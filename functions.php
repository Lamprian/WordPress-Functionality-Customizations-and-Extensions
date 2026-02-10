<?php

/**
The file will be frequently updated with new features and improvements.
 * - Designed to provide flexibility and scalability for WordPress installations.
 * - Includes well-documented code for easy maintenance and understanding.
 *
 * Usage:
 * - Place this file in your WordPress theme's directory (`wp-content/themes/your-theme/functions.php`).
 * - Customize the code as per your requirements.
 *
 * Author: Anastasios Lamprianidis
 * Site: https://lamprian.github.io/
 *
 * License: GPL-3.0+
 *
 * Copyright (C) 2026
 *
 * This program is distributed under the GNU General Public License, version 3 (GPL-3.0+).
 * See the full license at https://www.gnu.org/licenses/gpl-3.0.html
 */


/**
 * Function to regenerate thumbnails for all images in the WordPress media library.
 * Iterates through all image attachments in the database, checks if the file exists,
 * and regenerates thumbnails using WordPress image processing functions.
 */
/*
    Start: This function block starts for `regenerateThumbnails`.
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
/*
    End: This function block ends for `regenerateThumbnails`.
*/

/**
 * Note:
 * This function does not return a specific value; it processes all images silently.
 * It can be extended to provide feedback or logging for improved monitoring.
 */

/**
 * Resolve admin logo URL with child-theme fallback support.
 */
/*
    Start: This function block starts for `custom_admin_logo_image_url`.
*/
function custom_admin_logo_image_url() {
    $stylesheet_logo = trailingslashit(get_stylesheet_directory()) . 'images/admin_logo.png';

    if (file_exists($stylesheet_logo)) {
        return trailingslashit(get_stylesheet_directory_uri()) . 'images/admin_logo.png';
    }

    return trailingslashit(get_template_directory_uri()) . 'images/admin_logo.png';
}
/*
    End: This function block ends for `custom_admin_logo_image_url`.
*/

/**
 * Function to replace the default WordPress admin logo with a custom logo.
 * The custom logo is loaded from the "images" folder of the active theme.
 * This is achieved by injecting custom CSS into the admin page header.
 */
/*
    Start: This function block starts for `custom_admin_logo`.
*/
function custom_admin_logo() {
    echo '<style type="text/css"> 
    #header-logo { 
        background-image: url(' . esc_url(custom_admin_logo_image_url()) . ') !important; 
    } 
    </style>';

    /**
     * The CSS targets the element with the ID "header-logo" and sets the custom logo as the background image.
     */
}
/*
    End: This function block ends for `custom_admin_logo`.
*/

// Hook the function into 'admin_head' to ensure it runs when the admin header is loaded.
add_action('admin_head', 'custom_admin_logo');

/**
 * Note:
 * Ensure that the "admin_logo.png" file exists in the "images" folder of the active theme.
 */

/**
 * Resolve attachment ID from image URL and handle resized file names.
 */
/*
    Start: This function block starts for `mcw_get_attachment_id_from_image_url`.
*/
function mcw_get_attachment_id_from_image_url($image_url) {
    if (empty($image_url)) {
        return 0;
    }

    $attachment_id = attachment_url_to_postid($image_url);

    if (!empty($attachment_id)) {
        return (int) $attachment_id;
    }

    $parts = wp_parse_url($image_url);

    if (empty($parts['path'])) {
        return 0;
    }

    $path_info = pathinfo($parts['path']);

    if (empty($path_info['dirname']) || empty($path_info['filename']) || empty($path_info['extension'])) {
        return 0;
    }

    $original_filename = preg_replace('/-\d+x\d+$/', '', $path_info['filename']);
    $normalized_path = trailingslashit($path_info['dirname']) . $original_filename . '.' . $path_info['extension'];

    if (!empty($parts['query'])) {
        $normalized_path .= '?' . $parts['query'];
    }

    if (!empty($parts['fragment'])) {
        $normalized_path .= '#' . $parts['fragment'];
    }

    $normalized_url = str_replace($parts['path'], $normalized_path, $image_url);

    return (int) attachment_url_to_postid($normalized_url);
}
/*
    End: This function block ends for `mcw_get_attachment_id_from_image_url`.
*/

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
/*
    Start: This function block starts for `mcw_featured_image_in_feeds`.
*/
function mcw_featured_image_in_feeds($content) {

    global $post;

    if (!$post instanceof WP_Post) {
        return $content;
    }

    // Extract the first image from the post content
    preg_match('/< *img[^>]*src *= *["\']?([^"\']*)/i', $post->post_content, $matches);
    $first_img = $matches[1] ?? '';

    if ($first_img == '') {
        // If no image is found in the content, get the featured image
        $attachment_id = get_post_thumbnail_id($post->ID);
    } else {
        // Retrieve the attachment ID using the URL of the first image
        $attachment_id = mcw_get_attachment_id_from_image_url($first_img);
    }

    // If an attachment ID exists, prepend the image to the content
    if ($attachment_id != '') {
        $content = wp_get_attachment_image($attachment_id, 'full') . $content;
    }

    return $content;
}
/*
    End: This function block ends for `mcw_featured_image_in_feeds`.
*/

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
/*
    Start: This function block starts for `custom_duplicate_post_link`.
*/
function custom_duplicate_post_link($actions, $post) {
    if (!$post instanceof WP_Post) {
        return $actions;
    }

    if (!current_user_can('edit_post', $post->ID)) {
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
/*
    End: This function block ends for `custom_duplicate_post_link`.
*/

/**
 * Note:
 * This function modifies the row actions in the WordPress admin panel
 * by adding a "Duplicate" link for posts and pages.
 */

/**
 * Create a duplicate of the post/page as a draft
 */
add_action('admin_action_custom_duplicate_post_as_draft', 'custom_duplicate_post_as_draft');

/*
    Start: This function block starts for `custom_duplicate_post_as_draft`.
*/
function custom_duplicate_post_as_draft() {
    $nonce = isset($_GET['duplicate_nonce']) ? sanitize_text_field(wp_unslash($_GET['duplicate_nonce'])) : '';
    $post_id = isset($_GET['post']) ? absint(wp_unslash($_GET['post'])) : 0;

    if (empty($post_id) || empty($nonce) || !wp_verify_nonce($nonce, basename(__FILE__))) {
        wp_die(__('No post to duplicate has been provided or the action is unauthorized.'));
    }

    $post = get_post($post_id);

    if (!$post) {
        wp_die(__('Post creation failed, original post not found.'));
    }

    if (!current_user_can('edit_post', $post_id)) {
        wp_die(__('You are not allowed to duplicate this content.'));
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

    if (is_wp_error($new_post_id) || !$new_post_id) {
        wp_die(__('Post creation failed during duplication.'));
    }

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
            add_post_meta($new_post_id, $meta_key, maybe_unserialize($meta_value));
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
/*
    End: This function block ends for `custom_duplicate_post_as_draft`.
*/

/**
 * Note:
 * This function duplicates a post or page as a draft, including its taxonomies
 * and metadata. It can be extended to duplicate additional post properties.
 */

/**
 * Display a success notice after duplication
 */
add_action('admin_notices', 'custom_duplicate_admin_notice');

/*
    Start: This function block starts for `custom_duplicate_admin_notice`.
*/
function custom_duplicate_admin_notice() {
    $duplicated = isset($_GET['duplicated']) ? sanitize_text_field(wp_unslash($_GET['duplicated'])) : '';

    if ($duplicated === 'success') {
        echo '<div class="notice notice-success is-dismissible"><p>' . esc_html__('Post duplicated successfully.') . '</p></div>';
    }
}
/*
    End: This function block ends for `custom_duplicate_admin_notice`.
*/

/**
 * Note:
 * This function adds an admin notice to confirm successful duplication of a post or page.
 */

/**
 * Function to create a new menu in the WordPress admin panel for displaying notifications.
 * This function moves all admin notifications to a separate page in the admin menu.
 */
add_action('admin_menu', 'move_admin_notifications_to_menu');
/*
    Start: This function block starts for `move_admin_notifications_to_menu`.
*/
function move_admin_notifications_to_menu() {
    /**
     * Adds a new menu page for admin notifications.
     * 
     * @param string $page_title The title of the page displayed in the browser tab.
     * @param string $menu_title The title of the menu item displayed in the admin menu.
     * @param string $capability The required capability to access this menu.
     * @param string $menu_slug The unique identifier for the menu.
     * @param callable $function The function that renders the content of the page.
     * @param string $icon_url The icon displayed in the admin menu.
     * @param int $position The position of the menu in the admin menu order.
     */
    add_menu_page(
        __('Notifications', 'textdomain'), // The title of the page and menu item.
        __('Notifications', 'textdomain'), // The title displayed in the admin menu.
        'manage_options',                 // Capability required to access the page.
        'admin-notifications',            // Unique slug for the menu.
        'admin_notifications_page',       // Callback function to render the page content.
        'dashicons-info',                 // Icon for the menu item.
        1                                 // Menu position (1 places it at the top).
    );
}
/*
    End: This function block ends for `move_admin_notifications_to_menu`.
*/

/**
 * Callback function to display the content of the admin notifications page.
 * This function captures and displays all admin notifications.
 */
/*
    Start: This function block starts for `admin_notifications_page`.
*/
function admin_notifications_page() {
    echo '<div class="wrap">'; // Start the page wrapper.
    echo '<h1>' . __('Notifications', 'textdomain') . '</h1>'; // Display the page title.
    echo '<div id="admin-notifications-container">'; // Container for notifications.

    /**
     * Display all admin notices and messages.
     * Uses the standard WordPress hooks to capture and render notifications.
     */
    do_action('admin_notices'); // Hook for displaying regular admin notices.
    do_action('all_admin_notices'); // Hook for displaying additional admin notices.

    echo '</div>'; // Close the notifications container.
    echo '</div>'; // Close the page wrapper.
}
/*
    End: This function block ends for `admin_notifications_page`.
*/

/**
 * Function to disable admin notifications from appearing on other admin pages.
 * This ensures notifications are only visible on the "Notifications" page.
 */
add_action('in_admin_header', 'disable_admin_notifications_from_main_screen', 1);
/*
    Start: This function block starts for `disable_admin_notifications_from_main_screen`.
*/
function disable_admin_notifications_from_main_screen() {
    /**
     * Check if the current page is NOT the notifications page.
     * If true, remove all admin notifications.
     */
    $page = isset($_GET['page']) ? sanitize_key(wp_unslash($_GET['page'])) : '';

    if ($page !== 'admin-notifications') {
        remove_all_actions('admin_notices'); // Remove regular admin notices.
        remove_all_actions('all_admin_notices'); // Remove additional admin notices.
    }
}
/*
    End: This function block ends for `disable_admin_notifications_from_main_screen`.
*/

/**
 * Add post update date in RSS feeds for better feed readability.
 */
/*
    Start: This function block starts for `add_last_modified_date_to_feed`.
*/
function add_last_modified_date_to_feed($content) {
    if (!is_feed()) {
        return $content;
    }

    $modified = get_the_modified_date('c');

    if (empty($modified)) {
        return $content;
    }

    $date_markup = '<p><small>' . esc_html__('Last updated: ', 'textdomain') . ' ' . esc_html(get_the_modified_date()) . '</small></p>';

    return $date_markup . $content;
}
/*
    End: This function block ends for `add_last_modified_date_to_feed`.
*/

add_filter('the_excerpt_rss', 'add_last_modified_date_to_feed', 20);
add_filter('the_content_feed', 'add_last_modified_date_to_feed', 20);

/**
 * Register a reusable shortcode for displaying the current year.
 */
/*
    Start: This function block starts for `register_current_year_shortcode`.
*/
function register_current_year_shortcode() {
    add_shortcode('current_year', 'render_current_year_shortcode');
}
/*
    End: This function block ends for `register_current_year_shortcode`.
*/

add_action('init', 'register_current_year_shortcode');

/**
 * Return the current year for shortcode output.
 */
/*
    Start: This function block starts for `render_current_year_shortcode`.
*/
function render_current_year_shortcode() {
    return esc_html(wp_date('Y'));
}
/*
    End: This function block ends for `render_current_year_shortcode`.
*/

/**
 * Sanitize SVG uploads by restricting mime/extension combinations.
 */
/*
    Start: This function block starts for `mcw_allow_safe_svg_uploads`.
*/
function mcw_allow_safe_svg_uploads($mimes) {
    if (!current_user_can('manage_options')) {
        return $mimes;
    }

    $mimes['svg'] = 'image/svg+xml';
    return $mimes;
}
/*
    End: This function block ends for `mcw_allow_safe_svg_uploads`.
*/

add_filter('upload_mimes', 'mcw_allow_safe_svg_uploads');

/**
 * Add automatic lazy-loading and async decoding to post images.
 */
/*
    Start: This function block starts for `mcw_add_image_performance_attributes`.
*/
function mcw_add_image_performance_attributes($content) {
    if (is_admin() || empty($content)) {
        return $content;
    }

    $content = preg_replace('/<img(?![^>]*loading=)([^>]*)>/i', '<img loading="lazy"$1>', $content);
    $content = preg_replace('/<img(?![^>]*decoding=)([^>]*)>/i', '<img decoding="async"$1>', $content);

    return $content;
}
/*
    End: This function block ends for `mcw_add_image_performance_attributes`.
*/

add_filter('the_content', 'mcw_add_image_performance_attributes', 20);

/**
 * Disable comments and pingbacks on media attachments.
 */
/*
    Start: This function block starts for `mcw_close_attachment_comments`.
*/
function mcw_close_attachment_comments($open, $post_id) {
    if ('attachment' === get_post_type($post_id)) {
        return false;
    }

    return $open;
}
/*
    End: This function block ends for `mcw_close_attachment_comments`.
*/

add_filter('comments_open', 'mcw_close_attachment_comments', 10, 2);
add_filter('pings_open', 'mcw_close_attachment_comments', 10, 2);

/**
 * Remove query strings from static assets for improved cache hit ratio.
 */
/*
    Start: This function block starts for `mcw_remove_asset_query_strings`.
*/
function mcw_remove_asset_query_strings($src) {
    if (is_admin() || empty($src)) {
        return $src;
    }

    $parts = explode('?', $src);
    return $parts[0];
}
/*
    End: This function block ends for `mcw_remove_asset_query_strings`.
*/

add_filter('script_loader_src', 'mcw_remove_asset_query_strings', 15);
add_filter('style_loader_src', 'mcw_remove_asset_query_strings', 15);

/**
 * Auto-assign featured image from first content image if missing.
 */
/*
    Start: This function block starts for `mcw_auto_set_featured_image_from_content`.
*/
function mcw_auto_set_featured_image_from_content($post_id) {
    if (wp_is_post_revision($post_id) || wp_is_post_autosave($post_id)) {
        return;
    }

    if (has_post_thumbnail($post_id)) {
        return;
    }

    $post = get_post($post_id);
    if (!$post instanceof WP_Post || empty($post->post_content)) {
        return;
    }

    preg_match('/<img[^>]+src=["\']([^"\']+)["\']/i', $post->post_content, $matches);

    if (empty($matches[1])) {
        return;
    }

    $attachment_id = mcw_get_attachment_id_from_image_url($matches[1]);

    if (!empty($attachment_id)) {
        set_post_thumbnail($post_id, $attachment_id);
    }
}
/*
    End: This function block ends for `mcw_auto_set_featured_image_from_content`.
*/

add_action('save_post', 'mcw_auto_set_featured_image_from_content', 30);

/**
 * Add a custom REST field with reading-time estimate (minutes).
 */
/*
    Start: This function block starts for `mcw_register_reading_time_rest_field`.
*/
function mcw_register_reading_time_rest_field() {
    register_rest_field(
        array('post', 'page'),
        'reading_time_minutes',
        array(
            'get_callback' => 'mcw_get_reading_time_for_rest',
            'schema'       => array(
                'description' => __('Estimated reading time in minutes.', 'textdomain'),
                'type'        => 'integer',
                'context'     => array('view', 'edit'),
            ),
        )
    );
}
/*
    End: This function block ends for `mcw_register_reading_time_rest_field`.
*/

add_action('rest_api_init', 'mcw_register_reading_time_rest_field');

/**
 * Calculate reading-time estimate based on content length.
 */
/*
    Start: This function block starts for `mcw_get_reading_time_for_rest`.
*/
function mcw_get_reading_time_for_rest($post_arr) {
    $post_id = isset($post_arr['id']) ? absint($post_arr['id']) : 0;

    if (empty($post_id)) {
        return 0;
    }

    $content = get_post_field('post_content', $post_id);
    $word_count = str_word_count(wp_strip_all_tags($content));

    return max(1, (int) ceil($word_count / 220));
}
/*
    End: This function block ends for `mcw_get_reading_time_for_rest`.
*/

/**
 * Add a reusable shortcode for estimated reading time in current post.
 */
/*
    Start: This function block starts for `mcw_register_reading_time_shortcode`.
*/
function mcw_register_reading_time_shortcode() {
    add_shortcode('reading_time', 'mcw_render_reading_time_shortcode');
}
/*
    End: This function block ends for `mcw_register_reading_time_shortcode`.
*/

add_action('init', 'mcw_register_reading_time_shortcode');

/**
 * Render reading-time shortcode output.
 */
/*
    Start: This function block starts for `mcw_render_reading_time_shortcode`.
*/
function mcw_render_reading_time_shortcode() {
    $post_id = get_the_ID();

    if (empty($post_id)) {
        return '';
    }

    $minutes = mcw_get_reading_time_for_rest(array('id' => $post_id));
    return sprintf(esc_html__('%d min read', 'textdomain'), $minutes);
}
/*
    End: This function block ends for `mcw_render_reading_time_shortcode`.
*/

/**
 * Add security headers to frontend responses.
 */
/*
    Start: This function block starts for `mcw_add_basic_security_headers`.
*/
function mcw_add_basic_security_headers() {
    if (is_admin() || headers_sent()) {
        return;
    }

    header('X-Content-Type-Options: nosniff');
    header('X-Frame-Options: SAMEORIGIN');
    header('Referrer-Policy: strict-origin-when-cross-origin');
}
/*
    End: This function block ends for `mcw_add_basic_security_headers`.
*/

add_action('send_headers', 'mcw_add_basic_security_headers');


/**
 * Notes:
 * - This script moves all admin notifications to a separate menu page called "Notifications."
 * - The notifications are no longer displayed on other admin pages.
 * - Adjustments can be made to the menu position or title as needed.
 */

?>
