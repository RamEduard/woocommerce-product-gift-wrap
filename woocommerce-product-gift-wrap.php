<?php
/*
Plugin Name: WooCommerce Product Gift Wrap
Plugin URI: https://github.com/mikejolley/woocommerce-product-gift-wrap
Description: Add an option to your products to enable gift wrapping. Optionally charge a fee.
Version: 1.1.0
Author: Mike Jolley
Author URI: http://mikejolley.com
Requires at least: 3.5
Tested up to: 4.0
Text Domain: woocommerce-product-gift-wrap
Domain Path: /languages/

	Copyright: © 2014 Mike Jolley.
	License: GNU General Public License v3.0
	License URI: http://www.gnu.org/licenses/gpl-3.0.html
*/

/**
 * Localisation
 */
load_plugin_textdomain('woocommerce-product-gift-wrap', false, dirname(plugin_basename(__FILE__)) . '/languages/');

/**
 * define URL_Plugin
 */

define('GIFT_WRAP_URL', plugin_dir_url(__FILE__));

/**
 * WC_Product_Gift_wrap class.
 */
class WC_Product_Gift_Wrap
{

    /**
     * Hook us in :)
     *
     * @access public
     * @return void
     */
    public function __construct()
    {
        $default_message = '{checkbox} ' . sprintf(__('Gift wrap this item for %s?', 'woocommerce-product-gift-wrap'), '{price}');
        $this->gift_wrap_enabled = get_option('product_gift_wrap_enabled') == 'yes' ? true : false;
        $this->gift_wrap_cost = get_option('product_gift_wrap_cost', 0);
        $this->product_gift_wrap_message = get_option('product_gift_wrap_message');

        if (!$this->product_gift_wrap_message) {
            $this->product_gift_wrap_message = $default_message;
        }

        add_option('product_gift_wrap_enabled', 'no');
        add_option('product_gift_wrap_cost', '0');
        add_option('product_gift_wrap_message', $default_message);

        // Init settings
        $this->settings = array(
            array(
                'name' => __('Gift Wrapping Enabled by Default?', 'woocommerce-product-gift-wrap'),
                'desc' => __('Enable this to allow gift wrapping for products by default.', 'woocommerce-product-gift-wrap'),
                'id'   => 'product_gift_wrap_enabled',
                'type' => 'checkbox',
            ),
            array(
                'name'     => __('Default Gift Wrap Cost', 'woocommerce-product-gift-wrap'),
                'desc'     => __('The cost of gift wrap unless overridden per-product.', 'woocommerce-product-gift-wrap'),
                'id'       => 'product_gift_wrap_cost',
                'type'     => 'text',
                'desc_tip' => true
            ),
            array(
                'name'     => __('Gift Wrap Message', 'woocommerce-product-gift-wrap'),
                'id'       => 'product_gift_wrap_message',
                'desc'     => __('Note: <code>{checkbox}</code> will be replaced with a checkbox and <code>{price}</code> will be replaced with the gift wrap cost.', 'woocommerce-product-gift-wrap'),
                'type'     => 'text',
                'desc_tip' => __('The checkbox and label shown to the user on the frontend.', 'woocommerce-product-gift-wrap')
            ),
        );

        // Custom Option and Settings
        $default_note_message            = '{checkbox_note} ' . sprintf(__('Gift note this item for %s?', 'woocommerce-product-gift-wrap'), '{price_note}');
        $this->gift_note_enabled         = get_option('product_gift_note_enabled') == 'yes' ? true : false;
        $this->gift_note_cost            = get_option('product_gift_note_cost', 0);
        $this->product_gift_note_message = get_option('product_gift_note_message');

        if (!$this->product_gift_note_message) {
            $this->product_gift_note_message = $default_note_message;
        }

        add_option('product_gift_note_enabled', 'no');
        add_option('product_gift_note_cost', '0');
        add_option('product_gift_note_message', $default_note_message);

        $this->settings[] = array(
            'name' => __('Gift Note Enabled by Default?', 'woocommerce-product-gift-wrap'),
            'desc' => __('Enable this to allow gift note for products by default.', 'woocommerce-product-gift-wrap'),
            'id'   => 'product_gift_note_enabled',
            'type' => 'checkbox',
        );
        $this->settings[] = array(
            'name' => __('Default Gift Note Cost', 'woocommerce-product-gift-wrap'),
            'desc' => __('The cost of gift note unless overridden per-product.', 'woocommerce-product-gift-wrap'),
            'id'   => 'product_gift_note_cost',
            'type' => 'text',
        );
        $this->settings[] = array(
            'name'     => __('Gift Note Message', 'woocommerce-product-gift-wrap'),
            'id'       => 'product_gift_note_message',
            'desc'     => __('Note: <code>{checkbox_note}</code> will be replaced with a checkbox and <code>{price_note}</code> will be replaced with the gift note cost.', 'woocommerce-product-gift-wrap'),
            'type'     => 'text',
            'desc_tip' => __('The checkbox and label shown to the user on the frontend.', 'woocommerce-product-gift-wrap')
        );

		// Display on the front end
		add_action('woocommerce_after_add_to_cart_button', array($this, 'gift_option_html'), 10);

		// Filters for cart actions
		add_filter('woocommerce_add_cart_item_data', array($this, 'add_cart_item_data'), 10, 2);
		add_filter('woocommerce_get_cart_item_from_session', array($this, 'get_cart_item_from_session'), 10, 2);
		add_filter('woocommerce_get_item_data', array($this, 'get_item_data'), 10, 2);
		add_filter('woocommerce_add_cart_item', array($this, 'add_cart_item'), 10, 1);
		add_action('woocommerce_add_order_item_meta', array($this, 'add_order_item_meta'), 10, 2);

		// Write Panels
		add_action('woocommerce_product_options_pricing', array($this, 'write_panel'));
		add_action('woocommerce_process_product_meta', array($this, 'write_panel_save'));

		// Admin
		add_action('woocommerce_settings_general_options_end', array($this, 'admin_settings'));
		add_action('woocommerce_update_options_general', array($this, 'save_admin_settings'));
	}

