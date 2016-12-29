<?php
/**
 * Plugin Name: ContentXpress Article Importer
 * Description: This Plugin imports articles from the ContentXpress. {latest_commit}
 * Version: {version}
 * Author: Publishers Printing Company
 * Author URI: http://www.pubpress.com/services/pubpress-solutions/contentxpress
 * Network: true
 * License: GPL2
 */

// Initialize Background Image Processing
require_once ( plugin_dir_path( __FILE__ ) . 'lib/classes/wp-async-request.php' );
require_once ( plugin_dir_path( __FILE__ ) . 'lib/classes/wp-background-process.php' );
require_once ( plugin_dir_path( __FILE__ ) . 'lib/classes/background-image-process.php' );
$BackgroundImageProcess = new CXP_Background_Image_Process();

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

// Load misc functions
require_once ( plugin_dir_path( __FILE__ ) . 'lib/fns/custom_taxonomy.php' );
require_once ( plugin_dir_path( __FILE__ ) . 'lib/fns/settings.php' );
require_once ( plugin_dir_path( __FILE__ ) . 'lib/fns/wp_query.php' );


if ( true == WP_DEBUG ) {
    Logger::enable( true );
} else {
    Logger::disable();
}

function CXPImport_init()
{
    wp_enqueue_style('CXPStyleSheet', plugins_url('style.css', __FILE__));
    wp_register_script('contentView', plugins_url('contentView.js', __FILE__), array('jquery'), filemtime( plugin_dir_path( __FILE__ ) . 'contentView.js' ), true );
    wp_localize_script( 'contentView', 'contentViewAjax', array( 'ajax_url' => admin_url( 'admin-ajax.php' )));
    wp_enqueue_script('jQuery');
    wp_enqueue_script('contentView');
}

add_action('admin_init', 'CXPImport_init');
add_action( 'admin_menu', 'contentXpress' );

function contentXpress()
{
    $menu_slug = 'contentxpress'; // $menu_slug = 'contentXpress';

    // add_menu_page( $page_title, $menu_title, $capability, $menu_slug, $function, $icon_url, $position );
    add_menu_page( 'ContentXpress', 'ContentXpress', 'edit_posts', $menu_slug, 'content_submenu_page_callback', plugin_dir_url(__FILE__) . 'images/coLogo20px.png', '24.1' );
    add_submenu_page( $menu_slug, 'Import Content', 'Import', 'edit_posts', $menu_slug, 'content_submenu_page_callback');

    // add_submenu_page( $parent_slug, $page_title, $menu_title, $capability, $menu_slug, $function );
    add_submenu_page(
        $menu_slug,
        'Settings',
        'Settings',
        'edit_posts',
        'cxp-settings',
        'CXP\lib\fns\settings\settings_page_callback'
    );
}

// Check if user is logged in when clicking View Content tab. If they are not, redirect to the login page.
function logged_in_user_check()
{
    global $pagenow;
    $page = isset( $_REQUEST['page'] ) ? $_REQUEST['page'] : false ;
    if ( $pagenow == 'admin.php' && ( $page == 'contentxpress' ) ) {
        //$cxpuser = CXPRequest::contentXpressSessionGet("cxpuser");

        $success = cxp_login();
        if ( false == $success ) {
            add_settings_error( 'invalidLogin', 'invalid', 'Your login credentials did not work. Please update them.', 'error' );
            wp_redirect('admin.php?page=cxp-settings');
            exit;
        }
    }
    /*
    elseif ($pagenow == 'admin.php' && ($page == 'settings')) {
        $cxpuser = CXPRequest::contentXpressSessionGet("cxpuser");
        if (!empty($cxpuser)) {
            wp_redirect('admin.php?page=contentxpress');
            exit;
        }
    }
    /**/
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