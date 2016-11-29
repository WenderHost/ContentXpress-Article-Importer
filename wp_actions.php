<?php
//http://codex.wordpress.org/Function_Reference/wp_insert_post
//http://codex.wordpress.org/get_currentuserinfo
//http://codex.wordpress.org/Function_Reference/get_post_status
class WPActions
{

    // Creates a new post or updates a post based on duplicate title
    // if title is missing from CXP dc:identifier will be used instead
    public static function createPost($article)
    {
        $post_id = null;
        $title = $article->title;
        $content = $article->body;
        $tagsArray = $article->field_tags;
        $identifier = $article->identifier;
        $date = $article->date;
        $publish = $article->status;
        $postType = $article->post;

        // Add the article author as a WordPress user
        $authorName = $article->author;
        $authorUsername = WPActions::getAuthorUsername( $authorName );
        $authorEmail = $authorUsername . '@example.com';

        $user_id = email_exists( $authorEmail );
        if( ! $user_id ){
            $user_id = wp_create_user( $authorUsername, wp_generate_password(), $authorEmail );


            if( is_wp_error( $user_id ) ){
                // if no user exists, default to current WP user
                $user_id = get_current_user_id();
            } else {
                // if we've created a user, update this user's meta
                $name = explode( ' ', $authorName );
                $last_name = array_pop( $name );
                update_user_meta( $user_id, 'last_name', $last_name );
                update_user_meta( $user_id, 'first_name', implode( ' ', $name ) );
                update_user_meta( $user_id, 'nickname', $authorName );
                wp_update_user( ['ID' => $user_id, 'display_name' => $authorName] );
            }
        }

        // Create post object
        $cxp_post = array(
            'post_title' => !empty($title) ? $title : $identifier,
            'post_content' => $content,
            'post_status' => $publish ? 'publish' : 'draft',
            'post_type' => $postType,
            'post_author' => $user_id,
            'post_category' => array(1), // (1) Default: Uncategorized
        );

        if (!is_null($tagsArray))
            $cxp_post['tags_input'] = $tagsArray;

        if (!is_null($date))
            $cxp_post['post_date'] = $date; //cxp format 2011-09-27 wp format [ Y-m-d H:i:s ]

        $args = array(
            'posts_per_page' => -1,
            'offset' => 0,
            'category' => '',
            'category_name' => '',
            'orderby' => 'date',
            'order' => 'DESC',
            'include' => '',
            'exclude' => '',
            'meta_key' => '',
            'meta_value' => '',
            'post_type' => $postType,
            'post_mime_type' => '',
            'post_parent' => '',
            'author' => '',
            'post_status' => 'any',
            'suppress_filters' => true
        );



        // lesson learned query_posts('ASC') is bad
        foreach (get_posts($args) as $post) {
            //Logger::log(get_class() . __METHOD__, 'Post Title: ' . $post->post_title . ' Ident: ' . $identifier . ' Title: ' . $title, false);
            if ($post->post_title == $identifier || $post->post_title == $title) {
                $post_id = $post->ID;
                break;
            }
        }

        if (!is_null($post_id)) {
            WPActions::updatePost($post_id, $cxp_post);
            //Logger::log(get_class() . __METHOD__, 'Updated Post: ' . $post_id, false);
        } else {
            $post_id = wp_insert_post($cxp_post);
            //Logger::log(get_class() . __METHOD__, 'Created Post: ' . $post_id, false);
            //Logger::log(get_class() . __METHOD__, '<pre>$article = ' . print_r( $article, true ) . '</pre>', false);
        }

        return $post_id;
    }

    // Creates a new term in WP. Can be category or tag
    public static function createTerm($term, $taxonomy, $args)
    {
        $args = array(
            'name' => $term,
            'taxonomy' => $taxonomy,
            //'slug'			=>
            //'description'	=>
            //'parent'	=>
        );

        wp_insert_term($term, $taxonomy);
    }

    /**
     * Given a string, returns a username
     *
     * Feed this function a person's name, and it
     * will return that name with spaces and periods
     * removed and all characters converted to lower
     * case.
     *
     * @see str_replace, strtolower
     *
     * @since x.x.x
     *
     * @param str $string String.
     * @return str Username
     */
    public static function getAuthorUsername( $string ){
        if( empty( $string) )
            return false;

        $search = array( ' ', '.' );
        $username = strtolower( str_replace($search, '', $string) );

        return $username;
    }

    private static function updatePost($postID, $my_post)
    {
        $my_post['ID'] = $postID;

        wp_update_post($my_post);
    }

    //http://codex.wordpress.org/Function_Reference/wp_upload_dir
    //http://codex.wordpress.org/Function_Reference/wp_mkdir_p
    //http://codex.wordpress.org/Function_Reference/wp_insert_attachment
    //images are not currently overriden
    public static function uploadMedia($image, $caption, $post_id)
    {
        $upload_dir = wp_upload_dir();
        $filename = basename($image['filename']);
        $filepath = $upload_dir['path'] . '/' . $filename;
        $file = null;
        $media = null;
        $attach_id = null;

        if (wp_mkdir_p($upload_dir['path'])) {
            $file = $upload_dir['path'] . '/' . $filename;
        } else {
            //Check basedir to see if file exists
            $file = $upload_dir['basedir'] . '/' . $filename;
        }

        if (!file_exists($file)) {
            $file_created = file_put_contents($file, $image['file']);

            if ($file_created === false) {
                throw new Exception('File could not be created.');
            }

            $wp_filetype = wp_check_filetype($filename, null);
            $attachment = array(
                'guid' => wp_upload_dir()['url'] . '/' . $filename,
                'post_mime_type' => $wp_filetype['type'],
                'post_title' => $filename,
                'post_status' => 'inherit',
                'post_excerpt' => $caption,
                'post_content' => ''
            );

            $attach_id = wp_insert_attachment($attachment, $filepath, $post_id);
            $attach_data = wp_generate_attachment_metadata($attach_id, $filepath);
            wp_update_attachment_metadata($attach_id, $attach_data);

            //set the featured image
            set_post_thumbnail($post_id, $attach_id);
        } else {
            //get all media
            $args = array(
                'posts_per_page' => -1,
                'offset' => 0,
                'category' => '',
                'category_name' => '',
                'orderby' => 'date',
                'order' => 'DESC',
                'include' => '',
                'exclude' => '',
                'meta_key' => '',
                'meta_value' => '',
                'post_type' => 'attachment',
                'post_mime_type' => '',
                'post_parent' => '',
                'author' => '',
                'post_status' => 'any',
                'suppress_filters' => true
            );

            //lets try to match things up by the filename
            foreach (get_posts($args) as $media) {
                if ($media->post_title == $filename) {
                    $attach_id = $media->ID;
                    $attach_data = wp_generate_attachment_metadata($attach_id, $filepath);
                    wp_update_attachment_metadata($attach_id, $attach_data);
                }
            }
        }

        if (is_null($attach_id)) {
            throw new Exception('Could not find location of image.');
        }

        //Logger::log(get_class().__METHOD__, 'file: '.$upload_dir['url'], true);
        //print_r($media);

        $imgArray['request-size'] = wp_get_attachment_image($attach_id, 'medium');
        $imgArray['full-size'] = wp_get_attachment_image($attach_id, 'full');

        return $imgArray;
    }
}

?>