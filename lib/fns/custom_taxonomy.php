<?php

namespace contentXpressImporter\lib\fns\custom_taxonomy;

function create_issue_taxonomy(){
    // Add new taxonomy, make it hierarchical (like categories)
    $labels = array(
        'name'              => _x( 'Issues', 'taxonomy general name', 'contentxpress' ),
        'singular_name'     => _x( 'Issue', 'taxonomy singular name', 'contentxpress' ),
        'search_items'      => __( 'Search Issues', 'contentxpress' ),
        'all_items'         => __( 'All Issues', 'contentxpress' ),
        'parent_item'       => __( 'Parent Issue', 'contentxpress' ),
        'parent_item_colon' => __( 'Parent Issue:', 'contentxpress' ),
        'edit_item'         => __( 'Edit Issue', 'contentxpress' ),
        'update_item'       => __( 'Update Issue', 'contentxpress' ),
        'add_new_item'      => __( 'Add New Issue', 'contentxpress' ),
        'new_item_name'     => __( 'New Issue Name', 'contentxpress' ),
        'menu_name'         => __( 'Issue', 'contentxpress' ),
    );

    $args = array(
        'hierarchical'      => true,
        'labels'            => $labels,
        'show_ui'           => true,
        'show_admin_column' => true,
        'query_var'         => true,
        'rewrite'           => array( 'slug' => 'issue' ),
    );

    register_taxonomy( 'issue', array( 'post' ), $args );
}
add_action( 'init', __NAMESPACE__ . '\\create_issue_taxonomy' );