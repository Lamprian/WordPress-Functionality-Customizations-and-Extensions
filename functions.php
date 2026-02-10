 
 /**
  * Note:
  * This function does not return a specific value; it processes all images silently.
  * It can be extended to provide feedback or logging for improved monitoring.
  */
 
+/**
+ * Resolve admin logo URL with child-theme fallback support.
+ */
+function custom_admin_logo_image_url() {
+    $stylesheet_logo = trailingslashit(get_stylesheet_directory()) . 'images/admin_logo.png';
+
+    if (file_exists($stylesheet_logo)) {
+        return trailingslashit(get_stylesheet_directory_uri()) . 'images/admin_logo.png';
+    }
+
+    return trailingslashit(get_template_directory_uri()) . 'images/admin_logo.png';
+}
+
 /**
  * Function to replace the default WordPress admin logo with a custom logo.
  * The custom logo is loaded from the "images" folder of the active theme.
  * This is achieved by injecting custom CSS into the admin page header.
  */
 function custom_admin_logo() {
     echo '<style type="text/css"> 
     #header-logo { 
-        background-image: url(' . get_bloginfo('template_directory') . '/images/admin_logo.png) !important; 
+        background-image: url(' . esc_url(custom_admin_logo_image_url()) . ') !important; 
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
 
-<?php
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
 
+    if (!$post instanceof WP_Post) {
+        return $content;
+    }
+
     // Extract the first image from the post content
-    $output = preg_match('/< *img[^>]*src *= *["\']?([^"\']*)/i', $post->post_content, $matches);
+    preg_match('/< *img[^>]*src *= *["\']?([^"\']*)/i', $post->post_content, $matches);
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
@@ -154,161 +170,175 @@ add_filter('the_content_feed', 'mcw_featured_image_in_feeds');
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
-    if (!current_user_can('edit_posts')) {
+    if (!$post instanceof WP_Post) {
+        return $actions;
+    }
+
+    if (!current_user_can('edit_post', $post->ID)) {
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
 
+    if (!current_user_can('edit_post', $post_id)) {
+        wp_die(__('You are not allowed to duplicate this content.'));
+    }
+
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
 
+    if (is_wp_error($new_post_id) || !$new_post_id) {
+        wp_die(__('Post creation failed during duplication.'));
+    }
+
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
-            add_post_meta($new_post_id, $meta_key, $meta_value);
+            add_post_meta($new_post_id, $meta_key, maybe_unserialize($meta_value));
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
-    if (isset($_GET['duplicated']) && $_GET['duplicated'] === 'success') {
-        echo '<div class="notice notice-success is-dismissible"><p>' . __('Post duplicated successfully.') . '</p></div>';
+    $duplicated = isset($_GET['duplicated']) ? sanitize_text_field(wp_unslash($_GET['duplicated'])) : '';
+
+    if ($duplicated === 'success') {
+        echo '<div class="notice notice-success is-dismissible"><p>' . esc_html__('Post duplicated successfully.') . '</p></div>';
     }
 }
 
 /**
  * Note:
  * This function adds an admin notice to confirm successful duplication of a post or page.
  */
 
 /**
  * Function to create a new menu in the WordPress admin panel for displaying notifications.
  * This function moves all admin notifications to a separate page in the admin menu.
  */
 add_action('admin_menu', 'move_admin_notifications_to_menu');
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
@@ -327,46 +357,84 @@ function move_admin_notifications_to_menu() {
  * Callback function to display the content of the admin notifications page.
  * This function captures and displays all admin notifications.
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
 
 /**
  * Function to disable admin notifications from appearing on other admin pages.
  * This ensures notifications are only visible on the "Notifications" page.
  */
 add_action('in_admin_header', 'disable_admin_notifications_from_main_screen', 1);
 function disable_admin_notifications_from_main_screen() {
-    global $pagenow; // Access the current admin page.
-
     /**
      * Check if the current page is NOT the notifications page.
      * If true, remove all admin notifications.
      */
-    if (!isset($_GET['page']) || $_GET['page'] !== 'admin-notifications') {
+    $page = isset($_GET['page']) ? sanitize_key(wp_unslash($_GET['page'])) : '';
+
+    if ($page !== 'admin-notifications') {
         remove_all_actions('admin_notices'); // Remove regular admin notices.
         remove_all_actions('all_admin_notices'); // Remove additional admin notices.
     }
 }
 
+/**
+ * Add post update date in RSS feeds for better feed readability.
+ */
+function add_last_modified_date_to_feed($content) {
+    if (!is_feed()) {
+        return $content;
+    }
+
+    $modified = get_the_modified_date('c');
+
+    if (empty($modified)) {
+        return $content;
+    }
+
+    $date_markup = '<p><small>' . esc_html__('Last updated: ', 'textdomain') . esc_html(get_the_modified_date()) . '</small></p>';
+
+    return $date_markup . $content;
+}
+
+add_filter('the_excerpt_rss', 'add_last_modified_date_to_feed', 20);
+add_filter('the_content_feed', 'add_last_modified_date_to_feed', 20);
+
+/**
+ * Register a reusable shortcode for displaying the current year.
+ */
+function register_current_year_shortcode() {
+    add_shortcode('current_year', 'render_current_year_shortcode');
+}
+
+add_action('init', 'register_current_year_shortcode');
+
+/**
+ * Return the current year for shortcode output.
+ */
+function render_current_year_shortcode() {
+    return esc_html(wp_date('Y'));
+}
+
 /**
  * Notes:
  * - This script moves all admin notifications to a separate menu page called "Notifications."
  * - The notifications are no longer displayed on other admin pages.
  * - Adjustments can be made to the menu position or title as needed.
  */
 
 ?>
