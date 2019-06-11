<?php
/**
 * Class WC_WooMercadoPago_Hook_Abstract
 */

abstract class WC_WooMercadoPago_Hook_Abstract
{
    public $payment;
    public $class;
    public $mpInstance;

    /**
     * WC_WooMercadoPago_Hook_Abstract constructor.
     * @param $payment
     */
    public function __construct($payment)
    {
        $this->payment = $payment;
        $this->class = get_class($payment);
        $this->mpInstance = $payment->mp;
        $this->loadHooks();
    }

    /**
     * Load Hooks
     */
    public function loadHooks()
    {
        add_action('woocommerce_update_options_payment_gateways_' . $this->payment->id, array($this, 'custom_process_admin_options'));
        add_action('send_options_payment_gateways' . strtolower($this->class), array($this, 'send_settings_mp'));
        add_action('woocommerce_api_' . strtolower($this->class), array($this, 'check_ipn_response'));
        add_action('woocommerce_cart_calculate_fees', array($this, 'add_discount'), 10);
        add_filter('woocommerce_gateway_title', array($this, 'get_payment_method_title'), 10, 2);

        if (!empty($this->payment->settings['enabled']) && $this->payment->settings['enabled'] == 'yes') {
            add_action('woocommerce_after_checkout_form', array($this, 'add_mp_settings_script'));
            add_action('woocommerce_thankyou', array($this, 'update_mp_settings_script'));
        }
    }

    /**
     * @param $checkout
     */
    public function add_discount_abst($checkout)
    {
        if (isset($checkout['discount']) && !empty($checkout['discount']) && isset($checkout['coupon_code']) && !empty($checkout['coupon_code']) && $checkout['discount'] > 0 && WC()->session->chosen_payment_method == $this->payment->id)
        {
            $this->payment->log->write_log(__FUNCTION__, $this->class.'trying to apply discount...');
            $value = ($this->payment->site_data['currency'] == 'COP' || $this->payment->site_data['currency'] == 'CLP') ? floor($checkout['discount'] / $checkout['currency_ratio']) : floor($checkout['discount'] / $checkout['currency_ratio'] * 100) / 100;
            global $woocommerce;
            if (apply_filters('wc_mercadopago_custommodule_apply_discount', 0 < $value, $woocommerce->cart)) {
                $woocommerce->cart->add_fee(sprintf(__('Discount for %s coupon', 'woocommerce-mercadopago'), esc_attr($checkout['campaign'])), ($value * -1), false);
            }
        }
    }

    /**
     * Analytics Save
     */
    public function send_settings_mp()
    {
        $_site_id_v1 = get_option('_site_id_v1', '');
        $is_test_user = get_option('_test_user_v1', false);
        if (!empty($_site_id_v1)) {
            if (!$is_test_user) {
                $this->payment->mp->analytics_save_settings($this->define_settings_to_send());
            }

            if ($this->class == 'WC_WooMercadoPago_BasicGateway') {
                $this->payment->mp->set_two_cards_mode($this->payment->two_cards_mode);
            }
        }
    }

    /**
     * @return array
     */
    public function define_settings_to_send()
    {
        $infra_data = WC_WooMercadoPago_Module::get_common_settings();
        switch ($this->class) {
            case 'WC_WooMercadoPago_BasicGateway':
                $infra_data['checkout_basic'] = ($this->payment->settings['enabled'] == 'yes' ? 'true' : 'false');
                $infra_data['two_cards'] = ($this->payment->two_cards_mode == 'active' ? 'true' : 'false');
                break;
            case 'WC_WooMercadoPago_CustomGateway':
                $infra_data['checkout_custom_credit_card'] = ($this->payment->settings['enabled'] == 'yes' ? 'true' : 'false');
                $infra_data['checkout_custom_credit_card_coupon'] = ($this->payment->settings['coupon_mode'] == 'yes' ? 'true' : 'false');
                break;
            case 'WC_WooMercadoPago_TicketGateway':
                $infra_data['checkout_custom_ticket'] = ($this->payment->settings['enabled'] == 'yes' ? 'true' : 'false');
                $infra_data['checkout_custom_ticket_coupon'] = ($this->payment->settings['coupon_mode'] == 'yes' ? 'true' : 'false');
                break;
        }
        return $infra_data;
    }

