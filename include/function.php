<?php
global $plicense_settings, $wpdb, $plicense_attribute_label, $plicense_attribute_name;
$plicense_settings = $wpdb->get_row("SELECT * FROM {$wpdb->prefix}plicenses_setting WHERE id = '1'");
$plicense_attribute_label = 'Domain Limit';
$plicense_attribute_name  = 'licman-domain-limit';

add_action("init", "plicense_init");
function plicense_init() {
    if (is_admin()) {
        wp_enqueue_script('plic-admin-js', PLICENSE_PLUG_URL . '/assets/plicensing-admin.js', array('jquery', 'thickbox'), '1.0', true);
        wp_enqueue_style('plic-admin-css', PLICENSE_PLUG_URL . '/assets/plicensing-admin.css');
        
        $plicense_data = array('admin_ajax_url'=>admin_url('admin-ajax.php'));
        wp_localize_script( 'plic-admin-js', 'plicense_data', $plicense_data );
    }
    global $plicense_settings, $wpdb, $woocommerce, $plicense_attribute_label, $plicense_attribute_name;
    
    if (is_admin() && $plicense_settings && plicensing_wc_integrate() && plicensing_woocommerce_active()) {
        
        //check product attributes "LICMAN Domain Limit" for woocommerce_attribute_taxonomies table
        $chk = $wpdb->get_var("SELECT count(*) FROM `{$wpdb->prefix}woocommerce_attribute_taxonomies` WHERE attribute_label = '{$plicense_attribute_label}'");
        if (empty($chk)) {
            $woocommerce->clear_product_transients();
            
            $attribute = array('attribute_label'=>$plicense_attribute_label, 'attribute_name'=>$plicense_attribute_name, 'attribute_type'=>'select', 'attribute_orderby'=>'menu_order');
            $wpdb->insert( $wpdb->prefix.'woocommerce_attribute_taxonomies', $attribute );
            
            //$wpdb->delete($wpdb->prefix.'term_taxonomy', array('taxonomy'=>'pa_'.$plicense_attribute_name));
            //$wpdb->delete($wpdb->prefix.'woocommerce_termmeta', array('meta_key'=>'order_pa_'.sanitize_title($plicense_attribute_name)));

            $termArr = array(
                    'plicense-one-domain'=> array('1 Domain' ,1),
                    'plicense-two-domain'=> array('2 Domains', 2),
                    'plicense-three-domain'=> array('3 Domains', 3),
                    'plicense-four-domain'=> array('4 Domains', 4),
                    'plicense-five-domain'=> array('5 Domains', 5),
                    'plicense-ten-domain'=> array('10 Domains', 10),
                    'plicense-fifteen-domain'=> array('15 Domains', 15),
                    'plicense-twenty-domain'=> array('20 Domains', 20),
                    'plicense-unlimited-domain'=> array('Unlimited', 0)
                );
            foreach ($termArr as $key => $value) {
                $termsdata = array('name' => $value[0], 'slug' => $key, 'term_group' => '0');
                
                //$wpdb->delete($wpdb->prefix.'terms', $termsdata);
                $wpdb->insert($wpdb->prefix.'terms', $termsdata);
                $lasttermid = $wpdb->insert_id;
                $wpdb->insert($wpdb->prefix.'term_taxonomy', array('taxonomy'=>'pa_'.$plicense_attribute_name, 'term_id'=>$lasttermid, 'parent'=>'0', 'count'=>'0'));
                $wpdb->insert($wpdb->prefix.'woocommerce_termmeta', array('meta_key'=>'order_pa_'.sanitize_title($plicense_attribute_name), 'meta_value'=> $value[1], 'woocommerce_term_id'=>$lasttermid));
            }
        }
    }
}

