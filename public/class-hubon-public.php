<?php
class Hubon_Public
{
	private $plugin_name;

	private $version;

	public function __construct($plugin_name, $version)
	{
		$this->plugin_name = $plugin_name;
		$this->version = $version;
	}

	public function enqueue_styles()
	{
		wp_enqueue_style('jquery-ui', plugin_dir_url(__FILE__) . 'css/jquery-ui.min.css', array(), $this->version . "-" . md5(gmdate("Y-m-d H:i:s")));
		wp_enqueue_style($this->plugin_name, plugin_dir_url(__FILE__) . 'css/tailwind.css', array('jquery-ui'), $this->version . "-" . md5(gmdate("Y-m-d H:i:s")), false);
	}

	public function enqueue_scripts()
	{
		$hubon_shipping = $this->get_hubon_shipping();
		$hubon_secret_key = "";
		$hubon_client_id = "";
		if (!empty($hubon_shipping)) {
			$hubon_base_url = $hubon_shipping->rest_adapter->base_url;
			$hubon_secret_key = $hubon_shipping->rest_adapter->hubon_secret_key;
			$hubon_client_id = HUBON_CLIENT_ID;
		}
		$data = array(
			'hubonBaseUrl' => $hubon_base_url,
			'hubonSecretKey' => $hubon_secret_key,
			'hubonClientId' => $hubon_client_id,
		);
		wp_enqueue_script($this->plugin_name, plugin_dir_url(__FILE__) . 'js/index.js', array('jquery', 'jquery-ui-datepicker'), $this->version . "-" . md5(gmdate("Y-m-d H:i:s")), false);
		wp_localize_script($this->plugin_name, 'phpVars', $data);
	}

	private function get_hubon_shipping()
	{
		$hubon_shipping = [];
		if (is_plugin_active('woocommerce/woocommerce.php')) {
			$shipping_methods = WC()->shipping()->get_shipping_methods();
			$hubon_shipping = $shipping_methods['hubon'];
		}
		return $hubon_shipping;
	}

	public function create_transport_order($order_id)
	{
		$hubon_shipping = $this->get_hubon_shipping();
		if (!empty($hubon_shipping) && $hubon_shipping->settings['enabled'] === 'yes') {
			if (!$order_id) {
				return;
			}

			$order = wc_get_order($order_id);
			if (!$order) {
				return;
			}

			$transport_order_created = $order->get_meta('_hubon_transport_order_created') ? true : false;
			if ($transport_order_created) {
				return;
			}

			$orderDetail = array();
			foreach ($order->get_items() as $item_id => $item) {
				$product_name = $item->get_name();
				$quantity = $item->get_quantity();
				$orderDetail[] = array(
					'name' => $product_name,
					'quantity' => $quantity
				);
			}

			$shop_name = site_url();

			$notes = [
				'orderId' => $order_id,
				'orderDetail' => $orderDetail,
				'shop' => $shop_name
			];

			$total_qty = 0;
			foreach ($order->get_items() as $item_id => $item) {
				$total_qty += $item->get_quantity();
			}

			$shipping_phone = $order->get_shipping_phone() ? $order->get_shipping_phone() : $order->get_billing_phone();
			$shipping_first_name = $order->get_shipping_first_name() ? $order->get_shipping_first_name() : $order->get_billing_first_name();
			$shipping_last_name = $order->get_shipping_last_name() ? $order->get_shipping_last_name() : $order->get_billing_last_name();
			$shipping_full_name = $shipping_first_name . ' ' . $shipping_last_name;

			$shipping_address_1 = $order->get_shipping_address_1();
			$shipping_address_2 = $order->get_shipping_address_2();
			$shipping_city = $order->get_shipping_city();
			$shipping_state = $order->get_shipping_state();
			$shipping_postcode = $order->get_shipping_postcode();
			$shipping_country = $order->get_shipping_country();

			$full_shipping_address = trim($shipping_address_1 . ' ' . $shipping_address_2 . ', ' . $shipping_city . ', ' . $shipping_state . ' ' . $shipping_postcode . ', ' . $shipping_country);

			$hubon_shipping_methods_data = [];
			foreach ($order->get_items('shipping') as $shipping_item_obj) {
				$shipping_method_id = $shipping_item_obj->get_method_id();
				$code = $shipping_item_obj->get_meta('code');
				if ($shipping_method_id === 'hubon') {
					$hubon_shipping_methods_data[] = [
						'id' => $shipping_method_id,
						'code' => $code
					];
				}
			}

			if (!empty($hubon_shipping_methods_data)) {
				$hubon_secret_key = $hubon_shipping->rest_adapter->hubon_secret_key;
				$customer_info = $hubon_shipping->rest_adapter->customer_info($hubon_secret_key);
				$registered_customer = $customer_info['registered_customer'];
				$pickup_date = $order->get_meta('_pickup_date');

				$formatted_date = "";
				if (!empty($pickup_date)) {
					$dateTime = DateTime::createFromFormat('M d, Y', $pickup_date);
					$formatted_date = $dateTime->format('Y-m-d');
				}

				if (!empty($registered_customer)) {
					$payload = array(
						"sender_id" => $registered_customer['id'],
						"initiator_type" => "sender",
						"payer_type" => "sender",
						"recipient_phone_number" => $shipping_phone,
						"recipient_name" => $shipping_full_name,
						"destination_hub_id" => $hubon_shipping_methods_data['0']['code'],
						"quantity" => $total_qty,
						"category_id" => $registered_customer['setting']['default_category']['id'],
						"hub_storage_type_id" => $registered_customer['setting']['default_storage_type']['id'],
						'pickup_date' =>  $formatted_date,
						"sender_memo" => $this->formatOrder($notes),
						'shipping_address' => $full_shipping_address
					);

					$transport = $this->save_transport($payload);

					if (isset($transport['error'])) {
						$data = [
							'title' => 'Transport Failed #' . $order_id,
							'status' => 'Failed',
							'order_id' => $order_id,
							'response' => wp_json_encode($transport),
							'payload' => wp_json_encode($payload),
						];
						$response = $this->store_hubon_failed_entry($data);
						return $response;
					}

					update_post_meta($order_id, '_hubon_transport_order_created', true);
					return $transport;
				}
			}
		}
	}