    /**
     * Show the Gift Checkbox on the frontend
     *
     * @access public
     * @return void
     */
    public function gift_option_html()
    {
        global $post;

        $is_wrappable = get_post_meta($post->ID, '_is_gift_wrappable', true);

        if ($is_wrappable == '' && $this->gift_wrap_enabled)
            $is_wrappable = 'yes';

        if ($is_wrappable == 'yes') {

            $is_notabble = get_post_meta($post->ID, '_is_gift_notabble', true);

            if ($is_notabble == '' && $this->gift_note_enabled)
                $is_notabble = 'yes';

            $current_value = !empty($_REQUEST['gift_wrap']) ? 1 : 0;
            $cost = get_post_meta($post->ID, '_gift_wrap_cost', true);

            if ($cost == '') {
                $cost = $this->gift_wrap_cost;
            }

            $price_text = $cost > 0 ? woocommerce_price($cost) : __('free', 'woocommerce-product-gift-wrap');
            $checkbox_gift = '<input type="checkbox" name="gift_wrap" value="yes" ' . checked($current_value, 1, false) . ' />';

            if ($is_notabble == 'yes') {
                // enqueue scripts
                wp_register_script('woocommerce_gift_wrap_scripts', GIFT_WRAP_URL . '/js/scripts.js', null, '1.0');
                wp_enqueue_script('woocommerce_gift_wrap_scripts');
                // enqueue styles
                wp_register_style('woocommerce_gift_wrap_styles', GIFT_WRAP_URL . '/css/styles.css', null, '1.0');
                wp_enqueue_style('woocommerce_gift_wrap_styles');

                $current_note_value = !empty($_REQUEST['gift_note']) ? 1 : 0;
                $cost_note = get_post_meta($post->ID, '_gift_note_cost', true);

                if ($cost_note == '') {
                    $cost_note = $this->gift_note_cost;
                }

                $price_note_text = $cost_note > 0 ? woocommerce_price($cost_note) : __('free', 'woocommerce-product-gift-wrap');
                $checkbox_note = '<input type="checkbox" name="gift_note" value="yes" ' . checked($current_note_value, 1, false) . ' />';

                woocommerce_get_template('gift-wrap.php', array(
                    'product_gift_wrap_message' => $this->product_gift_wrap_message,
                    'checkbox_gift'             => $checkbox_gift,
                    'price_text'                => $price_text,
                    'product_gift_note_message' => $this->product_gift_note_message,
                    'checkbox_note'             => $checkbox_note,
                    'price_note_text'           => $price_note_text,
                ), 'woocommerce-product-gift-wrap', untrailingslashit(plugin_dir_path(__FILE__)) . '/templates/');
            } else {
                woocommerce_get_template('gift-wrap.php', array(
                    'product_gift_wrap_message' => $this->product_gift_wrap_message,
                    'checkbox_gift'             => $checkbox_gift,
                    'price_text'                => $price_text,
                    'product_gift_note_message' => '',
                    'checkbox_note'             => '',
                    'price_note_text'           => '',
                ), 'woocommerce-product-gift-wrap', untrailingslashit(plugin_dir_path(__FILE__)) . '/templates/');
            }
        }
    }