    /**
     * ADD Checkout Scripts
     */
    public function add_checkout_scripts()
    {
        if (is_checkout() && $this->payment->is_available()) {
            if (!get_query_var('order-received')) {
                wp_enqueue_style('woocommerce-mercadopago-style',
                    plugins_url('../../assets/css/custom_checkout_mercadopago.css', plugin_dir_path(__FILE__))
                );
                wp_enqueue_script('woocommerce-mercadopago-pse-js', 'https://secure.mlstatic.com/sdk/javascript/v1/mercadopago.js');
            }
        }
    }

    /**
     * @param $title
     * @return string
     */
    public function get_payment_method_title($title,$id)
    {
        if (!is_checkout() && !(defined('DOING_AJAX') && DOING_AJAX)) {
            return $title;
        }
        if ($title != $this->payment->title || $this->payment->gateway_discount == 0) {
            return $title;
        }
        if (!is_numeric($this->payment->gateway_discount) || $this->payment->gateway_discount < -99 || $this->payment->gateway_discount > 99) {
            return $title;
        }
        $total = (float)WC()->cart->subtotal;
        $price_percent = $this->$this->payment->gateway_discount / 100;
        if ($price_percent > 0) {
            $title .= ' (' . __('Discount of', 'woocommerce-mercadopago') . ' ' . strip_tags(wc_price($total * $price_percent)) . ')';
        } elseif ($price_percent < 0) {
            $title .= ' (' . __('Fee of', 'woocommerce-mercadopago') . ' ' . strip_tags(wc_price(-$total * $price_percent)) . ')';
        }
        return $title;
    }

    /**
     * MP Settings Script
     */
    public function add_mp_settings_script()
    {
        $public_key = get_option('_mp_public_key');
        $is_test_user = get_option('_test_user_v1', false);

        if (!empty($public_key) && !$is_test_user) {
            $w = WC_WooMercadoPago_Module::woocommerce_instance();
            $available_payments = array();
            $gateways = WC()->payment_gateways->get_available_payment_gateways();
            foreach ($gateways as $g) {
                $available_payments[] = $g->id;
            }
            $available_payments = str_replace('-', '_', implode(', ', $available_payments));
            if (wp_get_current_user()->ID != 0) {
                $logged_user_email = wp_get_current_user()->user_email;
            } else {
                $logged_user_email = null;
            }
            ?>
            <script src="https://secure.mlstatic.com/modules/javascript/analytics.js"></script>
            <script type="text/javascript">
                try {
                    var MA = ModuleAnalytics;
                    MA.setPublicKey('<?php echo $public_key; ?>');
                    MA.setPlatform('WooCommerce');
                    MA.setPlatformVersion('<?php echo $w->version; ?>');
                    MA.setModuleVersion('<?php echo WC_WooMercadoPago_Module::VERSION; ?>');
                    MA.setPayerEmail('<?php echo($logged_user_email != null ? $logged_user_email : ""); ?>');
                    MA.setUserLogged( <?php echo(empty($logged_user_email) ? 0 : 1); ?> );
                    MA.setInstalledModules('<?php echo $available_payments; ?>');
                    MA.post();
                } catch (err) {
                }
            </script>
            <?php
        }
    }

    /**
     * @param $order_id
     * @return string|void
     */
    public function update_mp_settings_script($order_id)
    {
        $_mp_public_key = get_option('_mp_public_key');
        $is_test_user = get_option('_test_user_v1', false);
        if (!$is_test_user) {
            $this->payment->log->write_log(__FUNCTION__, 'updating order of ID ' . $order_id);
            return '<script src="https://secure.mlstatic.com/modules/javascript/analytics.js"></script>
			<script type="text/javascript">
				try {
					var MA = ModuleAnalytics;
                    MA.setPublicKey(' . $_mp_public_key . ');
					MA.setPaymentType("basic");
					MA.setCheckoutType("basic");
					MA.put();
				} catch(err) {}
			</script>';
        }
    }
}