	public function save_transport($payload)
	{
		$hubon_shipping = $this->get_hubon_shipping();
		if (!empty($hubon_shipping)) {
			$adapter = new Hubon_Rest_Adapter($hubon_shipping->rest_adapter->hubon_secret_key);
			return $adapter->create_transport($payload);
		}
	}

	private function store_hubon_failed_entry($data)
	{
		if (!is_array($data)) {
			return new WP_Error('invalid_data', 'Provided data must be an array.');
		}

		$post_data = array(
			'post_title'   => isset($data['title']) ? $data['title'] : 'Untitled Failed Entry',
			'post_content' => isset($data['description']) ? $data['description'] : '',
			'post_status'  => 'publish',
			'post_type'    => 'hubon_failed',
		);

		$post_id = wp_insert_post($post_data);

		if (is_wp_error($post_id)) {
			return $post_id;
		}

		$meta_fields = [
			'order_id',
			'response',
			'payload',
			'status'
		];

		foreach ($meta_fields as $field) {
			if (isset($data[$field])) {
				update_post_meta($post_id, $field, sanitize_text_field($data[$field]));
			}
		}

		return $post_id;
	}

	public function add_custom_pickup_date_field($checkout)
	{
		wp_nonce_field('pickup_date_nonce_action', 'pickup_date_nonce');

		woocommerce_form_field('pickup_date', array(
			'type'          => 'text',
			'class'         => array('pickup-date-field form-row-wide'),
			'label'         => __('Choose a pickup date', 'hubon-local-pickup'),
			'placeholder'   => __('Select date', 'hubon-local-pickup'),
			'required'      => true,
		), $checkout->get_value('pickup_date'));
	}

	public function validate_pickup_date_field()
	{
		$pickup_date_nonce = sanitize_text_field($_POST['pickup_date_nonce']);
		$pickup_date = sanitize_text_field($_POST['pickup_date']);

		if (!isset($pickup_date_nonce) || !wp_verify_nonce($pickup_date_nonce, 'pickup_date_nonce_action')) {
			wc_add_notice(__('Nonce verification failed. Please try again.', 'hubon-local-pickup'), 'error');
			return;
		}

		if (empty($pickup_date)) {
			wc_add_notice(__('Please select a pickup date.', 'hubon-local-pickup'), 'error');
		}
	}

	public function save_pickup_date_field($order_id)
	{
		$pickup_date_nonce = sanitize_text_field($_POST['pickup_date_nonce']);
		$pickup_date = sanitize_text_field($_POST['pickup_date']);

		if (!isset($pickup_date_nonce) || !wp_verify_nonce($pickup_date_nonce, 'pickup_date_nonce_action')) {
			return;
		}

		if (!empty($pickup_date)) {
			update_post_meta($order_id, '_pickup_date', $pickup_date);
		}
	}

	function formatOrder($notes)
	{
		if (is_array($notes) && isset($notes['orderId'], $notes['shop'], $notes['orderDetail'])) {
			$result = "Order ID: " . htmlspecialchars($notes['orderId']) . ";" .
				"Store: " . htmlspecialchars($notes['shop']) . ";";

			foreach ($notes['orderDetail'] as $item) {
				$itemName = htmlspecialchars($item['name']);
				$quantity = htmlspecialchars($item['quantity']);
				$result .= "$quantity x $itemName;";
			}

			return $result;
		}

		return "Order ID: " . $notes['orderId'];
	}
}