add_action('admin_menu', 'plicensing_admin_menu');
function plicensing_admin_menu() {

    //add_menu_page( $page_title, $menu_title, $capability, $menu_slug, $function, $icon_url, $position)
    //add_submenu_page( $parent_slug, $page_title, $menu_title, $capability, $menu_slug, $function)
    add_menu_page("PLicensing Admin", 'Licenses', 'manage_options', 'plicenses', 'plicenses_page', PLICENSE_PLUG_URL . '/assets/images/plugicon.png');

    add_submenu_page('plicenses', 'Licenses', 'Licenses', 'manage_options', 'plicenses', 'plicenses_page');
    add_submenu_page('plicenses', 'Settings', 'Settings', 'manage_options', 'plicensing_settings', 'plicensing_settings_page');
}

add_filter('plugin_action_links_' . PLICENSE_PLUG_NAME, 'plicensing_setting_links');
function plicensing_setting_links($links) {

    array_push($links, '<a href="' . admin_url('admin.php?page=plicensing_settings') . '">' . __('Settings') . '</a>');
    return $links;
}

function plicensing_install() {
    global $wpdb;
    
    if (is_super_admin()) {

        $sql = "CREATE TABLE IF NOT EXISTS `{$wpdb->prefix}plicenses` (
                    `id` int(11) NOT NULL AUTO_INCREMENT,
                    `email` tinytext CHARACTER SET utf8 NOT NULL,
                    `license` tinytext CHARACTER SET utf8 NOT NULL,
                    `product` tinytext CHARACTER SET utf8 NOT NULL,
                    `status` tinyint(4) NOT NULL DEFAULT '0',
                    `created` int(11) NOT NULL,
                    `expire` int(11) NOT NULL,
                    `domain` tinytext CHARACTER SET utf8 NOT NULL,
                    `order_id` tinytext CHARACTER SET utf8 NOT NULL,
                    `allowed` int(4) NOT NULL DEFAULT '1',
                    PRIMARY KEY (`id`)
                  ) ENGINE=MyISAM DEFAULT CHARSET=utf8 AUTO_INCREMENT=1 ;";
        $wpdb->query($sql);

        $sql = "CREATE TABLE IF NOT EXISTS `{$wpdb->prefix}plicenses_setting` (
                    `id` int(11) NOT NULL AUTO_INCREMENT,
                    `key_pre` text CHARACTER SET utf8 NOT NULL,
                    `key_suf` text CHARACTER SET utf8 NOT NULL,
                    `key_expiredays` text CHARACTER SET utf8 NOT NULL,
                    `key_allowdomains` text CHARACTER SET utf8 NOT NULL,
                    `default_key_status` text CHARACTER SET utf8 NOT NULL,
                    `default_key_expire` text CHARACTER SET utf8 NOT NULL,
                    `int_woocommerce` text CHARACTER SET utf8 NOT NULL,
                    `mail_template` text CHARACTER SET utf8 NOT NULL,
                    PRIMARY KEY (`id`)
                  ) ENGINE=MyISAM  DEFAULT CHARSET=utf8 AUTO_INCREMENT=1;";
        $wpdb->query($sql);

        $record_chk = $wpdb->get_var("SELECT COUNT(id) AS total FROM {$wpdb->prefix}plicenses_setting WHERE id = '1'");
        if (!$record_chk) {

$mail_str = <<<CODE
Licensee: [customer_email]

For the item: [product_name].
You will need the following details to activate the plugin.
Activation Code: [license_key]
CODE;
            $sql = "INSERT INTO `{$wpdb->prefix}plicenses_setting` SET id = '1', 
                key_pre = '', 
                key_suf = '', 
                key_expiredays = '1|7|30|90|180|365|0', 
                key_allowdomains = '0|1|2|3|4|5|10|15|20', 
                default_key_status = '1', 
                default_key_expire = '7', 
                int_woocommerce = 'TRUE', 
                mail_template = '" . mysql_real_escape_string($mail_str) . "'";
            $wpdb->query($sql);
        }
    }
}

function plicensing_woocommerce_active() {
    if (in_array('woocommerce/woocommerce.php', apply_filters('active_plugins', get_option('active_plugins')))) {
        return true;
    } else {
        return false;
    }
}

function plicensing_wc_integrate() {
    global $plicense_settings;

    if ($plicense_settings->int_woocommerce == "TRUE") {
        return true;
    } else {
        return false;
    }
}

