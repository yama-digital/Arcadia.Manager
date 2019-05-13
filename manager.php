<?php
/**
 * Plugin Name: Sandhills Studio Manager
 * Description: A management plugin for the websites created, build, modified or managed by Sandhills Studio
 * Plugin URI: https://www.sandhillsstudio.com/
 * Author: Sandhills Studio
 * Version: 1.0
 * Author URI: https://www.sandhillsstudio.com/
 *
 * Text Domain: sandhills-studio
 */
if(!defined('ABSPATH')){exit;}
//Login Customisation
function shs_url_login_logo(){return "https://www.sandhillsstudio.com/";}
add_filter('login_headerurl','shs_url_login_logo');
function shs_logo_title(){return 'Sandhills Studio';}
add_filter('login_headertitle','shs_logo_title');
function shs_login_stylesheet(){wp_enqueue_style('shs-login',plugin_dir_url(__FILE__).'/admin/shs-login.css');}
add_action('login_enqueue_scripts','shs_login_stylesheet');
function shs_footer_admin(){echo 'Managed by <a href="https://www.sandhillsstudio.com/" target="_blank">Sandhills Studio</a> and powered by <a href="https://wordpress.org" target="_blank">WordPress</a>.';}
add_filter('admin_footer_text','shs_footer_admin');
//Ng Remover
add_action("admin_head","ng");
function ng(){echo base64_decode("PHN0eWxlPi5ub3RpY2UuZWxlbWVudG9yLW1lc3NhZ2UsLm5vdGljZS1pbmZvLCNlbnRlci1saWNlbnNlLWJkdGhlbWVzLWVsZW1lbnQtcGFjaywuZWxlbWVudG9yLXBsdWdpbnMtZ29wcm8sLm5vdGljZS1lcnJvcntkaXNwbGF5Om5vbmV9PC9zdHlsZT4=");}
//Add SVG Upload Support
add_filter('upload_mimes','cc_mime_types');
function cc_mime_types($mimes){
	$mimes['svg'] = 'image/svg+xml';
	return $mimes;
}
add_filter('wp_update_attachment_metadata','svg_meta_data',10,2);
function svg_meta_data($data,$id){
	$attachment = get_post($id);
	$mime_type = $attachment->post_mime_type;
	if($mime_type == 'image/svg+xml'){
		if(empty($data) || empty($data['width']) || empty($data['height'])){
			$xml = simplexml_load_file(wp_get_attachment_url($id));
			$attr = $xml->attributes();
			$viewbox = explode(' ',$attr->viewBox);
			$data['width'] = isset($attr->width) && preg_match('/\d+/',$attr->width,$value) ? (int) $value[0] :(count($viewbox) == 4 ? (int) $viewbox[2] :null);
			$data['height'] = isset($attr->height) && preg_match('/\d+/',$attr->height,$value) ? (int) $value[0] :(count($viewbox) == 4 ? (int) $viewbox[3] :null);
		}
	}
	return $data;
}
//Image SEO Optimizer
add_action('add_attachment','image_meta_upload');
function image_meta_upload($post_ID){
	if(wp_attachment_is_image($post_ID)){
		$image_title = get_post($post_ID)->post_title;
		$image_title = preg_replace('%\s*[-_\s]+\s*%',' ',$image_title);
		$image_title = ucwords(strtolower($image_title));
		$image_meta = array(
			'ID'				=> $post_ID,
			'post_title'		=> $image_title
		);
		update_post_meta($post_ID,'_wp_attachment_image_alt',$image_title);
		wp_update_post($image_meta);
	}
}
//Remove Elements Admin Bar
function remove_admin_bar_links(){
	global $wp_admin_bar;
	$wp_admin_bar->remove_node('wp-logo');
	$wp_admin_bar->remove_node('about');
	$wp_admin_bar->remove_node('wporg');
	$wp_admin_bar->remove_node('documentation');
	$wp_admin_bar->remove_node('support-forums');
	$wp_admin_bar->remove_node('feedback');
	$wp_admin_bar->remove_node('comments');
	$wp_admin_bar->remove_node('search');
}
add_action('wp_before_admin_bar_render','remove_admin_bar_links',999);
//Hide Managing Plugins
function hide_manager(){
	global $wp_list_table;
	$hidearr=array('worker/init.php','sandhills-studio-manager/manager.php');
	$myplugins=$wp_list_table->items;
	foreach($myplugins as $key => $val){if(in_array($key,$hidearr)){unset($wp_list_table->items[$key]);}}
}
add_action('pre_current_active_plugins','hide_manager');
