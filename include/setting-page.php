<?php
if (!defined('ABSPATH')) exit; // Exit if accessed directly

function plicensing_settings_page() {
    global $wpdb;
    /*
    key_pre = '', key_suf = '', key_expiredays = '1|7|30|90|180|365|0', key_allowdomains = '0|1|5|10', 
    default_key_status = '1', default_key_expire = '7', int_woocommerce = 'TRUE', mail_template = mysql_real_escape_string($mail_str)
    */
    if ($_POST['submitted']) {
        $wpdb->query("UPDATE `{$wpdb->prefix}plicenses_setting` SET 
            key_pre = '" . $_POST['key_pre'] . "', 
            key_suf = '" . $_POST['key_suf'] . "', 
            key_expiredays = '" . $_POST['key_expiredays'] . "', 
            key_allowdomains = '" . $_POST['key_allowdomains'] . "', 
            default_key_status = '" . $_POST['default_key_status'] . "', 
            default_key_expire = '" . $_POST['default_key_expire'] . "', 
            int_woocommerce = '" . $_POST['int_woocommerce'] . "', 
            mail_template = '" . $_POST['mail_template'] . "' WHERE id = '1';");
        
        echo '<div class="updated"><p>Settings Saved</p></div>';
    }

    global $plicense_settings;
    $plicense_settings = $wpdb->get_row("SELECT * FROM {$wpdb->prefix}plicenses_setting WHERE id = '1'");
    ?>

<div class="wrap">
    <div class="icon32"><img src="<?php echo PLICENSE_PLUG_URL . '/assets/images/settingicon.png'; ?>" /></div>
    <h2>Licensing Settings</h2>

    <form method="post" action="admin.php?page=plicensing_settings">
        <input type="hidden" value="TRUE" name="submitted" />
        <table class="wp-list-table widefat tags ui-sortable setting_table">
            <thead>
                <tr>
                    <th width="200"><b>Settings</b></th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
                <tr valign="top">
                    <th scope="row"><label for="key_pre">License Key Prefix:</label></th>
                    <td><input type="text"id="key_pre" name="key_pre" value="<?php echo $plicense_settings->key_pre; ?>"><p class="description">Set your first part of your license key, will be like: Prefix-key</span></td>
                </tr>
                <tr valign="top">
                    <th scope="row"><label for="key_suf">License Key Suffix:</label></th>
                    <td><input type="text"id="key_suf" name="key_suf" value="<?php echo $plicense_settings->key_suf; ?>"><p class="description">Set your last part of your license key, will be like: key-Suffix</span></td>
                </tr>
                <tr valign="top">
                    <th scope="row"><label for="key_expiredays">License Key expiration:</label></th>
                    <td><input type="text" id="key_expiredays" name="key_expiredays" value="<?php echo $plicense_settings->key_expiredays; ?>"><p class="description">Set array license key expiration, separate with " | " (0 = Unlimited).</span></td>
                </tr>
                <tr valign="top">
                    <th scope="row"><label for="key_allowdomains">License Allowed Domain:</label></th>
                    <td><input readonly type="text" id="key_allowdomains" name="key_allowdomains" value="<?php echo $plicense_settings->key_allowdomains; ?>"><p class="description">Set array Allowed Domain, separate with " | " (0 = Unlimited).</span></td>
                </tr>

                <tr valign="top">
                    <th scope="row"><label for="default_key_status">Default License Status:</label></th>
                    <td>
                        <select name="default_key_status" id="default_key_status">
                            <option value="">Choose Status</option>
                            <option value="0" <?php if ($plicense_settings->default_key_status == '0') echo 'selected="selected"'; ?>>Pending</option>
                            <option value="1" <?php if ($plicense_settings->default_key_status == '1') echo 'selected="selected"'; ?>>Active</option>
                        </select>
                        <p class="description">Default License status when user create their license</span>
                    </td>
                </tr>
                <tr valign="top">
                    <th scope="row"><label for="default_key_expire">Default License expiration:</label></th>
                    <td>
                        <select name="default_key_expire" id="default_key_expire">
                            <option value="">Choose Expiration</option>
                            <?php
                            $dexpire = explode("|", $plicense_settings->key_expiredays);
                            foreach ((array) $dexpire as $pexpire):
                            ?>
                            <option value="<?php echo $pexpire; ?>"<?php if ($pexpire == $plicense_settings->default_key_expire) echo 'selected="selected"'; ?>>
                            <?php 
                                if ($pexpire == 0) echo "Unlimited";
                                else {
                                    echo $pexpire;
                                    if ($pexpire == 1) echo "&nbsp;Day";
                                    elseif ($pexpire > 1)echo"&nbsp;Days";
                                }
                            ?></option>
                        <?php endforeach; ?>
                        </select>
                        <p class="description">Default License expiration when user create their license</span>
                    </td>
                </tr>
                <tr valign="top">
                    <th scope="row"><label for="mail_template">License Email Template:</label></th>
                    <td>
                        <?php wp_editor(html_entity_decode(stripcslashes($plicense_settings->mail_template)), 'mail_template', array('media_buttons'=>false, 'textarea_rows'=>12)); ?>
                        <div><b>You can use these shortcode.</b><br/> [customer_email]<br/>[license_key]<br/>[product_name]</div>
                    </td>
                </tr>                
                <?php if (plicensing_woocommerce_active()){ ?>
                <tr valign="top">
                    <th scope="row"><label for="int_woocommerce">WooCommerce Integration:</label></th>
                    <td>
                        <select id="int_woocommerce" name="int_woocommerce">
                            <option value="TRUE" <?php if (plicensing_wc_integrate()) echo 'selected="selected"'; ?>>Enable</option>
                            <option value="FALSE" <?php if (!plicensing_wc_integrate()) echo 'selected="selected"'; ?>>Disable</option>
                        </select><p class="description">Integrate Licensing Plugin with WooCommerce</p>
                    </td>
                </tr>
                <?php } ?>
            </tbody>
        </table>
        <div class="tablenav bottom">
            <div class="alignright actions"><input type="submit" value="Save Changes" class="button-primary" name="save"></div>
            <br class="clear">
        </div>
    </form>
</div>
<?php
if (!plicensing_woocommerce_active())
    $wpdb->query("UPDATE `{$wpdb->prefix}plicenses_setting` SET int_woocommerce='FALSE'");
}