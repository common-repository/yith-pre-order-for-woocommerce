<?php
/**
 * This file belongs to the YIT Framework.
 *
 * This source file is subject to the GNU GENERAL PUBLIC LICENSE (GPL 3.0)
 * that is bundled with this package in the file LICENSE.txt.
 * It is also available through the world-wide-web at this URL:
 * http://www.gnu.org/licenses/gpl-3.0.txt
 *
 * @package YITH\PreOrder\Includes
 * @author YITH <plugins@yithemes.com>
 */

if ( ! defined( 'YITH_WCPO_VERSION' ) ) {
	exit( 'Direct access forbidden.' );
}

if ( ! class_exists( 'YITH_Pre_Order_Admin' ) ) {
	/**
	 * Class YITH_Pre_Order_Admin
	 */
	class YITH_Pre_Order_Admin {

		/**
		 * YIT_Plugin_Panel_WooCommerce instance.
		 *
		 * @var YIT_Plugin_Panel_WooCommerce object
		 */
		protected $panel = null;

		/**
		 * Panel page slug.
		 *
		 * @var string
		 */
		protected $panel_page = 'yith_wcpo_panel';

		/**
		 * Show the premium landing page.
		 *
		 * @var bool
		 */
		public $show_premium_landing = true;

		/**
		 * Official plugin documentation URL.
		 *
		 * @var string
		 */
		protected $official_documentation = 'https://docs.yithemes.com/yith-woocommerce-pre-order/';

		/**
		 * Official plugin landing page URL.
		 *
		 * @var string
		 */
		protected $premium_landing = 'https://yithemes.com/themes/plugins/yith-woocommerce-pre-order/';

		/**
		 * Official plugin live demo page URL.
		 *
		 * @var string
		 */
		protected $premium_live = 'https://plugins.yithemes.com/yith-woocommerce-pre-order/';

		/**
		 * Single instance of the class YITH_Pre_Order_Admin.
		 *
		 * @var YITH_Pre_Order_Admin
		 * @since 1.0.0
		 */
		protected static $instance;

		/**
		 * Single instance of the class YITH_Pre_Order_Edit_Product_Page for backward compatibility.
		 *
		 * @var YITH_Pre_Order_Edit_Product_Page $edit_product_page Edit product page object.
		 */
		public $edit_product_page;

		/**
		 * Returns single instance of the class
		 *
		 * @return YITH_Pre_Order_Admin
		 * @since 1.0.0
		 */
		public static function get_instance() {
			if ( is_null( self::$instance ) ) {
				self::$instance = new self();
			}

			return self::$instance;
		}

		/**
		 * Construct
		 *
		 * @since 1.0
		 */
		public function __construct() {
			$this->edit_product_page = YITH_Pre_Order_Edit_Product_Page();

			/* === Register Panel Settings === */
			add_action( 'admin_menu', array( $this, 'register_panel' ), 5 );
			/* === Premium Tab === */
			add_action( 'yith_ywpo_pre_order_premium_tab', array( $this, 'show_premium_landing' ) );

			/* === Show Plugin Information === */
			add_filter( 'plugin_action_links_' . plugin_basename( YITH_WCPO_PATH . '/' . basename( YITH_WCPO_FILE ) ), array( $this, 'action_links' ) );
			add_filter( 'yith_show_plugin_row_meta', array( $this, 'plugin_row_meta' ), 10, 5 );
			add_filter( 'woocommerce_locate_core_template', array( $this, 'locate_core_template' ), 10, 2 );
		}

		/**
		 * Add a panel under YITH Plugins tab
		 *
		 * @return   void
		 * @since    1.0
		 * @use      /Yit_Plugin_Panel class
		 * @see      plugin-fw/lib/yit-plugin-panel.php
		 */
		public function register_panel() {

			if ( ! empty( $this->panel ) ) {
				return;
			}

			$args = array(
				'create_menu_page' => true,
				'parent_slug'      => '',
				'plugin_slug'      => YITH_WCPO_SLUG,
				'premium_tab'      => $this->get_premium_tab(),
				'page_title'       => 'YITH Pre-Order for WooCommerce',
				'menu_title'       => 'Pre-Order',
				'capability'       => 'manage_options',
				'parent'           => '',
				'parent_page'      => 'yith_plugin_panel',
				'page'             => $this->panel_page,
				'admin-tabs'       => $this->get_panel_tabs(),
				'class'            => yith_set_wrapper_class(),
				'options-path'     => YITH_WCPO_OPTIONS_PATH,
				'is_free'          => defined( 'YITH_WCPO_FREE_INIT' ),
				'is_premium'       => defined( 'YITH_WCPO_PREMIUM' ),
				'is_extended'      => false,
				'ui_version'       => 2,
			);

			/* === Fixed: not updated theme/old plugin framework  === */
			if ( ! class_exists( 'YIT_Plugin_Panel_WooCommerce' ) ) {
				require_once YITH_WCPO_PATH . '/plugin-fw/lib/yit-plugin-panel-wc.php';
			}

			$this->panel = new YIT_Plugin_Panel_WooCommerce( $args );
		}

		/**
		 * Get an array of panel tabs
		 *
		 * @since  3.0.0
		 * @return array
		 */
		public function get_panel_tabs() {
			return apply_filters(
				'yith_wcpo_free_admin_tabs',
				array(
					'general'       => array(
						'title'       => __( 'General Options', 'yith-pre-order-for-woocommerce' ),
						'description' => __( 'Set the general behavior of the plugin.', 'yith-pre-order-for-woocommerce' ),
						'icon'        => 'settings',
					),
					'customization'         => array(
						'title'       => __( 'Customization', 'yith-pre-order-for-woocommerce' ),
						'description' => __( 'Set custom labels to customize the pre-order options.', 'yith-pre-order-for-woocommerce' ),
						'icon'        => '<svg fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M15 11.25l1.5 1.5.75-.75V8.758l2.276-.61a3 3 0 10-3.675-3.675l-.61 2.277H12l-.75.75 1.5 1.5M15 11.25l-8.47 8.47c-.34.34-.8.53-1.28.53s-.94.19-1.28.53l-.97.97-.75-.75.97-.97c.34-.34.53-.8.53-1.28s.19-.94.53-1.28L12.75 9M15 11.25L12.75 9"></path></svg>',
					)
				)
			);
		}

		/**
		 * Get an array of panel tabs
		 *
		 * @since  3.0.0
		 * @return array
		 */
		public function get_premium_tab() {
			return apply_filters(
				'yith_wcpo_premium_tab',
				array(
					'landing_page_url' => $this->get_premium_landing_uri(),
					'features' => array(
						array(
							'title'       => __( 'Enable pre-order mode on out-of-stock products automatically', 'yith-pre-order-for-woocommerce' ),
							'description' => __( 'By automatically enabling pre-order mode for out-of-stock products, your customers can order products that are temporarily unavailable or out of stock. This way, you can avoid losing orders while also getting cash flow up front.', 'yith-pre-order-for-woocommerce' ),
						),
						array(
							'title'       => __( 'Allow pre-orders only for specific users or user roles', 'yith-pre-order-for-woocommerce' ),
							'description' => __( 'In the premium version, you can decide whether only specific customers or specific user roles can pre-order the products you sell.', 'yith-pre-order-for-woocommerce' ),
						),
						array(
							'title'       => __( 'Charge a "fee" to customers who pre-order your products', 'yith-pre-order-for-woocommerce' ),
							'description' => __( 'You can charge an additional fee for pre-ordered products by configuring a global cost for all products, and you can even override the cost for specific products.', 'yith-pre-order-for-woocommerce' ),
						),
						array(
							'title'       => __( 'Offer a pre-order discount', 'yith-pre-order-for-woocommerce' ),
							'description' => __( 'Apply a fixed or percentage discount off the product price for all users who order before the product is available in the shop. Offering products at a special price is a powerful strategy to leverage the principle of urgency and encourage customers to order.', 'yith-pre-order-for-woocommerce' ),
						),
						array(
							'title'       => __( 'Enable the "Pay Later" option to prompt customers to manually pay when the product is available', 'yith-pre-order-for-woocommerce' ),
							'description' => __( 'Enable the "Pay Later" method at checkout so that customers receive an email notification when the product is available in your store, prompting them to pay for the order.', 'yith-pre-order-for-woocommerce' ),
						),
						array(
							'title'       => __( 'Charge your customers\' credit card automatically when the product is available in your shop ', 'yith-pre-order-for-woocommerce' ),
							'description' => __( 'By using the plugin together with YITH Stripe, YITH Stripe Connect, or YITH Braintree, you can request customers to enter their credit card details so that you can automatically charge them only when the product is available. This solution will reduce the number of unpaid pre-orders and make them easier to manage.', 'yith-pre-order-for-woocommerce' ),
						),
						array(
							'title'       => __( 'Offer free shipping on pre-order product purchases', 'yith-pre-order-for-woocommerce' ),
							'description' => __( 'Do you want to add an incentive to push customers to pre-order products? Well, with just one click, you can offer free shipping on all orders that include a pre-order product.', 'yith-pre-order-for-woocommerce' ),
						),
						array(
							'title'       => __( 'Manage advanced admin and customer notifications', 'yith-pre-order-for-woocommerce' ),
							'description' => __( 'Send automated emails to customers to confirm their pre-orders and keep them updated on the status and availability date. The plugin also includes several notifications for the admin to easily manage pre-orders and products.', 'yith-pre-order-for-woocommerce' ),
						),
					),
					'main_image_url'   => YITH_WCPO_ASSETS_URL . 'images/get-premium-preorder.jpg',
				)
			);
		}

		/**
		 * Premium Tab Template
		 *
		 * Load the premium tab template on admin page
		 *
		 * @since    1.0
		 * @return void
		 */
		public function show_premium_landing() {
			if ( file_exists( YITH_WCPO_TEMPLATE_PATH . 'admin/premium_tab.php' ) && $this->show_premium_landing ) {
				require_once YITH_WCPO_TEMPLATE_PATH . 'admin/premium_tab.php';
			}
		}

		/**
		 * Get the premium landing uri
		 *
		 * @since   1.0.0
		 * @return  string The premium landing link
		 */
		public function get_premium_landing_uri() {
			return $this->premium_landing;
		}

		/**
		 * Action Links
		 *
		 * Add the action links to plugin admin page.
		 *
		 * @param array $links .
		 *
		 * @return mixed
		 * @use      plugin_action_links_{$plugin_file_name}
		 * @since    1.0
		 */
		public function action_links( $links ) {
			$links = yith_add_action_links( $links, $this->panel_page, false, YITH_WCPO_SLUG );
			return $links;
		}

		/**
		 * Plugin_row_meta
		 *
		 * Add the action links to plugin admin page.
		 *
		 * @param array  $new_row_meta_args .
		 * @param array  $plugin_meta .
		 * @param string $plugin_file .
		 * @param array  $plugin_data .
		 * @param string $status .
		 * @param string $init_file .
		 *
		 * @return   array
		 * @use      plugin_row_meta
		 */
		public function plugin_row_meta( $new_row_meta_args, $plugin_meta, $plugin_file, $plugin_data, $status, $init_file = 'YITH_WCPO_FREE_INIT' ) {
			if ( defined( $init_file ) && constant( $init_file ) === $plugin_file ) {
				$new_row_meta_args['slug'] = YITH_WCPO_SLUG;
			}

			return $new_row_meta_args;
		}

		/**
		 * Locate core template file
		 *
		 * @param string $core_file Template full path.
		 * @param string $template Template in use.
		 *
		 * @return string
		 * @since  1.0.0
		 */
		public function locate_core_template( $core_file, $template ) {
			$custom_template = array(
				'emails/ywpo-email-admin-new-pre-order',
				'emails/ywpo-email-user-pre-order-confirmed.php',
			);

			if ( in_array( $template, $custom_template, true ) ) {
				$core_file = YITH_WCPO_TEMPLATE_PATH . $template;
			}

			return $core_file;
		}
	}
}

/**
 * Unique access to instance of YITH_Pre_Order_Admin class
 *
 * @return YITH_Pre_Order_Admin
 */
function YITH_Pre_Order_Admin() { // phpcs:ignore WordPress.NamingConventions.ValidFunctionName.FunctionNameInvalid
	return YITH_Pre_Order_Admin::get_instance();
}
