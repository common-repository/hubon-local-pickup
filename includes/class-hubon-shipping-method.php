<?php

class Hubon_Shipping_Method extends WC_Shipping_Method
{
    public $shipping_calculation_error;

    public $option_list;

    public $rest_adapter;

    public function __construct()
    {
        $this->id = 'hubon';
        $this->title = __('HubOn Local Pickup', 'hubon-local-pickup');
        $this->method_title = __('HubOn Local Pickup', 'hubon-local-pickup');
        $this->method_description = __('HubOn shipping method for WooCommerce', 'hubon-local-pickup');
        $this->shipping_calculation_error = '';
        $this->option_list = array(
            'hubon_display_name',
            'hubon_secret_key',
            'information_licence'
        );
        $this->init();
    }

    function init()
    {
        $this->init_form_fields();
        $this->init_settings();

        add_action('woocommerce_update_options_shipping_' . $this->id, array($this, 'process_admin_options'));
        $secret_key = strlen(get_option($this->get_hubon_option_key("hubon_secret_key"))) ? get_option($this->get_hubon_option_key("hubon_secret_key")) : "";
        $this->rest_adapter = new Hubon_Rest_Adapter($secret_key);
    }

    function init_form_fields()
    {
        $this->form_fields = array(
            'enabled' => array(
                'title' => __('Enable', 'hubon-local-pickup'),
                'type' => 'checkbox',
                'description' => __('Activate the HubOn plugin when you have finished configuring it', 'hubon-local-pickup'),
                'default' => 'yes',
            ),
        );
    }

    function reset_settings_and_option()
    {
        foreach ($this->option_list as $key) {
            delete_option($this->get_hubon_option_key($key));
        }

        delete_option('woocommerce_' . $this->id . '_settings');
    }

    public function admin_options()
    {
        $this->save_settings();
        $this->loads_settings();
    }

    public function loads_settings()
    {
        // DO NOT REMOVE THIS CODE
        $options = $this->get_options();
        if (isset($options['hubon_secret_key']) || $options['hubon_secret_key'] === false) {
            if (!empty($options['hubon_secret_key'])) {
                $check_secret_key = $this->rest_adapter->customer_info($options['hubon_secret_key']);
                if (isset($check_secret_key['error'])) {
                    $options["information_licence"] = "Secret key is invalid, please re-enter it";
                }
            } else {
                $options["information_licence"] = "";
            }
        } else {
            $options["information_licence"] = "";
        }

        // DO NOT REMOVE THIS CODE
        require_once HUBON_PLUGIN_PATH . 'admin/views/settings.php';
    }

    private function check_customer_hubon()
    {
        $options = $this->get_options();
        if (isset($options['hubon_secret_key'])) {
            if (!empty($options['hubon_secret_key'])) {
                $check_secret_key = $this->rest_adapter->customer_info($options['hubon_secret_key']);
                if (isset($check_secret_key['error'])) {
                    return false;
                }
            } else {
                return false;
            }
        }
        return true;
    }

    public function get_options()
    {
        $options = array();
        foreach ($this->option_list as $key) {
            $options[$key] = get_option($this->get_hubon_option_key($key));
        }
        return $options;
    }

    private function save_settings()
    {
        if (wp_verify_nonce(sanitize_text_field(@$_POST['hubon-nonce']), 'hubon-settings')) {
            $new_options = array();

            foreach ($this->option_list as $opt_key) {
                if (isset($_POST[$opt_key])) {
                    $new_options[$opt_key] = sanitize_text_field($_POST[$opt_key]);
                } else {
                    $new_options[$opt_key] = "";
                }
            }

            $this->save_options($new_options);
            $this->loads_settings();
        }
    }

    private function save_options($new_options)
    {
        if (wp_verify_nonce(sanitize_text_field(@$_POST['hubon-nonce']), 'hubon-settings')) {
            $old_options = $this->get_options();
            $hubon_secret_key = sanitize_text_field($_POST['hubon_secret_key']) ?? '';

            if (empty($hubon_secret_key)) {
                $new_options["information_licence"] = "Please enter the secret key";
            } elseif ($hubon_secret_key !== $old_options['hubon_secret_key']) {
                $check_secret_key = $this->rest_adapter->customer_info($hubon_secret_key);
                if (isset($check_secret_key['error'])) {
                    $new_options["information_licence"] = "Secret key is invalid, please re-enter it";
                } else {
                    $new_options["information_licence"] = '';
                }
            }

            foreach ($new_options as $key => $new_value) {
                $old_value = $old_options[$key] ?? '';
                if (is_array($new_value)) {
                    $new_value = array_map('sanitize_text_field', $new_value);
                } else {
                    $new_value = sanitize_text_field($new_value);
                }

                if ($new_value !== $old_value) {
                    update_option($this->get_hubon_option_key($key), $new_value);
                }
            }
        }
    }

    private function get_hubon_option_key($key)
    {
        return $this->id . '_' . $key;
    }

