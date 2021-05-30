<?php 

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

define('WP_POST_REVISIONS', 0);
require "wp-load.php";
date_default_timezone_set('Europe/Istanbul');

try {
    $db = new PDO("mysql:host=localhost;dbname=" . DB_NAME, DB_USER, DB_PASSWORD);
} catch ( PDOException $e ){
    print $e->getMessage();
}

function post($type, $title, $content, $permalink, $category_id, $image = false){
    global $wpdb;

    $new_post = array(
        'post_title' => $title,
        'post_content' => $content,
        'post_status' => 'publish',
        'post_date' => date('Y-m-d H:i:s') ,
        'post_author' => 1,
        'post_type' => $type,
        'post_category' => array($category_id),
        'post_name' => $permalink
    );

    $query = $wpdb->prepare('SELECT ID FROM ' . $wpdb->posts . ' WHERE post_title = %s', $title);
    $wpdb->query($query);

    if ($wpdb->num_rows){
        $post_id = $wpdb->get_var($query);
        $meta = get_post_meta($post_id, 'times', true);
        $meta++;
        update_post_meta($post_id, 'times', $meta);
    
        $new_post["ID"] = $post_id;
        $result = wp_update_post($new_post);
    
    }else{
        $post_id = wp_insert_post($new_post);
        $result = add_post_meta($post_id, 'times', '1');
    }

    /* UPLOAD IMAGE */
    if($image){
        $file = $image;
        if (@file_get_contents($file))
        {
            $filename = basename($file);

            $upload_file = wp_upload_bits($filename, null, file_get_contents($file));
            if (!$upload_file['error'])
            {
                $wp_filetype = wp_check_filetype($filename, null);
                $attachment = array(
                    'post_mime_type' => $wp_filetype['type'],
                    'post_parent' => $post_id,
                    'post_title' => preg_replace('/\.[^.]+$/', '', $filename) ,
                    'post_content' => '',
                    'post_status' => 'inherit',
                    'post_type' => $type
                );
                $attachment_id = wp_insert_attachment($attachment, $upload_file['file'], $post_id);
                if (!is_wp_error($attachment_id)){
                    require_once (ABSPATH . "wp-admin" . '/includes/image.php');
                    $attachment_data = wp_generate_attachment_metadata($attachment_id, $upload_file['file']);
                    wp_update_attachment_metadata($attachment_id, $attachment_data);
                }
            }

            set_post_thumbnail($post_id, $attachment_id);
        }
    }
    /* UPLOAD IMAGE */

}






