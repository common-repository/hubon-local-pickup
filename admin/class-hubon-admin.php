<?php
class Hubon_Admin
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
		$valid_screens = array(
			"toplevel_page_hubon-to-be-paid",
			"hubon-pickup_page_hubon-paid",
			"hubon-pickup_page_hubon-failed",
			"hubon_page_hubon-failed",
		);

		$screen = get_current_screen();
		$query_params = $this->getQueryParams(sanitize_text_field($_SERVER['REQUEST_URI']));

		if (in_array($screen->id, $valid_screens) || (!empty($query_params['section']) && $query_params['section'] === "hubon")) {
			wp_enqueue_style("admin-tailwind", plugin_dir_url(__FILE__) . 'css/tailwind.css', array(), $this->version . "-" . md5(gmdate("Y-m-d H:i:s")));
		}
	}

	private function getQueryParams($url)
	{
		$url = esc_url_raw($url);

		$parsed_url = wp_parse_url($url);
		$query_params = array();

		if (isset($parsed_url['query'])) {
			wp_parse_str($parsed_url['query'], $query_params);

			foreach ($query_params as $key => $value) {
				$query_params[$key] = sanitize_text_field($value);
			}
		}

		return $query_params;
	}

	public function enqueue_scripts()
	{
		$screen = get_current_screen();
		$valid_screens = array(
			"toplevel_page_hubon-to-be-paid",
			"hubon-pickup_page_hubon-paid",
			"hubon-pickup_page_hubon-failed",
			"hubon_page_hubon-failed",
			"edit-hubon_failed"
		);

		$query_params = $this->getQueryParams(sanitize_text_field($_SERVER['REQUEST_URI']));

		if (in_array($screen->id, $valid_screens) || (!empty($query_params['section']) && $query_params['section'] === "hubon")) {
			wp_enqueue_script($this->plugin_name . '-admin', plugin_dir_url(__FILE__) . 'js/index.js', array('jquery'), $this->version . "-" . md5(gmdate("Y-m-d H:i:s")), true);
			if ($screen->id != "woocommerce_page_wc-settings") {
				wp_enqueue_script($this->plugin_name . '-admin-react', plugin_dir_url(__FILE__) . 'js/hubon/index.js', array('jquery'), $this->version . "-" . md5(gmdate("Y-m-d H:i:s")), true);
			}

			$hubon_shipping = $this->get_hubon_shipping();
			$hubon_base_url = "";
			$hubon_secret_key = "";
			$hubon_client_id = "";
			$admin_order_base_url = admin_url('post.php?action=edit&post=');
			$hubon_web_url = HUBON_WEB_URL;
			if (!empty($hubon_shipping)) {
				$hubon_base_url = $hubon_shipping->rest_adapter->base_url;
				$hubon_secret_key = $hubon_shipping->rest_adapter->hubon_secret_key;
				$hubon_client_id = HUBON_CLIENT_ID;
			}
			$data = array(
				'hubonRestApi' => esc_url_raw(rest_url()),
				'hubonBaseUrl' => $hubon_base_url,
				'hubonSecretKey' => $hubon_secret_key,
				'hubonClientId' => $hubon_client_id,
				'adminOrderBaseUrl' => $admin_order_base_url,
				'hubonWebUrl' => $hubon_web_url,
				'screenPageId' => $screen->id
			);
			wp_localize_script($this->plugin_name . '-admin', 'phpVars', $data);
			if ($screen->id != "woocommerce_page_wc-settings") {
				wp_localize_script($this->plugin_name . '-admin-react', 'phpVars', $data);
			}
		}
	}

	public function on_loaded()
	{
		add_action('admin_menu', array($this, 'menu_page'));
		add_action('init', array($this, 'post_type_hubon_failed'));
	}

	public function menu_page()
	{
		add_menu_page(
			__('To be Paid', 'hubon-local-pickup'),
			'HubOn Pickup',
			'manage_woocommerce',
			'hubon-to-be-paid',
			array($this, 'render_admin_home_page'),
			'dashicons-store',
			55
		);

		add_submenu_page(
			'hubon-to-be-paid',
			__('To be Paid', 'hubon-local-pickup'),
			__('To be Paid', 'hubon-local-pickup'),
			'manage_options',
			'hubon-to-be-paid',
			array($this, 'render_admin_home_page'),
		);

		add_submenu_page(
			'hubon-to-be-paid',
			__('Paid', 'hubon-local-pickup'),
			__('Paid', 'hubon-local-pickup'),
			'manage_woocommerce',
			'hubon-paid',
			array($this, 'render_admin_home_page'),
		);

		add_submenu_page(
			'hubon-to-be-paid',
			__('Failed', 'hubon-local-pickup'),
			__('Failed', 'hubon-local-pickup'),
			'manage_woocommerce',
			'hubon-failed',
			array($this, 'render_admin_home_page'),
		);
	}

	public function add_pickup_date_metabox()
	{
		add_meta_box(
			'pickup_date_metabox_id',
			esc_html_e('HubOn Local Pickup', 'hubon-local-pickup'),
			array($this, 'pickup_date_metabox_callback'),
			'shop_order',
			'normal',
			'high'
		);
	}

	public function pickup_date_metabox_callback($post)
	{
		$pickup_date = get_post_meta($post->ID, '_pickup_date', true);
		if (!empty($pickup_date)) {
			$formatted_date = esc_html(date_i18n('F j, Y', strtotime($pickup_date)));
		} else {
			$formatted_date = esc_html_e('No date set', 'hubon-local-pickup');
		}
		echo '<p class="form-field form-field-wide">
		<label>' . esc_html_e('HubOn Pickup Date:', 'hubon-local-pickup') . '</label> <span style="vertical-align: middle;">' . esc_html($formatted_date) . '</span>
		</p> ';
	}

	public function post_type_hubon_failed()
	{
		register_post_type('hubon_failed', array(
			'public' => false,
			'publicly_queryable' => false,
			'show_ui' => true,
			'show_in_menu' => false,
			'label'  => __('HubOn Failed', 'hubon-local-pickup'),
			'menu_icon' => 'dashicons-warning',
			'supports' => array('title'),
			'show_in_nav_menus' => false,
			'show_in_rest' => false,
		));
	}

	function render_admin_home_page()
	{
		$admin_page_path = plugin_dir_path(__FILE__) . "views/home.php";
		if (file_exists($admin_page_path)) include($admin_page_path);
		else echo '<div class="text-center"><p>' . esc_html_e('File not found.', 'hubon-local-pickup') . '</p></div>';
	}

	private function get_hubon_shipping()
	{
		$hubon_shipping = [];
		include_once(ABSPATH . 'wp-admin/includes/plugin.php');
		if (is_plugin_active('woocommerce/woocommerce.php')) {
			$shipping_methods = WC()->shipping()->get_shipping_methods();
			$hubon_shipping = $shipping_methods['hubon'];
		}
		return $hubon_shipping;
	}


	public function register_hubon_failed_get_routes()
	{
		register_rest_route('hubon/v1', '/failed', array(
			'methods' => WP_REST_Server::READABLE,
			'callback' => array($this, 'get_hubon_failed_entries'),
			'permission_callback' => '__return_true',
			'args' => array(
				'page' => array(
					'validate_callback' => function ($param) {
						return is_numeric($param);
					},
					'default' => 1,
				),
				'per_page' => array(
					'validate_callback' => function ($param) {
						return is_numeric($param) && $param > 0 && $param <= 100;
					},
					'default' => 10,
				)
			),
		));

		register_rest_route('hubon/v1', '/failed/(?P<id>\d+)', array(
			'methods' => WP_REST_Server::DELETABLE,
			'callback' => array($this, 'delete_hubon_failed_entry'),
			'permission_callback' => '__return_true',
			'args' => array(
				'id' => array(
					'validate_callback' => function ($param) {
						return is_numeric($param);
					},
					'required' => true,
					'description' => 'Unique identifier for the entry.',
				),
				'pickup_date' => array(
					'validate_callback' => function ($param) {
						return strtotime($param) !== false;
					},
					'required' => false
				)
			),
		));
	}

	public function delete_hubon_failed_entry($request)
	{
		$entry_id = (int) $request->get_param('id');
		$order_id = (int) $request->get_param('order_id');
		$pickup_date = $request->get_param('pickup_date');

		$post = get_post($entry_id);
		if (empty($post) || $post->post_type !== 'hubon_failed') {
			return new WP_Error('no_entry_found', 'No entry found with that ID.', array('status' => 404));
		}

		if (!empty($pickup_date)) {
			$dateTime = DateTime::createFromFormat('Y-m-d', $pickup_date);
			$formatted_date = $dateTime->format('M d, Y');
			update_post_meta($order_id, '_pickup_date', sanitize_text_field($formatted_date));
		}

		$result = wp_delete_post($entry_id, true);

		if ($result) {
			return new WP_REST_Response(array('message' => 'Deleted successfully', 'id' => $entry_id), 200);
		} else {
			return new WP_Error('delete_failed', 'Failed to delete the entry.', array('status' => 500));
		}
	}

	public function get_hubon_failed_entries($request)
	{
		$args = array(
			'post_type' => 'hubon_failed',
			'post_status' => 'publish',
			'posts_per_page' => $request['per_page'],
			'paged' => $request['page'],
			'orderby' => 'date',
			'order' => 'DESC'
		);

		$query = new WP_Query($args);
		$posts = [];

		if ($query->have_posts()) {
			while ($query->have_posts()) {
				$query->the_post();
				$post_id = get_the_ID();
				$posts[] = array(
					'key' => $post_id,
					'id' => $post_id,
					'status' => get_post_meta($post_id, 'status', true) ?: "Failed",
					'title' => get_the_title(),
					'order_id' => get_post_meta($post_id, 'order_id', true),
					'response' => json_decode(get_post_meta($post_id, 'response', true)),
					'payload' => json_decode(get_post_meta($post_id, 'payload', true)),
				);
			}
		}

		$total = $query->found_posts;
		$total_pages = $query->max_num_pages;
		$current_page = (int) $request['page'];
		$per_page = (int) $request['per_page'];
		$next_page = ($current_page < $total_pages) ? $current_page + 1 : null;
		$prev_page = ($current_page > 1) ? $current_page - 1 : null;

		$response = new WP_REST_Response(array(
			'posts' => $posts,
			'pagination' => array(
				'total' => $total,
				'total_pages' => $total_pages,
				'current_page' => $current_page,
				'next_page' => $next_page,
				'prev_page' => $prev_page,
				'per_page' => $per_page,
			),
		), 200);

		return $response;
	}
}