function plicensing_generate_guid($prefix = '', $suffix = '', $data = '') {
    global $wpdb;
    do {
        $key_string = plicensing_guid($prefix, $suffix, $data);
        $result = $wpdb->get_var("SELECT count(*) FROM `{$wpdb->prefix}plicenses` WHERE license = '$key_string'");
    } while ($result != false);
    
    return $key_string;
}

function plicensing_guid($prefix = '', $suffix = '', $data = '') {
    $data = preg_replace("/[^a-zA-Z0-9]+/", "", $data);
    $data = str_replace(' ','',$data);
    
    $tokens = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789' . $data;
    $len = strlen($tokens);
    $segment_chars = 5;
    $num_segments = 4;
    $key_string = '';
 
    for ($i = 0; $i < $num_segments; $i++) {
        $segment = '';
        for ($j = 0; $j < $segment_chars; $j++) {
                $segment .= $tokens[rand(0, $len)];
        }
        $key_string .= $segment;
        if ($i < ($num_segments - 1)) {
                $key_string .= '-';
        }
    }

    $key_string = (($prefix != '') ? $prefix.'-' : '') .  
            $key_string .
            (($suffix != '') ? '-' . $suffix : '');
    
    return $key_string;
}

function plicensing_create_wc_license($email, $product, $domain, $orderid, $allowed) { //create license for woocommerce product.
    global $wpdb, $plicense_settings;
    $license = plicensing_generate_guid($plicense_settings->key_pre, $plicense_settings->key_suf, $domain);
    $status  = $plicense_settings->default_key_status;
    $expire  = time() + (60 * 60 * 24 * $plicense_settings->default_key_expire);
    
    $chk = $wpdb->get_var("SELECT COUNT(id) AS total FROM {$wpdb->prefix}plicenses WHERE order_id='".$orderid."' and product='".$product."'");
    if ($chk) {
        $sql = "UPDATE {$wpdb->prefix}plicenses SET license='".$license."',email='".$email."',product='".$product."',domain='".$domain."',status='".$status."',allowed ='".$allowed."' WHERE order_id='".$orderid."' and product='".$product."'";
        $wpdb->query($sql);
    }
    else {
        $sql = "INSERT INTO {$wpdb->prefix}plicenses SET email='".$email."',license='".$license."',product='".$product."',created='".time()."',domain='".$domain."',order_id='".$orderid."',expire='".$expire."',status='".$status."',allowed='".$allowed."'";
        $wpdb->query($sql);
    }
    plicensing_send_email(get_option('admin_email'), $email, 'License Information', $plicense_settings->mail_template, $product, $license);
}

function plicensing_send_email($email_from, $email_to, $email_subject, $email_content, $product_name, $license) {
    $email_content = str_replace('[product_name]', $product_name, $email_content);
    $email_content = str_replace('[license_key]', $license, $email_content);
    $email_content = str_replace('[customer_email]', $email_to, $email_content);
    //$email_content = nl2br(html_entity_decode(stripcslashes($email_content)));
    
    $headers = "MIME-Version: 1.0" . "\r\n";
    $headers .= "Content-type: text/html; charset=utf-8" . "\r\n";
    $headers .= 'From: '.get_bloginfo('name').' <'.$email_from.'>' . "\r\n";
    
    wp_mail($email_to, $email_subject, $email_content, $headers);
}

function plicensing_disp_word($val, $word) {
    if ($val == 0) echo "Unlimited";
    else {
        echo $val;
        if ($val == 1) echo "&nbsp;".$word;
        elseif ($val > 1) echo"&nbsp;".$word."s";
    }
}

add_action('wp_ajax_nopriv_plicense_getlicense', 'plicense_ajax_getlicense');
add_action('wp_ajax_plicense_getlicense', 'plicense_ajax_getlicense');
function plicense_ajax_getlicense() {
    global $plicense_settings;

    echo plicensing_generate_guid($plicense_settings->key_pre, $plicense_settings->key_suf, 'SAMPLE');
}