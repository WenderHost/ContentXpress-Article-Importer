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

function cxppage_submenu_callback()
{
    $username = isset($_POST['cxp_username']) ? $_POST['cxp_username'] : '';
    $password = isset($_POST['cxp_password']) ? $_POST['cxp_password'] : '';

    $contentView = plugin_dir_url(__FILE__) . 'contentView.php';
    $contentStore = plugin_dir_url(__FILE__) . 'contentStore.php';

    // Check for failed login attempt
    if (!empty($username) && !empty($password)) {
        $login = CXPRequest::requestAuth($username, $password);
        if (!empty(CXPRequest::$http_code) && CXPRequest::$http_code != 200) {
            //$error_type = '';
            switch (CXPRequest::$http_code) {
                case 401:
                    $error_type = 'Incorrect Username/Password combination.';
                    break;
                default:
                    $error_type = 'Error code: ' . CXPRequest::$http_code . '. Please contact Publishers Support.';
                    break;
            }
            echo '<div class="error"><p>' . $error_type . '</p></div>';

        } else if (CXPRequest::$http_code = 200) {
            CXPRequest::contentXpressSessionSet("cxpuser", $username);
            CXPRequest::contentXpressSessionSet("cxppassword", $password);
            Redirects::contentXpress_logIn();
            exit;
        }
    }

    $cxpuser = CXPRequest::contentXpressSessionGet("cxpuser");
    $cxppassword = CXPRequest::contentXpressSessionGet("cxppassword");

    echo <<< EOT
    <!DOCTYPE html>
	<form method="post" id="cxp-login-form" accept-charset="UTF-8">
		<div id="loginStuff">
			<h2>ContentXpress Login</h2>
			<div class="form-item form-type-textfield form-item-cxp-username">
  				<label for="edit-cxp-username">
					ContentXpress Username: <span class="form-required" title="This field is required.">*</span>
				</label>
 				<input type="text" id="edit-cxp-username" name="cxp_username" value="$username" size="40" maxlength="120" class="form-text required" required />
				<div class="description">A valid ContentXpress username.</div>
			</div>
		<div class="form-item form-type-password form-item-cxp-password">
  			<label for="edit-cxp-password">
				ContentXpress Password: <span class="form-required" title="This field is required.">*</span>
			</label>
 			<input type="password" id="edit-cxp-password" name="cxp_password" value="$password" size="40" maxlength="120" class="form-text required" required />
			<div class="description">
				Password corresponding to the username above.
			</div>
		<input type="submit" id="edit-submit--2" name="op" value="Login" class="form-submit">
		</div>
		</div>
	</form>
	</html>
EOT;
}
