<?php
/**
 * Plugin Name: ContentXpress Article Importer
 * Description: This Plugin imports articles from the ContentXpress.
 * Version: 2.1.0
 * Author: Publishers Printing Company
 * Author URI: http://www.pubpress.com/services/pubpress-solutions/contentxpress
 * Network: true
 * License: GPL2
 */

require_once (dirname(__FILE__) . '/preview.php');
require_once (dirname(__FILE__) . '/ArticleToImport.php');
require_once (dirname(__FILE__) . '/contentStoreList.php');
require_once (dirname(__FILE__) . '/contentView.php');
require_once (dirname(__FILE__) . '/cxpRequest.php');
require_once (dirname(__FILE__) . '/httpUtils.php');
require_once (dirname(__FILE__) . '/logger.php');
require_once (dirname(__FILE__) . '/loginController.php');
require_once (dirname(__FILE__) . '/redirects.php');
require_once (dirname(__FILE__) . '/wp_actions.php');
require_once ( plugin_dir_path( __FILE__ ) . 'lib/fns/wp_query.php' );


if ( true == WP_DEBUG ) {
    Logger::enable( true );
} else {
    Logger::disable();
}

function CXPImport_init()
{
    wp_enqueue_style('CXPStyleSheet', plugins_url('style.css', __FILE__));
    wp_register_script('contentView', plugins_url('contentView.js', __FILE__), array('jquery'), '1.0', true );
    wp_localize_script( 'contentView', 'contentViewAjax', array( 'ajax_url' => admin_url( 'admin-ajax.php' )));
    wp_enqueue_script('jQuery');
    wp_enqueue_script('contentView');
}

add_action('admin_init', 'CXPImport_init');
add_action('admin_menu', 'contentXpress', 'cxp_ob_start');

function contentXpress()
{
    $page_title = 'ContentXpress';
    $menu_title = 'ContentXpress';
    $capability = 'edit_posts';
    $menu_slug = 'contentXpress';
    $function = 'cxppage_submenu_callback';
    $icon_url = plugin_dir_url(__FILE__) . "images/coLogo20px.png";
    $position = '24.1';

    add_menu_page($page_title, $menu_title, $capability, $menu_slug, $function, $icon_url, $position);
    add_submenu_page($menu_slug, 'Content', 'Content', 'edit_posts', 'content', 'content_submenu_page_callback');
}

//Check if user is logged in when clicking View Content tab. If they are not, redirect to the login page.
function logged_in_user_check()
{
    global $pagenow;
    $page = (isset($_REQUEST['page']) ? $_REQUEST['page'] : false);
    if ($pagenow == 'admin.php' && ($page == 'content')) {
        $cxpuser = CXPRequest::contentXpressSessionGet("cxpuser");
        if (empty($cxpuser)) {
            wp_redirect('admin.php?page=contentXpress');
            exit;
        }
    }
    elseif ($pagenow == 'admin.php' && ($page == 'contentXpress')) {
        $cxpuser = CXPRequest::contentXpressSessionGet("cxpuser");
        if (!empty($cxpuser)) {
            wp_redirect('admin.php?page=content');
            exit;
        }
    }
}


add_action('admin_init', 'logged_in_user_check');

function contentXpress_output_buffer()
{
    ob_start();
}

add_action('init', 'contentXpress_output_buffer');

add_action('wp_ajax_contentXpressImporter_contentStoreList', 'updateImportList');

add_action('wp_ajax_contentXpressImporter_defaultPostType', 'setDefaultPostType');

ini_set('session.save_path', '/tmp');

class loginController
{

}