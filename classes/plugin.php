<?php
class PAYPLUG_Plugin {

	public function __construct() {
        if ( is_admin() ){
            add_action( 'admin_menu',    array( __CLASS__, 'payplug_admin_menu' ) );
            add_action( 'admin_init',    array( __CLASS__, 'payplug_admin_init' ) );
            add_action( 'init',          array( __CLASS__, 'save_payplug_configuration' ) );
            add_action( 'admin_notices', array( __CLASS__, 'payplug_admin_notices' ) );
        }

        add_action( 'admin_head', array( __CLASS__, 'payplug_admin_head') );
        add_shortcode( 'payplug', array( __CLASS__, 'payplug_shortcode' ) );
    }

    public static function payplug_admin_head() {
        add_filter( 'mce_external_plugins', array( __CLASS__, 'payplug_mce_external_plugins') );
        add_filter( 'mce_buttons', array( __CLASS__, 'payplug_mce_buttons') );
    }

    public static function payplug_mce_external_plugins( $plugin_array ) {
        $plugin_array['shortcode_drop'] = PAYPLUG_URL . 'assets/js/button.js';
        return $plugin_array;
    }

    public static function payplug_mce_buttons( $buttons ) {
        array_push($buttons, 'payplug_shortcode_button');
        return $buttons;
    }

    public static function payplug_admin_notices() {
        settings_errors( 'payplug-notices' );
    }

    public static function payplug_admin_menu() {
        add_menu_page(
            __( 'PayPlug', 'payplug' ),
            __( 'PayPlug', 'payplug' ),
            'manage_options',
            'payplug-admin-options',
            array( __CLASS__, 'payplug_admin' ),
            PAYPLUG_URL . 'assets/images/payplug.png',
            62
        );
        do_settings_sections( 'payplug' );
    }

