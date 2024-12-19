<?php

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