    /**
     * When added to cart, save any gift data
     *
     * @access public
     * @param mixed $cart_item_meta
     * @param mixed $product_id
     * @return void
     */
    public function add_cart_item_data($cart_item_meta, $product_id)
    {
        $is_wrappable = get_post_meta($product_id, '_is_gift_wrappable', true);

        if ($is_wrappable == '' && $this->gift_wrap_enabled)
            $is_wrappable = 'yes';

        if (!empty($_POST['gift_wrap']) && $is_wrappable == 'yes')
            $cart_item_meta['gift_wrap'] = true;

        $is_notabble = get_post_meta($product_id, '_is_gift_notabble', true);

        if ($is_notabble == '' && $this->gift_note_enabled)
            $is_notabble = 'yes';

        if (!empty($_POST['gift_note']) && $is_notabble == 'yes')
            $cart_item_meta['gift_note'] = true;

        return $cart_item_meta;
    }

    /**
     * Get the gift data from the session on page load
     *
     * @access public
     * @param mixed $cart_item
     * @param mixed $values
     * @return void
     */
    public function get_cart_item_from_session($cart_item, $values)
    {
        if (!empty($values['gift_wrap'])) {
            $cart_item['gift_wrap'] = true;

            $cost = get_post_meta($cart_item['data']->id, '_gift_wrap_cost', true);

            if ($cost == '')
                $cost = $this->gift_wrap_cost;

            $cart_item['data']->adjust_price($cost);
        }

        if (!empty($values['gift_note'])) {
            $cart_item['gift_note'] = true;

            $cost_note = get_post_meta($cart_item['data']->id, '_gift_note_cost', true);

            if ($cost_note == '')
                $cost_note = $this->gift_note_cost;

            $cart_item['data']->adjust_price($cost_note);
        }

        return $cart_item;
    }

    /**
     * Display gift data if present in the cart
     *
     * @access public
     * @param mixed $other_data
     * @param mixed $cart_item
     * @return void
     */
    public function get_item_data($item_data, $cart_item)
    {
        if (!empty($cart_item['gift_wrap']))
            $item_data[] = array(
                'name'    => __('Gift Wrapped', 'woocommerce-product-gift-wrap'),
                'value'   => __('Yes', 'woocommerce-product-gift-wrap'),
                'display' => __('Yes', 'woocommerce-product-gift-wrap')
            );

        if (!empty($cart_item['gift_note']))
            $item_data[] = array(
                'name'    => __('Gift Note', 'woocommerce-product-gift-wrap'),
                'value'   => __('Yes', 'woocommerce-product-gift-wrap'),
                'display' => __('Yes', 'woocommerce-product-gift-wrap')
            );

        return $item_data;
    }

    /**
     * Adjust price after adding to cart
     *
     * @access public
     * @param mixed $cart_item
     * @return void
     */
    public function add_cart_item($cart_item)
    {
        if (!empty($cart_item['gift_wrap'])) {

            $cost = get_post_meta($cart_item['data']->id, '_gift_wrap_cost', true);

            if ($cost == '')
                $cost = $this->gift_wrap_cost;

            $cart_item['data']->adjust_price($cost);
        }

        if (!empty($values['gift_note'])) {
            $cart_item['gift_note'] = true;

            $cost_note = get_post_meta($cart_item['data']->id, '_gift_note_cost', true);

            if ($cost_note == '')
                $cost_note = $this->gift_note_cost;

            $cart_item['data']->adjust_price($cost_note);
        }

        return $cart_item;
    }

    /**
     * After ordering, add the data to the order line items.
     *
     * @access public
     * @param mixed $item_id
     * @param mixed $values
     * @return void
     */
    public function add_order_item_meta($item_id, $cart_item)
    {
        if (!empty($cart_item['gift_wrap'])) {
            woocommerce_add_order_item_meta($item_id, __('Gift Wrapped', 'woocommerce-product-gift-wrap'), __('Yes', 'woocommerce-product-gift-wrap'));
        }
        if (!empty($cart_item['gift_note'])) {
            woocommerce_add_order_item_meta($item_id, __('Gift Note', 'woocommerce-product-gift-wrap'), __('Yes', 'woocommerce-product-gift-wrap'));
        }
    }

