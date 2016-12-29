<?php
/*
 $cxp_options = Array
(
    [username] => mentelle
    [password] => mentell2Read
)
 */
namespace CXP\lib\fns\settings;

add_action( 'admin_init', __NAMESPACE__ . '\\settings_init' );
function settings_init(){
    if( false == get_option( 'cxp_username' ) )
        add_option( 'cxp_username', '' );
    if( false == get_option( 'cxp_password' ) )
            add_option( 'cxp_password', '' );

    // add_settings_section( $id, $title, $callback, $page );
    add_settings_section( 'cxp_settings_section', 'ContentXpress Login', __NAMESPACE__ . '\\settings_section_cb', 'cxp-settings' );

    // add_settings_field( $id, $title, $callback, $page, $section, $args );
    add_settings_field(
        'cxp_username',
        'Username',
        __NAMESPACE__ . '\\username_field_cb',
        'cxp-settings',
        'cxp_settings_section',
        [
            'label_for' => 'cxp_username',
            'name' => 'cxp_username'
        ]
    );
    add_settings_field(
        'cxp_password',
        'Password',
        __NAMESPACE__ . '\\password_field_cb',
        'cxp-settings',
        'cxp_settings_section',
        [
            'label_for' => 'cxp_password',
            'name' => 'cxp_password'
        ]
    );

    // register_setting( $option_group, $option_name, $sanitize_callback );
    register_setting( 'cxp_options', 'cxp_username', __NAMESPACE__ . '\\validate_username' );
    register_setting( 'cxp_options', 'cxp_password' );
}

/**
 * Handles display of the ContentXpress settings fields
 */
function settings_page_callback(){
?>
<div class="wrap">
    <form action="options.php" method="post">
    <?php
    settings_fields('cxp_options');
    do_settings_sections( 'cxp-settings' );
    submit_button( 'Save Settings', 'primary', 'save', false );
    ?>
    </form>
</div>
<?php
}

/**
 * HTML displayed above the ContentXpress Settings section
 */
function settings_section_cb(){
    ?><p>Provide your ContextXpress login details:</p><?php
}

/**
 * Display error notices
 */
function settings_section_errors(){
    settings_errors( '' );
}
add_action( 'admin_notices', __NAMESPACE__ . '\\settings_section_errors' );

/**
 * Displays username field
 */
function username_field_cb( $args ){
    $username = get_option( 'cxp_username' );
    $name = esc_attr( $args['name'] );
?>
<input type="text" name="<?= $name ?>" id="<?= $name ?>" value="<?= isset( $username ) ? esc_attr( $username ) : '' ?>" />
<?php
}

/**
 * Displays password field
 */
function password_field_cb( $args ){
    $password = get_option( 'cxp_password' );
    $name = esc_attr( $args['name'] );
?>
<input type="text" name="<?= $name ?>" id="<?= $name ?>" value="<?= isset( $password ) ? esc_attr( $password ) : '' ?>" />
<?php
}

//*
function validate_username( $username = null ){
    if( null == $username ){
        add_settings_error( 'usernameEmpty', 'empty', 'Username can not be emtpy.', 'error' );
        return $username;
    }

    if( ! preg_match( '/^[0-9A-Za-z]*$/', $username ) ){
        add_settings_error( 'usernameNotAlphaNumeric', 'invalid', 'Username must be alphanumeric.', 'error' );
    }

    return $username;
}
/**/