    public function calculate_shipping($package = array())
    {
        $this->shipping_calculation_error = '';
        if ($this->settings['enabled'] === 'yes' && $this->check_customer_hubon()) {
            $destination = $package['destination'];
            $customer_id = get_current_user_id();
            if ($customer_id) {
                $first_name = get_user_meta($customer_id, 'billing_first_name', true);
                $last_name = get_user_meta($customer_id, 'billing_last_name', true);
                $phone = get_user_meta($customer_id, 'billing_phone', true);
            } else {
                $first_name = WC()->customer->get_billing_first_name();
                $last_name = WC()->customer->get_billing_last_name();
                $phone = WC()->customer->get_billing_phone();
            }

            $origin = $this->get_origin_details();

            $items = [];
            foreach ($package['contents'] as $values) {
                $_product = $values['data'];
                $items[] = [
                    "name" => $_product->get_name(),
                    "sku" => $_product->get_sku(),
                    "quantity" => $values['quantity'],
                    "grams" => $_product->get_weight() != null ? $_product->get_weight() : 0,
                    "price" => $_product->get_price(),
                    "vendor" => "",
                    "requires_shipping" => $_product->needs_shipping(),
                    "taxable" => $_product->is_taxable(),
                    "fulfillment_service" => "manual",
                    "properties" => null,
                    "product_id" => $_product->get_id(),
                    "variant_id" => $_product->get_variation_id() ? $_product->get_variation_id() : $_product->get_id(),
                ];
            }

            $rateData = [
                "rate" => [
                    "origin" => [
                        "country" => $origin['country'],
                        "postal_code" => $origin['postal_code'],
                        "province" => $origin['province'],
                        "city" => $origin['city'],
                        "name" => $origin['name'],
                        "address1" => $origin['address1'],
                        "address2" => $origin['address2'],
                        "address3" => $origin['address3'],
                        "latitude" => $origin['latitude'],
                        "longitude" => $origin['longitude'],
                        "phone" => $origin['phone'],
                        "fax" => $origin['fax'],
                        "email" => $origin['email'],
                        "address_type" => $origin['address_type'],
                        "company_name" => $origin['company_name']
                    ],
                    "destination" => [
                        "country" => $destination['country'],
                        "postal_code" => $destination['postcode'],
                        "province" => $destination['state'],
                        "city" => $destination['city'],
                        "name" => trim($first_name . ' ' . $last_name),
                        "address1" => $destination['address'],
                        "address2" => isset($destination['address_2']) ? $destination['address_2'] : "",
                        "address3" => null,
                        "latitude" => "",
                        "longitude" => "",
                        "phone" => $phone,
                        "fax" => null,
                        "email" => null,
                        "address_type" => null,
                        "company_name" => isset($destination['address_2']) ? $destination['address_2'] : ""
                    ],
                    "items" => $items,
                    "currency" => "USD",
                    "locale" => "en"
                ]
            ];


            $get_nearest_hub = $this->rest_adapter->get_nearest_hub($rateData);

            if (!empty($get_nearest_hub) && isset($get_nearest_hub['details']['message'])) {
                $this->shipping_calculation_error = $get_nearest_hub['details']['message'];
                return;
            }

            foreach ($get_nearest_hub as $rate) {
                $rate = array(
                    'id' => $rate['service_code'],
                    'label' => $rate['service_name'],
                    'cost' => $rate['total_price'],
                    'calc_tax' => 'per_item',
                    'meta_data' => array(
                        'description' => $rate['description'],
                        'code' => $rate['service_code'],
                    )
                );
                $this->add_rate($rate);
            }
        }
    }

    private function get_origin_details()
    {
        $secret_key = $this->rest_adapter->hubon_secret_key;
        $get_origin_customer = $this->rest_adapter->customer_info($secret_key);
        if (isset($get_origin_customer['registered_customer'])) {
            $setting = $get_origin_customer['registered_customer']['setting'];
            $info = $get_origin_customer['registered_customer']['info'];
            $hub = $setting['default_hub'];
            return [
                'country' => $hub ? strtoupper($hub['address']['country_code']) : "",
                'postal_code' => $hub ? $hub['address']['country_code'] : "",
                'province' => $hub ? $hub['address']['province_code'] : "",
                'city' => $hub ? $hub['address']['city'] : "",
                'name' => $info['full_name'],
                'address1' => $hub ? $hub['address']['full_address'] : "",
                'address2' => null,
                'address3' => null,
                'latitude' => $hub ? $hub['address']['latitude'] : "",
                'longitude' => $hub ? $hub['address']['longitude'] : "",
                'phone' => $get_origin_customer['registered_customer']['phone_number'],
                'fax' => null,
                'email' => $info['email'],
                'address_type' => null,
                'company_name' => null
            ];
        } else {
            return [
                'country' => "",
                'postal_code' =>  "",
                'province' =>  "",
                'city' => "",
                'name' => "",
                'address1' => "",
                'address2' => "",
                'address3' => "",
                'latitude' => "",
                'longitude' => "",
                'phone' => "",
                'fax' => "",
                'email' => "",
                'address_type' => "",
                'company_name' => "",
            ];
        }
    }
}
