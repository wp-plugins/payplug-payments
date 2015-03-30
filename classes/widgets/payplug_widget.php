<?php
class PayPlug_Widget extends WP_Widget {

	public function __construct() {
		$widget_options = array(
			'classname'		 => 'payplug_widget',
			'description'	 => __('Add button payment PayPlug.', 'payplug')
		);
		parent::__construct('payplug_widget', 'PayPlug', $widget_options);
	}

	public function widget($args, $instance) {
		extract($args, EXTR_SKIP);
		echo $before_widget;

		// Test OpenSSL
        if ( !extension_loaded( 'openssl' ) ) {
            return __( 'OpenSSL must be enabled to run PayPlug', 'payplug' );
        }

        require_once PAYPLUG_DIR . '/classes/payplug_php/lib/Payplug.php';

        // Retrieving settings
        $parametres = get_option( 'payplug_parameters' );

        // If error
        if ( '' == $parametres || !isset( $instance[ 'price' ]  ) ) {
            return __( 'Unable to generate the button PayPlug, thank you to verify the configuration PayPlug', 'payplug' );
        }

        // PayPlug settings
        Payplug::setConfig( Parameters::createFromString( $parametres ) );

        // Generate URL
        $paymentUrl = PaymentUrl::generateUrl(
            array(
              'amount' => str_replace( ',', '.', $instance[ 'price' ] ) * 100,
              'currency' => 'EUR',
              'ipnUrl' => home_url(),
              // 'email' => 'john.doe@example.fr',
              // 'firstName' => 'John', 
              // 'lastName' => 'Doe'
            )
        );

        $title_button = ($instance[ 'title_button' ]) ? $instance[ 'title_button' ] : __( 'Buy', 'payplug' );

        echo '<a class="payplug_buy_button" href="' . $paymentUrl . '" target="_blank" base_url="https://www.payplug.fr">' . $title_button . '</a>';

		echo $after_widget;
	}

	public function update($new_instance, $old_instance) {
		$new_instance = parent::update($new_instance, $old_instance);
		return $new_instance;
	}

	public function form($instance) {
		$default = array(
			'title_button'	=> __('Buy', 'payplug'),
			'price'			=> ''
		);

		$instance = wp_parse_args((array)$instance, $default);

		$title_button_id		= $this->get_field_id('title_button');
		$title_button_name		= $this->get_field_name('title_button');
		$price_id				= $this->get_field_id('price');
		$price_name				= $this->get_field_name('price');
		?>
		<p>
			<span></span>
			<label for="<?php echo $title_button_id ?>">
				<?php _e('Button title', 'payplug'); ?> :
				<input id="<?php echo $title_button_id ?>" name="<?php echo $title_button_name ?>" type="text" value="<?php echo ($instance['title_button']) ? $instance['title_button'] : ''; ?>" />
			</label><br /><br />
			<label for="<?php echo $price_id ?>">
				<?php _e('Price', 'payplug'); ?> :
				<input id="<?php echo $price_id ?>" name="<?php echo $price_name ?>" type="text" value="<?php echo ($instance['price']) ? $instance['price'] : ''; ?>" />
			</label>
		</p>
		<?php
	}

}