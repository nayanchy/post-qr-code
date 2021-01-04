<?php 
/*
Plugin Name: Post QR Code
Plugin URI: https://nayan.host24u.com
Description: Auto Generate QR Code for posts of wordpress.
Author: Nayan Chowdhury
Author URI: https://nayan.host24u.com
License: GPLv2 or later
Text Domain: postqrcode
*/

$pqrc_countries = ['Afghanistan', 'Australia', 'Bangladesh', 'England', 'India', 'New Zealand', 'Pakistan', 'South Africa', 'Srilanka', 'West Indies', 'Zimbabwe'];

function pqrc_init(){
    global $pqrc_countries;
    $pqrc_countries = apply_filters('pqrc_country', $pqrc_countries);
}
add_action ('init', 'pqrc_init');


//Loading the text domain for translation
function pqrc_load_text_domain(){
    load_textdomain('postqrcode', false, dirname(__FILE__). '/languages');
}

// Callback function for frontend display hook (the_content)
function pqrc_display_qr_code( $content ) {
    $current_post_id    = get_the_ID();
    $current_post_title = get_the_title( $current_post_id );
    $current_post_url   = urlencode( get_the_permalink( $current_post_id ) );
    $current_post_type  = get_post_type( $current_post_id );

    // Post Type Check
    $excluded_type = get_option('pqrc_select');
    $post_type = [];

    // Checking condition for 'None' selected as Excluded Post Type and assigining value to $post_type based on condition.
    if($excluded_type !== 'None'){
        $post_type[] = $excluded_type;
    }else{
        $post_type[]= '';
    }

    // If Selected post type is in array of $post_type then exclude it and just return the $content.
    $excluded_post_types = apply_filters( 'pqrc_excluded_post_types', $post_type );
    if ( in_array( $current_post_type, $excluded_post_types ) ) {
        return $content;
    }

    //Dimension Hook
    $height = get_option('pqrc_height');
    $width = get_option('pqrc_width');

    $height = $height ? $height: 180;
    $width  = $width ? $width : 180;

    $dimension = apply_filters( 'pqrc_qrcode_dimension', "{$width}x{$height}" );

    //Image Attributes
    $image_attributes = apply_filters('pqrc_image_attributes',null);

    $image_src = sprintf( 'https://api.qrserver.com/v1/create-qr-code/?size=%s&ecc=L&qzone=1&data=%s', $dimension, $current_post_url );
    $content   .= sprintf( "<div class='qrcode'><img %s  src='%s' alt='%s' /></div>",$image_attributes, $image_src, $current_post_title );

    return $content;
}

add_filter( 'the_content', 'pqrc_display_qr_code', 12 );

function pqrc_admin_init(){
    // Adding Section On Settings > General
    add_settings_section('pqrc_section', __('QR Code Settings', 'postqrcode'), 'pqrc_display_section', 'general');
    
    // Adding Fields for Height and Width Settings
    add_settings_field('pqrc_height', __('QR Code Height', 'postqrcode'), 'pqrc_display_field', 'general', 'pqrc_section', array('pqrc_height'));
    add_settings_field('pqrc_width', __('QR Code Width', 'postqrcode'), 'pqrc_display_field', 'general', 'pqrc_section', array('pqrc_width'));
    
    // Adding Field for Exclude Post Type Setting
    add_settings_field('pqrc_select', __('Excluded Post Type', 'postqrcode'), 'pqrc_display_dropdown', 'general', 'pqrc_section');

    // Adding Checkbox field for multiple Post Type exclusion
    add_settings_field('pqrc_checkbox',__('Select the post types to exclude', 'postqrcode'), 'pqrc_display_checkbox', 'general', 'pqrc_section');

    // Registering the Section and Fields
    register_setting('general', 'pqrc_height', array('sanitize_callback' => 'esc_attr'));
    register_setting('general', 'pqrc_width', array('sanitize_callback' => 'esc_attr'));
    register_setting('general', 'pqrc_select', array('sanitize_callback' => 'esc_attr'));

    // Registering the checkbox field
    register_setting('general','pqrc_checkbox');
}

// Callback function to select the checkbox
function pqrc_display_checkbox(){
    global $pqrc_countries;
    $option = get_option('pqrc_checkbox');
    // if(!$option){
    //     $option = [];
    // }

    

    foreach ($pqrc_countries as $country){
        $selected = '';
        if(is_array($option) && in_array($country, $option)){
            $selected = 'checked';
        }
        printf('<input type="checkbox" name="pqrc_checkbox[]" value="%s" %s /> %s <br />', $country , $selected, $country);
    }
}


// Callback Function for Selecting Post Type to exclude
function pqrc_display_dropdown(){
    $option = get_option('pqrc_select');
    $post_types = get_post_types();
    
    printf('<select id="%s" name="%s">','pqrc_select', 'pqrc_select');
    ($option == 'none') ? $selected = 'selected' : $selected = '';
    printf('<option value ="none" %s>None</option>', $selected);
    foreach($post_types as $post_type){
        ($option == $post_type) ? $selected = 'selected' : $selected = '';
        printf('<option value="%s" %s>%s</option>', $post_type, $selected, $post_type);
    }
    printf('</select>');
}

// Callback function for Height and Width field
function pqrc_display_field($args){
    $option = get_option($args[0]);
    printf('<input type="text" id="%s" name="%s" value="%s">', $args[0], $args[0], $option);
}

// Callback function for Section
function pqrc_display_section(){
    printf('<p>'.__('This is the settings section for Post to QR Code plugin', 'postqrcode').'</p>');
}

// Action Hook for admin settings
add_action ('admin_init', 'pqrc_admin_init');

function pqrc_admin_assets($hook){
    if($hook == 'options-general.php'){
        wp_enqueue_script('pqrc-main-js', plugin_dir_url(__FILE__).'/assets/js/pqrc_main.js', array('jquery'), time(), true);
        wp_enqueue_script('pqrc-mini-toggle-js', plugin_dir_url(__FILE__).'/assets/js/script.js', array('jquery'), time(), true);
        wp_enqueue_style('pqrc-mini-toggle-css', plugin_dir_url(__FILE__).'/assets/css/minitoggle.css', null, time());
    }
}
add_action('admin_enqueue_scripts', 'pqrc_admin_assets');