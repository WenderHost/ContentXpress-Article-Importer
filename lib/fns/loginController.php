<?php

/**
 * Logs plugin into ContentXpress
 *
 * @return     boolean  `true` if login successful
 */
function cxp_login(){
    $cxpuser = CXPRequest::contentXpressSessionGet( "cxpuser" );
    if ( empty( $cxpuser ) ) {
        // Login the user
        $cxp_username = get_option( 'cxp_username' );
        $cxp_password = get_option( 'cxp_password' );
        $login = CXPRequest::requestAuth( $cxp_username, $cxp_password );
        if ( ! empty( CXPRequest::$http_code ) && CXPRequest::$http_code != 200 ) {

            switch ( CXPRequest::$http_code ) {
                case 401:
                    $error_type = 'Incorrect Username/Password combination.';
                    break;
                default:
                    $error_type = 'Error code: ' . CXPRequest::$http_code . '. Please contact Publishers Support.';
                    break;
            }
            echo 'ERROR: ' . $error_type . "\n";
            write_log( 'ERROR: ' . $error_type );

            return false;

        } else if ( CXPRequest::$http_code = 200 ) {
            CXPRequest::contentXpressSessionSet( "cxpuser", $cxp_username );
            CXPRequest::contentXpressSessionSet( "cxppassword", $cxp_password );

            return true;
        }
    } else {
        return true;
    }
}
