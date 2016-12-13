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
        $termsArray = $article->term_tags;
        $coverDisplayDate = $article->coverDisplayDate;
        $section = $article->section;

        // Add the article author as a WordPress user
        $authorName = $article->author;
        if( empty( $authorName ) )
            $authorName = 'MD-Update Staff';
        $authorUsername = WPActions::getAuthorUsername( $authorName );
        $authorEmail = $authorUsername . '@example.com';

        $user_id = email_exists( $authorEmail );
        if( ! $user_id ){
            $user_id = wp_create_user( $authorUsername, wp_generate_password(), $authorEmail );

            if( is_wp_error( $user_id ) ){
                // Show the errors
                $error_codes = $user_id->get_error_codes();
                if( is_array( $error_codes ) && 0 < count( $error_codes ) ){
                    $errors = array();
                    foreach( $error_codes as $code ){
                        $errors[] = 'Error(' . $code . '): ' . $user_id->get_error_message( $code );
                    }
                }
                Logger::log( get_class() . __METHOD__, 'Error creating user <code>' . $authorUsername . '</code><ul><li>' . implode( '</li><li>', $errors ) . '<li></ul>', true );

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

                // Set new user to `author` role
                $user = new WP_User( $user_id );
                $user->remove_role( 'subscriber' );
                $user->add_role( 'author' );
            }
        }

        $post_title = ( ! empty( $title ) )? $title : $identifier;

        // Sanitize the post content
        // Set <p prism:class="deck">...</p> as sub_heading
        if( false != preg_match( '/(<p\sprism:class="deck">)(.*)(<\/p>)/U', $content, $matches ) ){
            $sub_heading = $matches[2];
            $content = str_replace( $matches[0], '', $content );
        }

        // Create post object
        $cxp_post = array(
            'post_title' => $post_title,
            'post_content' => $content,
            'post_status' => $publish ? 'publish' : 'draft',
            'post_type' => $postType,
            'post_author' => $user_id,
            'post_category' => array(1), // (1) Default: Uncategorized
            'sub_heading' => $sub_heading,
        );

        if (!is_null($tagsArray))
            $cxp_post['tags_input'] = $tagsArray;

        if( empty( $date ) ){
            $date_array = explode( '_', $identifier );
            $month = $date_array[1];
            $year = $date_array[2];
            if( in_array( strtolower( $month ), ['jan','feb','mar','apr','may','jun','jul','aug','sep','oct','nov','dec'] ) && is_numeric( $year ) ){
                $date = date( 'Y-m-d H:i:s', strtotime( $month . ' ' . $year ) );
            }
        }

        if ( ! is_null( $date ) )
            $cxp_post['post_date'] = $date; //cxp format 2011-09-27 wp format [ Y-m-d H:i:s ]

        // Check if a post with the same title exists
        $args = [
            'posts_per_page' => -1,
            'post_title_like' => $post_title,
            'post_type' => $postType,
            'post_status' => 'any',
        ];

        foreach ( get_posts( $args ) as $post ) {
            if( $post_title == $post->post_title ){
                $post_id = $post->ID;
                break;
            }
        }

        if ( ! is_null( $post_id ) ) {
            WPActions::updatePost( $post_id, $cxp_post );
            //Logger::log(get_class() . __METHOD__, 'Updated Post: ' . $post_id, false);
        } else {
            $post_id = wp_insert_post( $cxp_post );
            //Logger::log(get_class() . __METHOD__, 'Created Post: ' . $post_id, false);
            Logger::log(get_class() . __METHOD__, '<p><strong>$article:</strong></p><textarea style="width: 80%; height: 200px; font-family: Courier; background-color: #eee;">' . print_r( $article, true ) . '</textarea>', true );
        }

        // Add the sub-heading as a custom field
        if( ! empty( $cxp_post['sub_heading'] ) ){
            update_post_meta( $post_id, 'sub_heading', $cxp_post['sub_heading'] );
        }

        // Add $termsArray as tags if post_type == `post`
        if( 0 < count( $termsArray ) && 'post' == $postType ){
            $term_ids = array();
            foreach( $termsArray as $term ){
                $term_exists = term_exists( $term, 'post_tag' );
                if( ! $term_exists ){
                    $term_id = wp_insert_term( $term, 'post_tag' );
                    $term_id = ( is_array( $term_id ) )? $term_id['term_id'] : $term_id;
                    $term_ids[] = intval( $term_id );
                } else {
                    $term_ids[] = intval( $term_exists['term_id'] );
                }
            }
            wp_set_object_terms( $post_id, $term_ids, 'post_tag' );
        }

        // Tag this post under the `Issue` custom taxonomy
        // using the value of $coverDisplayDate
        if( ! empty( $coverDisplayDate ) && 'post' == $postType ){
            $issue = $coverDisplayDate;
            $term_exists = term_exists( $issue, 'issue' );
            if( ! $term_exists ){
                $term_id = wp_insert_term( $issue, 'issue' );
                $term_id = ( is_array( $term_id ) )? $term_id['term_id'] : $term_id;
            } else {
                $term_id = $term_exists['term_id'];
            }
            settype( $term_id, 'int' );
            wp_set_object_terms( $post_id, $term_id, 'issue' );
        }

        // Categorize this article under $section
        if( ! empty( $section ) && 'post' == $postType ){
            $term_exists = term_exists( $section, 'category' );
            if( ! $term_exists ){
                $term_id = wp_insert_term( $section, 'category' );
                $term_id = ( is_array( $term_id ) )? $term_id['term_id'] : $term_id;
            } else {
                $term_id = $term_exists['term_id'];
            }
            settype( $term_id, 'int' );
            wp_set_object_terms( $post_id, $term_id, 'category' );
        }

        return $post_id;
    }

    // Creates a new term in WP. Can be category or tag
    public static function createTerm( $term, $taxonomy, $args )
    {
        $args = array(
            'name' => $term,
            'taxonomy' => $taxonomy,
            //'slug'			=>
            //'description'	=>
            //'parent'	=>
        );

        wp_insert_term( $term, $taxonomy );
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

        $search = array( ' ', '.', ',', '-' );
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

        if ( wp_mkdir_p( $upload_dir['path'] ) ) {
            $path = $upload_dir['path'];
        } else {
            //Check basedir to see if file exists
            $path = $upload_dir['basedir'];
        }

        $unique_filename = wp_unique_filename( $path, $filename );

        $file_created = file_put_contents( trailingslashit( $path ) . $unique_filename, $image['file'] );

        if ( false === $file_created ) {
            throw new Exception('File could not be created.');
        }

        $wp_filetype = wp_check_filetype( $unique_filename, null );
        $attachment = array(
            'guid' => trailingslashit( $upload_dir['url'] ) . $unique_filename,
            'post_mime_type' => $wp_filetype['type'],
            'post_title' => $unique_filename,
            'post_status' => 'inherit',
            'post_excerpt' => $caption,
            'post_content' => ''
        );

        $attach_id = wp_insert_attachment( $attachment, trailingslashit( $path ) . $unique_filename, $post_id );
        $attach_data = wp_generate_attachment_metadata( $attach_id, trailingslashit( $path ) . $unique_filename );
        wp_update_attachment_metadata( $attach_id, $attach_data );

        //set the featured image
        set_post_thumbnail($post_id, $attach_id);

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