    public static function payplug_admin() { ?>
        <style>
        #wc_get_started.payplug{
            padding: 10px;
            padding-left: 230px;
            background-image: url(<?php echo plugins_url( '../assets/images/payplug-logo-large.png' , __FILE__ )?>);
            background-position: 20px 40%;
            background-repeat: no-repeat;
            background-color: white;
            margin-top: 10px;
            border-radius: 5px;
            margin-bottom: 15px;
        }
        </style>
        <div id="wc_get_started" class="payplug">
            <span><?php _e( 'Integrate online payment on your website in 1 minute.', 'payplug'); ?></span>
            <?php _e( '<b>2,5% per transaction + 0,25 €</b>.<br/>Your client doesn\'t have to account to pay.', 'payplug'); ?>
            <p>
                <a href="www.payplug.fr/portal2/signup?sponsor=136" target="_blank" class="button button-primary"><?php _e('Create free account', 'payplug'); ?></a>
                <a href="www.payplug.fr/?sponsor=136" target="_blank" class="button"><?php _e('Learn more about PayPlug', 'payplug'); ?></a>
            </p>
        </div>
        <h2><?php _e( 'PayPlug configuration', 'payplug' ); ?></h2>
        <form action="options.php" method="post"><?php
            settings_fields( 'payplug-admin-settings' );
            do_settings_sections( 'payplug-admin-settings' );
            submit_button(); ?>
            <?php _e( 'Once configured, use the shortcode [payplug price="xx.xx" title_button="Buy"] to generate a payment button PayPlug.', 'payplug' ); ?>
        </form><?php
    }

    public static function payplug_admin_init() {
        register_setting(
            'payplug-admin-settings',
            'payplug_options',
            ''
        );
        add_settings_section(
            'payplug_id',
            '',
            '',
            'payplug-admin-settings'
        );
        add_settings_field(
            'payplug_login',
            __( 'Login PayPlug', 'payplug'),
            array( __CLASS__, 'input_login' ),
            'payplug-admin-settings',
            'payplug_id'
        );
        add_settings_field(
            'payplug_password',
            __( 'Password PayPlug', 'payplug'),
            array( __CLASS__, 'input_password' ),
            'payplug-admin-settings',
            'payplug_id'
        );
        add_settings_field(
            'payplug_test_mode',
            __( 'Payplug TEST mode ?', 'payplug'),
            array( __CLASS__, 'input_test_mode' ),
            'payplug-admin-settings',
            'payplug_id'
        );
    }

    public static function input_login() {
        echo self::input( 'payplug_login' );
    }

    public static function input_password() {
        echo self::input( 'payplug_password', 'password' );
    }

    public static function input_test_mode() {
        echo self::input( 'payplug_test_mode', 'checkbox' );
    }

    public static function input( $name, $type = 'text' ) {
        $value = 'value="' . esc_attr( get_option( $name ) ) . '"';
        $style = 'style="width:90%"';
        if ( 'checkbox' == $type ){
            $value = 'checked="' . ( ( 1 == get_option( $name ) ) ? 'checked' : '' ) . '"';
            $style = '';
        }
        return '<input type="' . $type . '" name="' . $name . '" ' . $style . ' ' . $value . '>';
    }

    public static function save_payplug_configuration() {
        if ( isset( $_POST[ 'option_page' ] ) && 'payplug-admin-settings' == $_POST[ 'option_page' ] ) {
            require_once( ABSPATH . 'wp-admin/includes/template.php' );
            if ( !extension_loaded( 'openssl' ) ) {
                add_settings_error(
                    'payplug-notices',
                    'extension_openssl',
                    __( 'OpenSSL must be enabled to run PayPlug', 'payplug' ),
                    'error'
                );
                return false;
            }

            if ( !isset( $_POST[ 'payplug_login' ] ) || empty( $_POST[ 'payplug_login' ] ) || !isset( $_POST[ 'payplug_password' ] ) || empty( $_POST[ 'payplug_password' ] ) ) {
                add_settings_error(
                    'payplug-notices',
                    'empty_fields',
                    __( 'The login and password are mandatory', 'payplug' ),
                    'error'
                );
                return false;
            }

            require_once PAYPLUG_DIR . '/classes/payplug_php/lib/Payplug.php';
            $isTest = ( isset( $_POST[ 'payplug_test_mode' ] ) ) ? true : false;
            try{
                $parameters = Payplug::loadParameters(
                    $_POST[ 'payplug_login' ],
                    $_POST[ 'payplug_password' ],
                    $isTest
                );
            } catch ( Exception $e){
                $error = __('Your login and/or password PayPlug are incorrect', 'payplug');
                if ( '' != $e->getMessage() )
                    $error = $e->getMessage();

                add_settings_error(
                    'payplug-notices',
                    'error_payplug',
                    __( 'PayPlug error', 'payplug') . ' : ' . $error,
                    'error'
                );
                return false;
            }

            // update option
            update_option( 'payplug_login',      $_POST[ 'payplug_login' ] );
            update_option( 'payplug_password',   $_POST[ 'payplug_password' ] );
            update_option( 'payplug_test_mode',  $isTest );
            update_option( 'payplug_parameters', json_encode( $parameters ) );

            add_settings_error(
                'payplug-notices',
                'success_payplug',
                __( 'Changes saved', 'payplug'),
                'updated'
            );
        }
    }

    public static function payplug_shortcode( $atts ) {
        // Test OpenSSL
        if ( !extension_loaded( 'openssl' ) ) {
            return __( 'OpenSSL must be enabled to run PayPlug', 'payplug' );
        }

        require_once PAYPLUG_DIR . '/classes/payplug_php/lib/Payplug.php';

        // Retrieving settings
        $parametres = get_option( 'payplug_parameters' );

        // If error
        if ( '' == $parametres || !isset( $atts[ 'price' ]  ) ) {
            return __( 'Unable to generate the shortcode PayPlug, thank you to verify the configuration PayPlug', 'payplug' );
        }

        // PayPlug settings
        Payplug::setConfig( Parameters::createFromString( $parametres ) );

        // Generate URL
        $paymentUrl = PaymentUrl::generateUrl(
            array(
              'amount' => str_replace( ',', '.', $atts[ 'price' ] ) * 100,
              'currency' => 'EUR',
              'ipnUrl' => home_url(),
              // 'email' => 'john.doe@example.fr',
              // 'firstName' => 'John', 
              // 'lastName' => 'Doe'
            )
        );

        $title_button = ($atts[ 'title_button' ]) ? $atts[ 'title_button' ] : __( 'Buy', 'payplug' );

        return '<a class="payplug_buy_button" href="' . $paymentUrl . '" target="_blank" base_url="https://www.payplug.fr">' . $title_button . '</a>';
    }
}
