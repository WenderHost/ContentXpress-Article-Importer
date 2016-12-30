<?php

function cxp_import_content(){
    if(
        ! isset( $_POST['_cxp_form_post_nonce'] )
        || ! wp_verify_nonce( $_POST['_cxp_form_post_nonce'], 'cxp_form_post' )
    ){
        wp_die( '[nonce error] You do not have permission to perform this operation.' );
    }

    global $BackgroundImageProcess;

    $count_articles = 0;
    $count_images = 0;
    if ( isset( $_POST ['importButton'] ) ) {
        ob_start();

        //upload article and images to Wordpress
        $count_articles = count( $_SESSION['importList'] );
        write_log( '-----------------------------------' );
        write_log( 'IMPORTING ' . $count_articles . ' ARTICLE(S)' );
        write_log( '-----------------------------------' );

        echo '<pre style="background: #eee; padding: 10px; max-width: 1200px; margin: 10px auto;">[ContentXpress] IMPORTING ' . $count_articles . ' ARTICLE(S):' . "\n\n";
        ob_flush();
        flush();

        foreach ($_SESSION['importList'] as $articleCid => $value) {
            write_log("\n\n");
            $article = CXPRequest::importArticle( $value );

            // If article has images, add a gallery to the top of the post
            if ( isset( $article->field_image ) )
                $article->body = "[gallery link=\"file\" type=\"slideshow\" autostart=\"false\"]\n" . $article->body;

            //create the initial post
            $post_id = WPActions::createPost( $article );
            echo 'ADDING POST `' . $article->title . '` (ID: ' . $post_id . ').' . "\n";
            ob_flush();
            flush();

            // Add any article images to our Background Image Processing queue
            if ( isset( $article->field_image ) ) {
                $message = 'Adding ' . count( $article->field_image ) . ' image(s) to Background Image Processing queue...';
                write_log( $message );
                echo $message . "\n";
                ob_flush();
                flush();

                for ( $j = 0; $j < sizeof( $article->field_image ); $j++) {
                    $image = [
                        'contentID' => $article->field_image[$j]['fid'],
                        'caption' => $article->field_image[$j]['caption'],
                        'post_id' => $post_id
                    ];
                    $image_message = 'Queuing $image = [ contentID => ' . $image['contentID'] . ', post_id => ' . $image['post_id'] . ' ]';
                    write_log( $image_message );
                    echo $image_message . "\n";
                    ob_flush();
                    flush();

                    $BackgroundImageProcess->push_to_queue( $image );
                    $count_images++;
                }
                $BackgroundImageProcess->save()->dispatch();
                $BackgroundImageProcess->empty_queue();
                echo "\n";
                ob_flush();
                flush();
            }

            unset($_SESSION['importList'][$articleCid]);
        }

        write_log( '-----------------------------------' . "\n\n" );
        echo "\n" . '[ContentXpress] ' . $count_images . ' IMAGES were queued for background processing.' . "\n";
        echo '------------- FINISHED! REDIRECTING... -------------</pre>' . "\n\n";
        ob_flush();
        flush();
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

    // Do a JS redirect if we've already sent
    // output to the browser
    if( isset( $_POST ['importButton'] ) ){
    ?>
<script type="text/javascript">
window.location.href = '<?= $page ?>';
</script>
    <?php
    } else {
        wp_redirect( admin_url( $page ) );
    }
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