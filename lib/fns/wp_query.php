<?php

namespace contentXpressImporter\lib\fns\wp_query;

/**
 * Adds a `post_title_like` query variable to WP_Query
 *
 * Thanks to [Stack Exchange](http://wordpress.stackexchange.com/a/22961/108021).
 *
 * @global obj $wpdb WordPress database object.
 *
 * @since x.x.x
 *
 * @param str $where MySQL `WHERE` clause passed from $wp_query.
 * @param obj $wp_query WP_Query object.
 * @return str Modified MySQL `WHERE` clause.
 */
function title_like_posts_where( $where, $wp_query ) {
    global $wpdb;
    if ( $post_title_like = $wp_query->get( 'post_title_like' ) ) {
        $where .= ' AND ' . $wpdb->posts . '.post_title LIKE \'%' . esc_sql( $wpdb->esc_like( $post_title_like ) ) . '%\'';
    }
    return $where;
}
add_filter( 'posts_where', __NAMESPACE__ . '\\title_like_posts_where', 10, 2 );
