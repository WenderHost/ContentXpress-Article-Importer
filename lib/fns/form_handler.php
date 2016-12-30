<?php

function cxp_import_content(){
    global $BackgroundImageProcess;

    $count_articles = 0;
    if ( isset( $_POST ['importButton'] ) ) {
        //upload article and images to Wordpress
        $count_articles = count( $_SESSION['importList'] );
        write_log( '-----------------------------------' );
        write_log( 'IMPORTING ' . $count_articles . ' ARTICLE(S)' );
        write_log( '-----------------------------------' );

        foreach ($_SESSION['importList'] as $articleCid => $value) {
            write_log("\n\n");
            $article = CXPRequest::importArticle( $value );

            // If article has images, add a gallery to the top of the post
            if ( isset( $article->field_image ) )
                $article->body = "[gallery link=\"file\" type=\"slideshow\" autostart=\"false\"]\n" . $article->body;

            //create the initial post
            $post_id = WPActions::createPost( $article );

            // Add any article images to our Background Image Processing queue
            if ( isset( $article->field_image ) ) {
                write_log( 'Adding ' . count( $article->field_image ) . ' image(s) to Background Image Processing queue...' );
                for ( $j = 0; $j < sizeof( $article->field_image ); $j++) {
                    $image = [
                        'contentID' => $article->field_image[$j]['fid'],
                        'caption' => $article->field_image[$j]['caption'],
                        'post_id' => $post_id
                    ];
                    write_log( 'Queuing $image = [ contentID => ' . $image['contentID'] . ', post_id => ' . $image['post_id'] . ' ]' );
                    $BackgroundImageProcess->push_to_queue( $image );
                }
                $BackgroundImageProcess->save()->dispatch();
                $BackgroundImageProcess->empty_queue();
            }

            unset($_SESSION['importList'][$articleCid]);
        }

        write_log( '-----------------------------------' . "\n\n" );
    }

    $allowed_query_vars = [
        'publicationsSelect',
        'issuesSelect',
        'sectionsSelect',
        'keywordsInput',
        'newPageLengthSelect',
        'platformSelect',
        'sortBySelect',
        'publishedSelect',
        'newStart',
        'postTypesAll',
    ];

    $query_args = array();
    foreach( $allowed_query_vars as $var ){
        if( isset( $_POST[$var] ) && ! empty( $_POST[$var] ) ){
            $query_args[] = $var . '=' . urlencode( $_POST[$var] );
        }
    }

    if( 0 < $count_articles )
        $query_args[] = 'articles_imported=' . $count_articles;

    $page = 'admin.php?page=contentxpress';
    if( 0 < count( $query_args ) ){
        $page.= '&' . implode( '&', $query_args );
    }

    wp_redirect( admin_url( $page ) );
    exit;
}
add_action( 'admin_post_cxp_form', 'cxp_import_content' );

function cxp_import_content_admin_notice(){
    if( ! isset( $_GET['articles_imported'] ) )
        return;

    ?>
<div class="notice notice-success is-dismissible">
    <p>[ContentXpress] <?= $_GET['articles_imported'] ?> articles were imported.</p>
</div>
    <?php
}
add_action( 'admin_notices', 'cxp_import_content_admin_notice' );