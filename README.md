 # WordPress-Functionality-Customizations-and-Extensions
+
 WordPress Functionality Customizations and Extensions
- 
- Introduction
-   
-   This functions.php file is a dynamic and continuously evolving script for customizing WordPress functionality.
-   It serves as the backbone for adding bespoke features, optimizing processes, and adapting the WordPress environment
-   to specific project needs. The codebase within this file will be regularly updated and extended, allowing for the
-   seamless integration of new capabilities as requirements evolve.
-  
-   Key Objectives:
-  1. Enhance the flexibility and functionality of WordPress beyond its default behavior.
-  2. Include clean, well-documented code for ease of understanding, maintenance, and collaboration.
-  3. Provide a centralized location for managing all custom modifications and hooks.
- 
-Functions
 
-1) regenerateThumbnails: Regenerates thumbnails for all images in the WordPress media library.
+## Introduction
+
+This `functions.php` file is a dynamic and continuously evolving script for customizing WordPress functionality.
+It serves as the backbone for adding bespoke features, optimizing processes, and adapting the WordPress environment
+to specific project needs.
+
+## Key Objectives
+
+1. Enhance WordPress flexibility beyond its default behavior.
+2. Keep code clean and documented for easier maintenance and collaboration.
+3. Provide a centralized location for custom hooks, actions, and utilities.
 
-    Checks if the image files exist.
-    Uses WordPress image processing functions to regenerate thumbnails.
+## Functions
 
-2) custom_admin_logo: Replaces the default WordPress admin logo with a custom logo.
+1. **`regenerateThumbnails`**
+   - Regenerates thumbnails for all images in the WordPress media library.
+   - Checks if image files exist before processing.
 
-    Injects CSS into the admin panel.
-    Sets an image from the "images" folder of the active theme as the logo.
+2. **`custom_admin_logo_image_url` + `custom_admin_logo`**
+   - Resolves admin logo URL with child-theme fallback support.
+   - Replaces default WordPress admin logo via injected admin CSS.
 
-3) Adds an image to the RSS Feed from the post content:
+3. **`mcw_featured_image_in_feeds`**
+   - Adds the first content image (or featured image fallback) into RSS feed content.
+   - Includes a safe guard when global `$post` is unavailable.
 
-    Overrides the default RSS Feed behavior by adding images.
-    Detects the first image in the post content.
-    If no image is found in the content, it uses the post's featured image.
-    Enhances the aesthetics and functionality of the RSS Feed for applications displaying images.
+4. **Post duplication tools (`custom_duplicate_post_link`, `custom_duplicate_post_as_draft`)**
+   - Adds a "Duplicate" action for posts/pages/custom post types.
+   - Uses nonce verification and post-level capability checks.
+   - Duplicates core fields, taxonomies, and metadata.
+   - Handles insert failures safely.
 
-4) Adds a "Duplicate" link to the WordPress admin panel:
+5. **`custom_duplicate_admin_notice`**
+   - Shows success notice after duplication.
+   - Sanitizes query parameters before usage.
 
-   Allows users to easily duplicate posts, pages, and custom post types. Copies the original post's content, title, taxonomies, and metadata into a new draft. Securely handles duplication using nonce     verification. Displays a success message after duplication.
+6. **Admin notifications page (`move_admin_notifications_to_menu`, `admin_notifications_page`, `disable_admin_notifications_from_main_screen`)**
+   - Moves admin notices into a dedicated dashboard page.
+   - Sanitizes current page query checks before removing notice hooks.
 
-5) Adds a "Notifications" menu to the WordPress admin panel:
+7. **`add_last_modified_date_to_feed`**
+   - Prepends a "Last updated" date in RSS excerpt/content output.
 
-   Moves all admin notifications to a dedicated "Notifications" page in the WordPress dashboard. This ensures a cleaner admin interface by removing notifications from other admin pages. Captures all admin notices and additional notices using WordPress hooks. Displays the notifications in a structured way on the new "Notifications" page. The menu is positioned at the top of the admin menu for easy access. Includes proper capability checks to ensure only authorized users can view the page.
+8. **`register_current_year_shortcode` + `render_current_year_shortcode`**
+   - Registers `[current_year]` shortcode.
+   - Returns the current year using `wp_date('Y')`.
