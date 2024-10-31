<?php
if (!defined('ABSPATH')) exit; // Exit if accessed directly

if (plicensing_woocommerce_active() && plicensing_wc_integrate()) {
    add_action('woocommerce_after_order_notes', 'plic_domain_field'); //showed on checkout page (front)
    add_action('woocommerce_checkout_process', 'plic_domain_field_process'); //when user clicks "place order"
    add_action('woocommerce_checkout_update_order_meta', 'plic_domain_field_update_order_meta'); // when woocommerce makes order with order data like billing, shipping address, checkout method.
    add_action('woocommerce_order_details_after_order_table', 'plic_order_details'); //showed on order details page (front).
    add_filter('woocommerce_payment_complete_order_status', 'plic_change_order_status', 10, 2);
    add_action('woocommerce_payment_complete', 'plic_create_wc_license_action'); 
    
    function plic_domain_field($checkout) { //showed on checkout page
        
        global $woocommerce, $wpdb, $plicense_attribute_name;
        
        foreach ($woocommerce->cart->get_cart() as $cart_item) {
            if ($cart_item['variation'] && $cart_item['variation']['attribute_pa_'.$plicense_attribute_name]) {
                $product = $cart_item['data'];
                $product_variation_id = $cart_item['variation_id'];
                $product_title = $product->get_title();
                $domain_slug = $cart_item['variation']['attribute_pa_'.$plicense_attribute_name];
                
                $sql = "SELECT meta_value FROM {$wpdb->prefix}woocommerce_termmeta WHERE woocommerce_term_id=(SELECT term_id FROM {$wpdb->prefix}terms WHERE slug='{$domain_slug}')";
                $domain_count_value = $wpdb->get_var($sql);
                
                if ($domain_count_value == '0') { //means unlimited domain
                    echo '<div><h3>License for '.$product_title.'</h3> Unlimited Domain</div>';
                    echo "<input type='hidden' name='plicense_0_{$product_variation_id}' value='unlimited'>";
                }
                else {
                    echo '<div><h3>License for '.$product_title.'</h3>';
                    for ($i = 0; $i < $domain_count_value; $i++) {
                        woocommerce_form_field("plicense_{$i}_{$product_variation_id}", array(
                            'type'          => 'text',
                            'class'         => array('input-text form-row form-row-wide'),
                            'label'         => 'Domain Name '.(($domain_count_value == 1) ? '' : ($i + 1)),
                            'required'      => true
                            ), $checkout->get_value("plicense_{$i}_{$product_variation_id}"));
                    }
                    echo '</div>';
                }                
            }
        }
    }

    function plic_domain_field_process() { //when user clicks "place order"
        global $woocommerce;
        $error = false;
        // Check if set, if its not set add an error.
        foreach($_POST as $k => $v) {            
            if(strpos($k, 'plicense_') !== false) {
                $domain_name = $v;
                
                if ($domain_name == 'unlimited') continue;
                if (empty($domain_name)) $error = true;
                if (!preg_match("/([0-9a-z-]+\.)?[0-9a-z-]+\.[a-z]{2,7}/", $domain_name))
                    $error = true;
            }
        }
        if ($error)
            $woocommerce->add_error('<b>Please enter valid domain name.</b>');
    }
     
    //when woocommerce makes order with order data like billing, shipping address, checkout method.
    function plic_domain_field_update_order_meta($order_id) {
        $meta_arr = array();
        foreach($_POST as $k => $v) {
            if(strpos($k, 'plicense_') !== false) {
                $product_variation_id = str_replace("_", "", strrchr($k, '_')); //get 13905 for plicense_0_13905
                $domain_name = $v;
                
                if (empty($meta_arr[$product_variation_id]))
                    $meta_arr[$product_variation_id] = $domain_name;
                else
                    $meta_arr[$product_variation_id] = $meta_arr[$product_variation_id] . ', ' . $domain_name;
            }
        }
        
        $meta_value = '';
        foreach ($meta_arr as $key => $value) {
            $meta_value = $meta_value . $key . ':' . $value . '|';
        }
        if (!empty($meta_value))
            update_post_meta($order_id, 'LICMAN_Domain', substr($meta_value, 0, -1)); //eg:LICMAN_Domain = 13905:test.com|13906:pan.com1,pan.com2
    }
    
    function plic_order_details($order) { //showed on order details page.
        global $wpdb;
        $orid = "woo-" . $order->get_order_number();
        $lic_result = $wpdb->get_results("SELECT * FROM {$wpdb->prefix}plicenses WHERE order_id = '" . $orid . "'");
        
        if ($lic_result) {
            echo '<div class="license_info"><h2>' . __('Product License information') . '</h2>';
            
            foreach ($lic_result as $record) {
                $expired = ($record->expire < time() && $record->expire != 0);

                if ($record->expire == 0) $date = 'Unlimited Days for License';
                else $date = date("d/m/Y H:i", $record->expire);
                
                echo '<div class="product_name">Product Name: <b>' . $record->product . '</b></div>';
                echo '<div class="product_domain">Valid Domain: <b>' . (($record->allowed=='0') ? 'Unlimited' : $record->domain) . '</b></div>';
                echo '<div class="product_license">License Key: <b> ' . $record->license . '</b></div>';
                echo '<div class="product_license_status">License Status: <b>';
                if (!$expired) {
                    if ($record->status == '0') echo "Pending";
                    else if ($record->status == '1') echo "Active";
                } 
                else 
                    echo "Expired";
                
                echo '</b></div>';
                echo '<div class="product_expiredon">Expired On: <b> ' . $date . '</b></div>';
            }
            echo '</div>';
        }
    }
    
    function plic_change_order_status($order_status, $order_id) {
        $order = new WC_Order($order_id);
        
        if ('processing' == $order_status && ('on-hold' == $order->status || 'pending' == $order->status || 'failed' == $order->status)) {
            $licman_domain = get_post_meta($order_id, 'LICMAN_Domain', true);
            if (!empty($licman_domain)) return 'completed';
        }
        return $order_status;
    }
    
    function plic_create_wc_license_action($order_id) {
        global $wpdb, $plicense_attribute_name;
        $order = new WC_Order($order_id);
        
        $licman_domain = get_post_meta($order_id, 'LICMAN_Domain', true);
        if (!empty($licman_domain)) {
            //LICMAN_Domain = 13905:test.com|13906:pan.com1,pan.com2, 13905, 13906 is product variation id
            $licman_domain_arr = explode("|", $licman_domain);
            
            foreach($licman_domain_arr as $licman_domain_value)
            {
                $domain_arr = explode(":", $licman_domain_value);
                $email = $order->billing_email;
                $order_id = 'woo-' . $order->get_order_number();

                $product_variation_id = $domain_arr[0];
                $product_variation = get_product($product_variation_id);

                $sql = "SELECT meta_value FROM {$wpdb->prefix}woocommerce_termmeta WHERE woocommerce_term_id=(SELECT term_id FROM {$wpdb->prefix}terms WHERE slug = (SELECT meta_value FROM {$wpdb->prefix}postmeta WHERE post_id={$product_variation_id} AND meta_key='attribute_pa_{$plicense_attribute_name}'))";
                $allowed = $wpdb->get_var($sql);

                plicensing_create_wc_license($email, $product_variation->get_title(), $domain_arr[1], $order_id, $allowed);
            }
        }
    }
}