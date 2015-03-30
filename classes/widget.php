<?php
class PAYPLUG_Plugin_Widget {

	public function __construct() {
        add_action( 'widgets_init', array( __CLASS__, 'payplug_widgets_init' ) );
    }

    public static function payplug_widgets_init() {
    	_payplug_load_files( PAYPLUG_DIR . 'classes/widgets/', array( 'payplug_widget' ) );

    	register_widget("PayPlug_Widget");
    }    
}