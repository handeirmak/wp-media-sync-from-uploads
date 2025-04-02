<?php

/**
 * Plugin Name: WP Media Sync from Uploads
 * Description: Scans the wp-content/uploads folder and imports all found files into the WordPress media library.
 * Version: 1.0
 * Author: handeirmak
 * License: MIT
 */

add_action('admin_menu', function () {
  add_menu_page('Media Sync', 'Media Sync', 'manage_options', 'media-importer', 'custom_media_importer');
});

function custom_media_importer()
{
  echo '<div class="wrap"><h1>Sync Media from Uploads Folder</h1>';

  $upload_dir = wp_upload_dir();
  $base = $upload_dir['basedir'];
  $files = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($base));

  $imported = 0;

  foreach ($files as $file) {
    if ($file->isDir()) continue;

    $filepath = $file->getRealPath();
    $url = str_replace($base, $upload_dir['baseurl'], $filepath);

    // Check if already imported
    $exists = attachment_url_to_postid($url);
    if ($exists) continue;

    $filetype = wp_check_filetype(basename($filepath), null);
    $wp_filetype = $filetype['type'];

    $attachment = array(
      'guid' => $url,
      'post_mime_type' => $wp_filetype,
      'post_title' => preg_replace('/\.[^.]+$/', '', basename($filepath)),
      'post_status' => 'inherit'
    );

    $attach_id = wp_insert_attachment($attachment, $filepath);
    require_once(ABSPATH . 'wp-admin/includes/image.php');
    $attach_data = wp_generate_attachment_metadata($attach_id, $filepath);
    wp_update_attachment_metadata($attach_id, $attach_data);

    echo "<p>✔️ Imported: " . basename($filepath) . "</p>";
    $imported++;
  }

  echo "<p><strong>$imported files imported successfully.</strong></p></div>";
}
