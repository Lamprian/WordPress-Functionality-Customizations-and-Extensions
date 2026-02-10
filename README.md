# WordPress-Functionality-Customizations-and-Extensions

WordPress Functionality Customizations and Extensions

## Introduction

This `functions.php` file is a dynamic and continuously evolving script for customizing WordPress functionality.
It serves as the backbone for adding bespoke features, optimizing processes, and adapting the WordPress environment
to specific project needs.

## Key Objectives

1. Enhance WordPress flexibility beyond its default behavior.
2. Keep code clean and documented for easier maintenance and collaboration.
3. Provide a centralized location for custom hooks, actions, and utilities.

## Functions

1. **`regenerateThumbnails`**
   - Regenerates thumbnails for all images in the WordPress media library.
   - Checks if image files exist before processing.

2. **`custom_admin_logo_image_url` + `custom_admin_logo`**
   - Resolves admin logo URL with child-theme fallback support.
   - Replaces default WordPress admin logo via injected admin CSS.

3. **`mcw_get_attachment_id_from_image_url` + `mcw_featured_image_in_feeds`**
   - Resolves attachment IDs from image URLs, including fallback for resized image file names (e.g. `-300x200`).
   - Adds the first content image (or featured image fallback) into RSS feed content.
   - Includes a safe guard when global `$post` is unavailable.

4. **Post duplication tools (`custom_duplicate_post_link`, `custom_duplicate_post_as_draft`)**
   - Adds a "Duplicate" action for posts/pages/custom post types.
   - Uses nonce verification, sanitized query input handling, and post-level capability checks.
   - Duplicates core fields, taxonomies, and metadata.
   - Handles insert failures safely.

5. **`custom_duplicate_admin_notice`**
   - Shows success notice after duplication.
   - Sanitizes query parameters before usage.

6. **Admin notifications page (`move_admin_notifications_to_menu`, `admin_notifications_page`, `disable_admin_notifications_from_main_screen`)**
   - Moves admin notices into a dedicated dashboard page.
   - Sanitizes current page query checks before removing notice hooks.

7. **`add_last_modified_date_to_feed`**
   - Prepends a "Last updated" date in RSS excerpt/content output.

8. **`register_current_year_shortcode` + `render_current_year_shortcode`**
   - Registers `[current_year]` shortcode.
   - Returns the current year using `wp_date('Y')`.
