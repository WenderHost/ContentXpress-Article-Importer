<?php

class CXP_Background_Image_Process extends WP_Background_Process{

    /**
     * @var string
     */
    protected $action = 'process_image';

    /**
     * Empties the queue of images
     */
    public function empty_queue(){
        $this->data = array();
    }

    /**
     * Handle
     *
     * Override this method to perform any actions required
     * during the async request.
     */
    protected function task( $image ) {
        // Login to ContentXpress
        $success = cxp_login();
        if( false == $success ){
            write_log( 'Unable to login to ContentXpress.' );
            return false;
        }

        write_log( 'Running background image processing for: $image[ contentID => ' . $image['contentID'] . ', post_id => ' . $image['post_id'] . ']' );

        //get image data
        $imageData = CXPRequest::getImage( $image['contentID'] );
        $caption = $image['caption'];
        $post_id = $image['post_id'];

        //upload images to the wp directory
        $imgArray = WPActions::uploadMedia($imageData, $caption, $post_id);

        return false;
    }

    /**
     * Complete
     *
     * Override if applicable, but ensure that the below actions are
     * performed, or, call parent::complete().
     */
    protected function complete() {
        parent::complete();

        // Show notice to user or perform some other arbitrary task...
        write_log( '[BackgroundImageProcess] Batch completed!' . "\n\n" );
    }

}