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

if ( ! class_exists( 'YITH_Pre_Order_Utils' ) ) {
	/**
	 * Class YITH_Pre_Order_Utils
	 */
	class YITH_Pre_Order_Utils {

		/**
		 * Check if a product can be displayed as pre-order in the shop.
		 *
		 * @param WC_Product|int $product The WC_Product object or product ID.
		 *
		 * @return bool
		 */
		public static function is_pre_order_active( $product ) {
			$return = false;

			if ( $product instanceof WC_Product ) {
				$product_id = $product->get_id();
			} else {
				$product_id = is_numeric( $product ) && $product > 0 ? $product : false;
			}

			if ( ! $product_id ) {
				return $return;
			}

			if ( function_exists( 'wpml_get_default_language' ) ) {
				$product_id = apply_filters( 'wpml_object_id', $product_id, 'product', true, wpml_get_default_language() );
			}

			if ( function_exists( 'YITH_Pre_Order_Frontend' ) ) {
				if ( isset( YITH_Pre_Order_Frontend()->pre_orders[ $product_id ] ) ) {
					$product = YITH_Pre_Order_Frontend()->pre_orders[ $product_id ];
				} else {
					$product = wc_get_product( $product_id );
					if ( $product && $product->exists() ) {
						YITH_Pre_Order_Frontend()->pre_orders[ $product_id ] = $product;
					}
				}
			}

			if ( 'simple' === $product->get_type() || 'variation' === $product->get_type() ) {
				// Backward compatibility.
				if ( apply_filters( 'ywpo_enable_backward_compatibility_mode', false, $product ) ) {
					return 'yes' === self::get_pre_order_status( $product );
				}
				if ( function_exists( 'ywpo_automatic_pre_order_check' ) && ywpo_automatic_pre_order_check( $product ) ) {
					$return = true;
				} elseif ( 'yes' === self::get_pre_order_status( $product ) && 'outofstock' !== $product->get_stock_status( 'edit' ) ) {
					if ( function_exists( 'ywpo_product_is_eligible_for_auto_pre_order' ) ) {
						$auto_pre_order_condition = ywpo_product_is_eligible_for_auto_pre_order( $product );
						if (
							( $auto_pre_order_condition && 'manual' === self::get_override_pre_order_mode( $product ) ) ||
							( ! $auto_pre_order_condition && 'manual' === self::get_pre_order_mode( $product ) )
						) {
							$return = true;
						}
					} else {
						$return = true;
					}

					if (
						'yes' === get_option( 'yith_wcpo_enable_pre_order_purchasable', 'yes' ) &&
						'date' === self::get_availability_date_mode( $product ) &&
						time() > self::get_for_sale_date_timestamp( $product )
					) {
						ywpo_reset_pre_order( $product );
						$return = false;
					}
				}
			}

			return apply_filters( 'ywpo_is_pre_order_active', $return, $product );
		}

		/**
		 * Get the pre-order status value. This value defines if a product is a pre-order or not, but not if the
		 * pre-order is currently active. For checking if a pre-order product is active,
		 * see YITH_Pre_Order_Utils::is_pre_order_active().
		 *
		 * @return string
		 */
		public static function get_pre_order_status( $product ) {
			$pre_order_status = $product->get_meta( '_ywpo_preorder' );

			return apply_filters( 'ywpo_pre_order_get_status', $pre_order_status, $product );
		}

		/**
		 * Get the pre-order mode value.
		 *
		 * @return string
		 */
		public static function get_pre_order_mode( $product ) {
			$pre_order_mode = $product->get_meta( '_ywpo_pre_order_mode' );

			return apply_filters( 'ywpo_pre_order_get_mode', $pre_order_mode, $product );
		}

		/**
		 * Get the override pre-order mode value.
		 *
		 * @return string
		 */
		public static function get_override_pre_order_mode( $product ) {
			$pre_order_mode = $product->get_meta( '_ywpo_override_pre_order_mode' );

			return apply_filters( 'ywpo_pre_order_get_override_mode', $pre_order_mode, $product );
		}

		/**
		 * Get the pre-order start mode.
		 *
		 * @return string
		 */
		public static function get_start_mode( $product ) {
			$start_mode = $product->get_meta( '_ywpo_start_mode' );

			return apply_filters( 'ywpo_pre_order_get_start_mode', $start_mode, $product );
		}

		/**
		 * Get the start date in human-readable format.
		 *
		 * @return string
		 */
		public static function get_start_date( $product ) {
			$timestamp  = $product->get_meta( '_ywpo_start_date' );
			$start_date = ! empty( $timestamp ) ? get_date_from_gmt( gmdate( 'Y-m-d H:i:s', (int) $timestamp ) ) : '';

			return apply_filters( 'ywpo_pre_order_get_start_date', $start_date, $product );
		}

		/**
		 * Get the start date in timestamp format.
		 *
		 * @return string
		 */
		public static function get_start_date_timestamp( $product ) {
			$start_date = $product->get_meta( '_ywpo_start_date' );

			return apply_filters( 'ywpo_pre_order_get_start_date_timestamp', $start_date, $product );
		}

		/**
		 * Get the custom text for the start date.
		 *
		 * @return string
		 */
		public static function get_start_date_label( $product ) {
			$start_date_label = $product->get_meta( '_ywpo_start_date_label' );

			return apply_filters( 'ywpo_pre_order_get_start_date_label', $start_date_label, $product );
		}

		/**
		 * Get the value for the availability date mode. Possible values are 'no_date', 'date' and 'dynamic'.
		 *
		 * @return string
		 */
		public static function get_availability_date_mode( $product ) {
			$availability_date_mode = $product->get_meta( '_ywpo_availability_date_mode' );

			return apply_filters( 'ywpo_pre_order_get_availability_date_mode', $availability_date_mode, $product );
		}

		/**
		 * Get the availability date in human-readable format.
		 *
		 * @return string
		 */
		public static function get_for_sale_date( $product ) {
			$timestamp     = $product->get_meta( '_ywpo_for_sale_date' );
			$for_sale_date = ! empty( $timestamp ) ? get_date_from_gmt( gmdate( 'Y-m-d H:i:s', (int) $timestamp ) ) : '';

			return apply_filters( 'ywpo_pre_order_get_for_sale_date', $for_sale_date, $product );
		}

		/**
		 * Get the availability date in timestamp format.
		 *
		 * @return string
		 */
		public static function get_for_sale_date_timestamp( $product ) {
			$timestamp = $product->get_meta( '_ywpo_for_sale_date' );

			return apply_filters( 'ywpo_pre_order_get_for_sale_date_timestamp', $timestamp, $product );
		}

		/**
		 * Calculates the availability date for a product, having a fixed date or a dynamic date.
		 *
		 * @return string
		 */
		public static function calculate_availability_date_timestamp( $product ) {
			$timestamp               = '';
			$availability_date_mode  = self::get_availability_date_mode( $product );
			$days                    = self::get_dynamic_availability_date( $product );
			$for_sale_date_timestamp = self::get_for_sale_date_timestamp( $product );

			// For backward compatibility.
			if ( $for_sale_date_timestamp && ! $availability_date_mode ) {
				$timestamp = $for_sale_date_timestamp;
			}

			if ( 'dynamic' === $availability_date_mode ) {
				$timestamp = time() + ( (int) $days * DAY_IN_SECONDS );
			} elseif ( 'date' === $availability_date_mode ) {
				$timestamp = $for_sale_date_timestamp;
			}

			return apply_filters( 'ywpo_pre_order_calculate_availability_date_timestamp', $timestamp, $availability_date_mode, $days, $for_sale_date_timestamp );
		}

		/**
		 * Get the number of days for the dynamic availability date.
		 *
		 * @return string
		 */
		public static function get_dynamic_availability_date( $product ) {
			$dynamic_availability_date = $product->get_meta( '_ywpo_dynamic_availability_date' );

			return apply_filters( 'yith_ywpo_pre_order_get_dynamic_availability_date', $dynamic_availability_date, $product );
		}

		/**
		 * Get the value for the price mode. Possible values are 'default', 'fixed', 'discount_percentage',
		 * 'discount_fixed', 'increase_percentage' and 'increase_fixed'.
		 *
		 * @return string
		 */
		public static function get_price_mode( $product ) {
			$price_mode = $product->get_meta( '_ywpo_price_mode' );

			return apply_filters( 'yith_ywpo_pre_order_get_price_mode', $price_mode, $product );
		}

		/**
		 * Get the value to check if the Maximum Quantity option is enabled for this pre-order product.
		 *
		 * @return string
		 */
		public static function get_max_qty_enabled( $product ) {
			$max_qty_enabled = $product->get_meta( '_ywpo_max_qty_enabled' );

			return apply_filters( 'yith_ywpo_pre_order_get_max_qty_enabled', $max_qty_enabled, $product );
		}

		/**
		 * Get the maximum number of items that can be pre-ordered.
		 *
		 * @return string
		 */
		public static function get_max_qty( $product ) {
			$max_qty = $product->get_meta( '_ywpo_max_qty' );

			return apply_filters( 'yith_ywpo_pre_order_get_max_qty', $max_qty, $product );
		}

		/**
		 * Get the value to check if the pre-order labels are overridden for this pre-order product.
		 *
		 * @return string
		 */
		public static function get_override_labels( $product ) {
			$override_labels = $product->get_meta( '_ywpo_override_labels' );

			return apply_filters( 'yith_ywpo_pre_order_get_override_labels', $override_labels, $product );
		}

		/**
		 * Get the text that substitutes the Add to cart button text.
		 *
		 * @return string
		 */
		public static function get_pre_order_label( $product ) {
			$pre_order_label = $product->get_meta( '_ywpo_preorder_label' );

			return apply_filters( 'yith_ywpo_pre_order_get_label', $pre_order_label, $product );
		}

		/**
		 * Get the text that announces the product's release date.
		 *
		 * @return string
		 */
		public static function get_pre_order_availability_date_label( $product ) {
			$pre_order_availability_date_label = $product->get_meta( '_ywpo_preorder_availability_date_label' );

			return apply_filters( 'yith_ywpo_pre_order_get_availability_date_label', $pre_order_availability_date_label, $product );
		}

		/**
		 * Get the text to display when no release date is set for the pre-order product.
		 *
		 * @return string
		 */
		public static function get_pre_order_no_date_label( $product ) {
			$pre_order_no_date_label = $product->get_meta( '_ywpo_preorder_no_date_label' );

			return apply_filters( 'yith_ywpo_pre_order_get_no_date_label', $pre_order_no_date_label, $product );
		}

		/**
		 * Get the value to check if the pre-order fee is overridden for this pre-order product.
		 *
		 * @return string
		 */
		public static function get_override_fee( $product ) {
			$override_fee = $product->get_meta( '_ywpo_override_fee' );

			return apply_filters( 'yith_ywpo_pre_order_get_override_fee', $override_fee, $product );
		}

		/**
		 * Get the fee amount value.
		 *
		 * @return string
		 */
		public static function get_fee( $product ) {
			$fee = $product->get_meta( '_ywpo_fee' );

			return apply_filters( 'yith_ywpo_pre_order_get_fee', $fee, $product );
		}

		/**
		 * Get the value to check if the charge type is overridden for this pre-order product.
		 *
		 * @return string
		 */
		public static function get_override_charge_type( $product ) {
			$override_charge_type = $product->get_meta( '_ywpo_override_charge_type' );

			return apply_filters( 'yith_ywpo_pre_order_get_override_charge_type', $override_charge_type, $product );
		}

		/**
		 * Get the charge type.
		 *
		 * @return string
		 */
		public static function get_charge_type( $product ) {
			$charge_type = $product->get_meta( '_ywpo_charge_type' );

			return apply_filters( 'yith_ywpo_pre_order_get_charge_type', $charge_type, $product );
		}

		/**
		 * Get the pre-order price.
		 *
		 * @return string
		 */
		public static function get_pre_order_price( $product ) {
			$pre_order_price = $product->get_meta( '_ywpo_preorder_price' );

			return apply_filters( 'yith_ywpo_pre_order_get_price', $pre_order_price, $product );
		}

		/**
		 * Get the value for the percentage amount for discount.
		 *
		 * @return string
		 */
		public static function get_discount_percentage( $product ) {
			$discount_percentage = $product->get_meta( '_ywpo_preorder_discount_percentage' );

			return apply_filters( 'yith_ywpo_pre_order_get_discount_percentage', $discount_percentage, $product );
		}

		/**
		 * Get the value for the fixed amount for discount.
		 *
		 * @return string
		 */
		public static function get_discount_fixed( $product ) {
			$discount_fixed = $product->get_meta( '_ywpo_preorder_discount_fixed' );

			return apply_filters( 'yith_ywpo_pre_order_get_discount_fixed', $discount_fixed, $product );
		}

		/**
		 * Get the value for the percentage amount for increase.
		 *
		 * @return string
		 */
		public static function get_increase_percentage( $product ) {
			$increase_percentage = $product->get_meta( '_ywpo_preorder_increase_percentage' );

			return apply_filters( 'yith_ywpo_pre_order_get_increase_percentage', $increase_percentage, $product );
		}

		/**
		 * Get the value for the fixed amount for increase.
		 *
		 * @return string
		 */
		public static function get_increase_fixed( $product ) {
			$increase_fixed = $product->get_meta( '_ywpo_preorder_increase_fixed' );

			return apply_filters( 'yith_ywpo_pre_order_get_increase_fixed', $increase_fixed, $product );
		}

		/**
		 * Kept for backward compatibility. Get the adjustment amount.
		 *
		 * @return string
		 */
		public static function get_pre_order_adjustment_amount( $product ) {
			$price_adjustment_amount = $product->get_meta( '_ywpo_price_adjustment_amount' );

			return apply_filters( 'yith_ywpo_pre_order_get_adjustment_amount', $price_adjustment_amount, $product );
		}

		/**
		 * Kept for backward compatibility.
		 *
		 * @return string
		 */
		public static function get_pre_order_price_adjustment( $product ) {
			$price_adjustment = $product->get_meta( '_ywpo_price_adjustment' );
			$price_adjustment = empty( $price_adjustment ) ? 'manual' : $price_adjustment;

			return apply_filters( 'yith_ywpo_pre_order_get_price_adjustment', $price_adjustment, $product );
		}

		/**
		 * Kept for backward compatibility.
		 *
		 * @return string
		 */
		public static function get_pre_order_adjustment_type( $product ) {
			$adjustment_type = $product->get_meta( '_ywpo_adjustment_type' );

			return apply_filters( 'yith_ywpo_pre_order_get_adjustment_type', $adjustment_type, $product );
		}

		// Setters.

		/**
		 * Set the pre-order status. Possible values are 'yes' or 'no'.
		 *
		 * @param string $status Pre-order status.
		 */
		public static function set_pre_order_status( $product, $status ) {
			$old_status = self::get_pre_order_status( $product );
			$product->update_meta_data( '_ywpo_preorder', $status );
			do_action( 'yith_ywpo_pre_order_status_changed', $product, $status, $old_status );
			$product->save();
		}

		/**
		 * Set the pre-order mode. This is the option used when the product isn't selected for the automatic
		 * pre-order mode. Possible values are 'manual' or 'auto'.
		 * When mode is 'manual', the pre-order will work as usual. When it's 'auto', the pre-order will be available
		 * only when the product is out-of-stock.
		 *
		 * @param string $mode Pre-order mode.
		 */
		public static function set_pre_order_mode( $product, $mode ) {
			$product->update_meta_data( '_ywpo_pre_order_mode', $mode );
			do_action( 'yith_ywpo_pre_order_mode_changed', $product, $mode );
			$product->save();
		}

		/**
		 * Set the override pre-order mode. Possible values are 'yes' or 'no'.
		 *
		 * @param string $mode Option value.
		 */
		public static function set_override_pre_order_mode( $product, $mode ) {
			$product->update_meta_data( '_ywpo_override_pre_order_mode', $mode );
			do_action( 'yith_ywpo_override_pre_order_mode_changed', $product, $mode );
			$product->save();
		}

		/**
		 * Set the start mode for the pre-order product. Possible values are:
		 * - 'now':  There is no start date, the product is available for pre-order immediately.
		 * - 'date': There is a start date. The product cannot be pre-ordered until this date arrives.
		 *
		 * @param string $start_mode Start mode.
		 */
		public static function set_start_mode( $product, $start_mode ) {
			$product->update_meta_data( '_ywpo_start_mode', $start_mode );
			do_action( 'yith_ywpo_start_mode_changed', $product, $start_mode );
			$product->save();
		}

		/**
		 * Set the start date used when the start date mode is 'date'. The value entered must be in timestamp format.
		 *
		 * @param string $start_date The start date in timestamp format.
		 */
		public static function set_start_date( $product, $start_date ) {
			if ( ! empty( $start_date ) ) {
				$formatted_date = get_gmt_from_date( $start_date );
				$product->update_meta_data( '_ywpo_start_date', $formatted_date ? strtotime( $formatted_date ) : '' );
				do_action( 'yith_ywpo_start_date_changed', $product, $start_date );
			} else {
				$product->update_meta_data( '_ywpo_start_date', '' );
			}
			$product->save();
		}

		/**
		 * Set the value for the start date label.
		 *
		 * @param string $start_date_label Start date label.
		 */
		public static function set_start_date_label( $product, $start_date_label ) {
			$product->update_meta_data( '_ywpo_start_date_label', $start_date_label );
			do_action( 'yith_ywpo_start_date_label_changed', $product, $start_date_label );
			$product->save();
		}

		/**
		 * Set the availability date mode. Possible values are:
		 * - 'no_date': The pre-order doesn't have a release date. It must be manually completed by the admin.
		 * - 'date':    There is a release date for the pre-order. The pre-order will be automatically completed on the
		 *              release date.
		 * - 'dynamic': The release date is calculated X days after the customer places the pre-order.
		 *
		 * @param string $availability_date_mode The availability date mode.
		 */
		public static function set_availability_date_mode( $product, $availability_date_mode ) {
			$product->update_meta_data( '_ywpo_availability_date_mode', $availability_date_mode );
			do_action( 'yith_ywpo_availability_date_mode_changed', $product, $availability_date_mode );
			$product->save();
		}

		/**
		 * Set the release date for the pre-order in timestamp format.
		 *
		 * @param string $date The release date in timestamp format.
		 */
		public static function set_for_sale_date( $product, $date ) {
			if ( ! empty( $date ) ) {
				$format_date = get_gmt_from_date( $date );
				$product->update_meta_data( '_ywpo_for_sale_date', $format_date ? strtotime( $format_date ) : '' );
				do_action( 'yith_ywpo_pre_order_date_changed', $product, $date );
			} else {
				$product->update_meta_data( '_ywpo_for_sale_date', '' );
			}
			$product->save();
		}

		/**
		 * Set the dynamic availability date. This value is not a date, it's a number of days in order to calculate the
		 * release date dynamically based on the day the product is pre-ordered.
		 * Valid only if the availability date mode is 'dynamic'. See get_availability_date_mode().
		 *
		 * @param string $dynamic_availability_date Number of days to calculate the release date dynamically.
		 */
		public static function set_dynamic_availability_date( $product, $dynamic_availability_date ) {
			$product->update_meta_data( '_ywpo_dynamic_availability_date', $dynamic_availability_date );
			do_action( 'yith_ywpo_dynamic_availability_date_changed', $product, $dynamic_availability_date );
			$product->save();
		}

		/**
		 * Set the price mode. Possible values are:
		 * - 'default':             Use the product's regular/sale price.
		 * - 'fixed:                Use a fixed pre-order price for this product.
		 * - 'discount_percentage': Apply a percentage discount over the product's price.
		 * - 'discount_fixed':      Apply a fixed amount discount over the product's price.
		 * - 'increase_percentage': Apply a percentage increase over the product's price.
		 * - 'increase_fixed':      Apply a fixed amount increase over the product's price.
		 *
		 * @param string $price_mode The price mode.
		 */
		public static function set_price_mode( $product, $price_mode ) {
			$product->update_meta_data( '_ywpo_price_mode', $price_mode );
			do_action( 'yith_ywpo_price_mode_changed', $product, $price_mode );
			$product->save();
		}

		/**
		 * Set if the max qty feature is enabled for this pre-order product.
		 *
		 * @param string $max_qty_enabled Whether is the feature is enabled or not.
		 */
		public static function set_max_qty_enabled( $product, $max_qty_enabled ) {
			$product->update_meta_data( '_ywpo_max_qty_enabled', $max_qty_enabled );
			do_action( 'yith_ywpo_max_qty_enabled_changed', $product, $max_qty_enabled );
			$product->save();
		}

		/**
		 * The number of maximum quantity of items that can be pre-ordered for this product.
		 *
		 * @param string $max_qty The option value.
		 */
		public static function set_max_qty( $product, $max_qty ) {
			$product->update_meta_data( '_ywpo_max_qty', $max_qty );
			do_action( 'yith_ywpo_max_qty_changed', $product, $max_qty );
			$product->save();
		}

		/**
		 * Set if the pre-order labels ('_ywpo_preorder_label', '_ywpo_preorder_availability_date_label' and
		 * '_ywpo_preorder_no_date_label') are overridden or not.
		 *
		 * @param string $override_labels The option value.
		 */
		public static function set_override_labels( $product, $override_labels ) {
			$product->update_meta_data( '_ywpo_override_labels', $override_labels );
			do_action( 'yith_ywpo_override_labels_changed', $product, $override_labels );
			$product->save();
		}

		/**
		 * Set the text that substitutes the Add to cart button text. If '_ywpo_override_labels' is 'yes', this option
		 * will be used rather than the global option.
		 *
		 * @param string $pre_order_label The option value.
		 */
		public static function set_pre_order_label( $product, $pre_order_label ) {
			if ( isset( $pre_order_label ) ) {
				$product->update_meta_data( '_ywpo_preorder_label', $pre_order_label );
				do_action( 'yith_ywpo_pre_order_label_changed', $product, $pre_order_label );
				$product->save();
			}
		}

		/**
		 * Set the text that announces the product's release date. If '_ywpo_override_labels' is 'yes', this option
		 * will be used rather than the global option.
		 *
		 * @param string $pre_order_availability_date_label The option value.
		 */
		public static function set_pre_order_availability_date_label( $product, $pre_order_availability_date_label ) {
			$product->update_meta_data( '_ywpo_preorder_availability_date_label', $pre_order_availability_date_label );
			do_action( 'yith_ywpo_pre_order_availability_date_label_changed', $product, $pre_order_availability_date_label );
			$product->save();
		}

		/**
		 * Set the text to display when no release date is set for the pre-order product.
		 *
		 * @param string $pre_order_no_date_label The option value.
		 */
		public static function set_pre_order_no_date_label( $product, $pre_order_no_date_label ) {
			$product->update_meta_data( '_ywpo_preorder_no_date_label', $pre_order_no_date_label );
			do_action( 'yith_ywpo_pre_order_no_date_label_changed', $product, $pre_order_no_date_label );
			$product->save();
		}

		/**
		 * Set the value to check if the pre-order fee is overridden for this pre-order product.
		 *
		 * @param string $override_fee The option value.
		 */
		public static function set_override_fee( $product, $override_fee ) {
			$product->update_meta_data( '_ywpo_override_fee', $override_fee );
			do_action( 'yith_ywpo_override_fee_changed', $product, $override_fee );
			$product->save();
		}

		/**
		 * Set the fee amount value.
		 *
		 * @param string $fee The option value.
		 */
		public static function set_fee( $product, $fee ) {
			$product->update_meta_data( '_ywpo_fee', $fee );
			do_action( 'yith_ywpo_fee_changed', $product, $fee );
			$product->save();
		}

		/**
		 * Set the value to check if the charge type is overridden for this pre-order product.
		 *
		 * @param string $override_charge_type The option value.
		 */
		public static function set_override_charge_type( $product, $override_charge_type ) {
			$product->update_meta_data( '_ywpo_override_charge_type', $override_charge_type );
			do_action( 'yith_ywpo_override_charge_type_changed', $product, $override_charge_type );
			$product->save();
		}

		/**
		 * Set the charge type. Possible values are:
		 * - 'upfront':      The product will be charged upfront when placing the pre-order.
		 * - 'upon_release': The product will be charged on the release date through an integrated payment gateway.
		 * - 'pay_later':    The product will be pre-ordered through the Pay Later gateway and the payment method can be
		 *                   selected by the customer on the release date.
		 *
		 * @param string $charge_type The option value.
		 */
		public static function set_charge_type( $product, $charge_type ) {
			$product->update_meta_data( '_ywpo_charge_type', $charge_type );
			do_action( 'yith_ywpo_charge_type_changed', $product, $charge_type );
			$product->save();
		}

		/**
		 * Set the pre-order price.
		 *
		 * @param string $pre_order_price The option value.
		 */
		public static function set_pre_order_price( $product, $pre_order_price ) {
			$product->update_meta_data( '_ywpo_preorder_price', $pre_order_price );
			do_action( 'yith_ywpo_pre_order_price_changed', $product, $pre_order_price );
			$product->save();
		}

		/**
		 * Set the discount percentage amount.
		 *
		 * @param string $discount_percentage The option value.
		 */
		public static function set_discount_percentage( $product, $discount_percentage ) {
			$product->update_meta_data( '_ywpo_preorder_discount_percentage', $discount_percentage );
			do_action( 'yith_ywpo_discount_percentage_changed', $product, $discount_percentage );
			$product->save();
		}

		/**
		 * Set the fixed amount for discount.
		 *
		 * @param string $discount_fixed The option value.
		 */
		public static function set_discount_fixed( $product, $discount_fixed ) {
			$product->update_meta_data( '_ywpo_preorder_discount_fixed', $discount_fixed );
			do_action( 'yith_ywpo_discount_fixed_changed', $product, $discount_fixed );
			$product->save();
		}

		/**
		 * Set the increase percentage amount.
		 *
		 * @param string $increase_percentage The option value.
		 */
		public static function set_increase_percentage( $product, $increase_percentage ) {
			$product->update_meta_data( '_ywpo_preorder_increase_percentage', $increase_percentage );
			do_action( 'yith_ywpo_increase_percentage_changed', $product, $increase_percentage );
			$product->save();
		}

		/**
		 * Set the fixed amount for increase.
		 *
		 * @param string $increase_fixed The option value.
		 */
		public static function set_increase_fixed( $product, $increase_fixed ) {
			$product->update_meta_data( '_ywpo_preorder_increase_fixed', $increase_fixed );
			do_action( 'yith_ywpo_increase_fixed_changed', $product, $increase_fixed );
			$product->save();
		}
	}
}
