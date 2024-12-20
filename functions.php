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

?>