    /**
     * write_panel function.
     *
     * @access public
     * @return void
     */
    public function write_panel()
    {
        global $post;

        echo '</div><div class="options_group show_if_simple show_if_variable">';

        $is_wrappable = get_post_meta($post->ID, '_is_gift_wrappable', true);

        if ($is_wrappable == '' && $this->gift_wrap_enabled)
            $is_wrappable = 'yes';

        woocommerce_wp_checkbox(array(
            'id'            => '_is_gift_wrappable',
            'wrapper_class' => '',
            'value'         => $is_wrappable,
            'label'         => __('Gift Wrappable', 'woocommerce-product-gift-wrap'),
            'description'   => __('Enable this option if the customer can choose gift wrapping.', 'woocommerce-product-gift-wrap'),
        ));

        woocommerce_wp_text_input(array(
            'id'            => '_gift_wrap_cost',
            'label'         => __('Gift Wrap Cost', 'woocommerce-product-gift-wrap'),
            'placeholder'   => $this->gift_wrap_cost,
            'desc_tip'      => true,
            'description'   => __('Override the default cost by inputting a cost here.', 'woocommerce-product-gift-wrap'),
        ));

        // Custom Note
        $is_notabble  = get_post_meta($post->ID, '_is_gift_notabble', true);

        if ($is_notabble == '' && $this->gift_note_enabled)
            $is_notabble = 'yes';

        woocommerce_wp_checkbox(array(
            'id'            => '_is_gift_notabble',
            'wrapper_class' => '',
            'value'         => $is_notabble,
            'label'         => __('Gift Notable', 'woocommerce-product-gift-wrap'),
            'description'   => __('Enable this option if the customer can choose gift note.', 'woocommerce-product-gift-wrap'),
        ));

        woocommerce_wp_text_input(array(
            'id'            => '_gift_note_cost',
            'label'         => __('Gift Note Cost', 'woocommerce-product-gift-wrap'),
            'placeholder'   => $this->gift_note_cost,
            'desc_tip'      => true,
            'description'   => __('Override the default cost by inputting a cost here.', 'woocommerce-product-gift-wrap'),
        ));

        wc_enqueue_js("
			jQuery('input#_is_gift_wrappable').change(function(){

				jQuery('._gift_wrap_cost_field, ._is_gift_notabble_field, ._gift_note_cost_field').hide();

				if ( jQuery('#_is_gift_wrappable').is(':checked') ) {
					jQuery('._gift_wrap_cost_field, ._is_gift_notabble_field').show();

					if ( jQuery('#_is_gift_notabble').is(':checked') ) {
                        jQuery('._gift_note_cost_field').show();
                    }
				}

			}).change();

			jQuery('input#_is_gift_notabble').change(function(){

		        jQuery('._gift_note_cost_field').hide();

		        if ( jQuery('#_is_gift_notabble').is(':checked') ) {
					jQuery('._gift_note_cost_field').show();
				}

			}).change();
		");
    }

    /**
     * write_panel_save function.
     *
     * @access public
     * @param mixed $post_id
     * @return void
     */
    public function write_panel_save($post_id)
    {
        $_is_gift_wrappable = !empty($_POST['_is_gift_wrappable']) ? 'yes' : 'no';
        $_gift_wrap_cost = !empty($_POST['_gift_wrap_cost']) ? woocommerce_clean($_POST['_gift_wrap_cost']) : '';

        $_is_gift_notabble = !empty($_POST['_is_gift_notabble']) ? 'yes' : 'no';
        $_gift_note_cost = !empty($_POST['_gift_note_cost']) ? woocommerce_clean($_POST['_gift_note_cost']) : '';

        update_post_meta($post_id, '_is_gift_wrappable', $_is_gift_wrappable);
        update_post_meta($post_id, '_gift_wrap_cost', $_gift_wrap_cost);

        update_post_meta($post_id, '_is_gift_notabble', $_is_gift_notabble);
        update_post_meta($post_id, '_gift_note_cost', $_gift_note_cost);
    }

    /**
     * admin_settings function.
     *
     * @access public
     * @return void
     */
    public function admin_settings()
    {
        woocommerce_admin_fields($this->settings);
    }

    /**
     * save_admin_settings function.
     *
     * @access public
     * @return void
     */
    public function save_admin_settings()
    {
        woocommerce_update_options($this->settings);
    }
}

new WC_Product_Gift_Wrap();
