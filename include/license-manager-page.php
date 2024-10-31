<?php   
if (!defined('ABSPATH')) exit; // Exit if accessed directly

function plicenses_page() {
    global $wpdb, $plicense_settings;

if ($_POST['license']) {
    if ($_POST['edit'])
    {
        if ($_POST['send_email']) {
            //plicensing_send_email($email_from, $email_to, $email_subject, $email_content, $product_name, $license)
            plicensing_send_email($_POST['email_from'], $_POST['email_to'], $_POST['email_subject'], $_POST['email_content'], $_POST['product'], $_POST['license']);
            echo '<div class="updated"><p>Email sent successfully.</p></div>';
        }
        else {
            if ($_POST['allow'] == "") $allowed = $_POST['allowed'];
            else $allowed = $_POST['allow'];

            if ($_POST['expire'] == "0") $expired = $_POST['expire'];
            else $expired = (time() + (60 * 60 * 24 * $_POST['expire']));

            $sql = "UPDATE {$wpdb->prefix}plicenses SET license='".$_POST['license']."',email='".$_POST['email']."',product='".$_POST['product']."',domain='".$_POST['domain']."',status='".$_POST['status']."',allowed ='".$allowed."' WHERE id='".$_POST['edit']."'";
            $wpdb->query($sql);
            echo '<div class="updated"><p>License key modified.</p></div>';
        }
    }
    else {
        if ($_POST['expire'] == "0"): //means unlimited days
            $expired = $_POST['expire'];
        else:
            $expired = (time() + (60 * 60 * 24 * $_POST['expire']));
        endif;
        
        $sql = "INSERT INTO {$wpdb->prefix}plicenses SET created='".time()."',email='".$_POST['email']."',license='".$_POST['license']."',product='".$_POST['product']."',domain='".$_POST['domain']."',status='".$_POST['status']."',expire='".$expired."',allowed='".$_POST['allow']."'";
        $wpdb->query($sql);
        echo '<div class="updated"><p>New License is saved.</p></div>';
    }
}

if (isset($_POST['extendtime'])) {
    $extd = $wpdb->get_row("SELECT * FROM {$wpdb->prefix}plicenses WHERE id = '" . $_POST['extendid'] . "'");
    $counttime = (60 * 60 * 24 * $_POST['extendtime']);

    if ($_POST['extendtime'] != "0" && $extd->expire == "0") {
        $newexpire = (time() + $counttime);
    } elseif ($_POST['extendtime'] != "0" && $extd->expire != "0") {
        $newexpire = ($extd->expire + $counttime);
    } elseif ($_POST['extendtime'] == "0" && $extd->expire != "0") {
        $newexpire = "0";
    }

    $wpdb->query("UPDATE {$wpdb->prefix}plicenses SET expire = '" . $newexpire . "' WHERE id = '" . $_POST['extendid'] . "'");

    if ($_POST['extendtime'] == 0) {
        $extended = "Unlimited";
    } else {
        $extended = $_POST['extendtime'];
    }
    echo '<div class="updated"><p>License Key time has been extended for ' . $extended . ' Day(s).</p></div>';
}

if ($_POST['liccheck']) {
    foreach ((array) $_POST['liccheck'] as $lid) {
        $wpdb->query("DELETE FROM {$wpdb->prefix}plicenses WHERE id = '" . $lid . "'");
    }
    echo "<div class='updated'><p>Selected License(s) Deleted</p></div>";
}

if ($_GET['del']) {
    $wpdb->query("DELETE FROM {$wpdb->prefix}plicenses WHERE id = '" . $_GET['del'] . "'");
    echo '<div class="updated"><p>The license has been deleted.</p></div>';
}

if ($_GET['activate']) {
    $wpdb->query("UPDATE {$wpdb->prefix}plicenses SET status = '1' WHERE id = '" . $_GET['activate'] . "'");
    echo '<div class="updated"><p>The license has been activated.</p></div>';
}

if ($_GET['deactivate']) {
    $wpdb->query("UPDATE {$wpdb->prefix}plicenses SET status = '0' WHERE id = '" . $_GET['deactivate'] . "'");
    echo '<div class="updated"><p>The license has been deactivated.</p></div>';
}
?>

<div class="wrap nosubsub">
    <div class="icon32" style="width:21px;"><img src="<?php echo PLICENSE_PLUG_URL . '/assets/images/mainicon.png'; ?>" /></div>
    <h2><?php
        if ($_GET['act']) {
            if ($_GET['edit']) echo 'Edit License';
            else echo 'Add New License';
        }
        else echo 'Licenses';
    ?></h2>
    <?php if ($_GET['extend']) { $ext = $wpdb->get_row("SELECT * FROM {$wpdb->prefix}plicenses WHERE id = '" . $_GET['extend'] . "'"); ?>
        <form action="admin.php?page=plicenses" method="post">
            <input type="hidden" id="extendid" name="extendid" value="<?php echo $ext->id; ?>" />
            <h3>You can Extend License Key for:</h3>
            <p>License Key : <b><?php echo $ext->license; ?></b><br />
                Product : <b><?php echo $ext->product; ?></b><br />
                Expired On : <b>
                <?php if ($ext->expire == 0) echo "Never Expired";
                      else echo date('d/m/Y H:i:s', ($ext->expire));
                ?></b>
            </p>
            <div>
                <select name="extendtime" id="extendtime" style="width:240px;">
                    <option value="">Choose Expiration</option>
                    <?php
                    $dexpire = explode("|", $plicense_settings->key_expiredays);
                    foreach ((array) $dexpire as $pexpire):
                    ?>
                    <option value="<?php echo $pexpire; ?>" 
                        <?php if ($pexpire == $plicense_settings->default_key_expire) echo 'selected="selected"'; ?>>
                        <?php plicensing_disp_word($pexpire, 'Day'); ?>
                    </option>
                    <?php endforeach; ?>
                </select>
                <input type="submit" value="Save" class="button-primary submitextendtime">
            </div>
        </form>
    <?php return; } ?>

    <form method="post" action="admin.php?page=plicenses" id="posts-filter-form" enctype="multipart/form-data">
    <?php if (!$_GET['act']) { ?>
        <div class="fl">
            <div class="alignleft actions">
                <select name="action">
                    <option selected="selected" value="-1">Bulk Actions</option>
                    <option value="delete">Delete</option>
                </select>
                <input type="submit" value="Apply" class="button-secondary action" id="doaction" name="doaction">
            </div>
        </div>
    
        <p class="search-box">
            <a href="admin.php?page=plicenses&act=1" class="button-primary">Add New License</a>
            <label for="link-search-input" class="">&nbsp;&nbsp;&nbsp;Email:</label>
            <input type="text" value="<?php echo isset($_REQUEST['slicense'])? $_REQUEST['slicense'] : ''; ?>" name="slicense" id="link-search-input">
            <input type="submit" value="Search License" class="button" id="search-submit" name=""> 
        </p>
        <div class="clear"></div>
    <?php } ?>
        <?php 
        if ($_REQUEST['act']) {
            if ($_GET['edit']) $edit = $wpdb->get_row("SELECT * FROM {$wpdb->prefix}plicenses WHERE id = '" . $_GET['edit'] . "'"); ?>
            <input type="hidden" name="edit" value="<?php echo $edit->id; ?>" />
            
            <div class="fl">
            <table class="wp-list-table widefat tags ui-sortable addedit-license-table" style="margin-top: 10px; width:530px;">
                <tbody>
                    <tr><td valign="top" width="140"><label for="email">Email Address:</label></td>
                        <td><input type="text" name="email" size="40" value="<?php echo $edit->email; ?>" class="required email"/></td>
                    </tr>
                    <tr><td valign="top"><label for="license">License Key:</label></td>
                        <td>
                            <input type="text" name="license" size="40" id="license_key" class="required" value="<?php if($_GET['edit']) echo $edit->license; else echo plicensing_generate_guid($plicense_settings->key_pre, $plicense_settings->key_suf, 'SAMPLE'); ?>" />
                            <input id="regenerate_license" class="button-primary" type="button" name="regenerate_license" value="Regenerate"></input>
                            <img class="plic-loading-icon" src="<?php echo PLICENSE_PLUG_URL.'/assets/images/loading.gif'; ?>" width="15" height="15"/>
                        </td>
                    </tr>
                    <tr><td valign="top"><label for="product">Related Product:</label></td>
                        <td>
                        <?php if (plicensing_woocommerce_active() && $plicense_settings->int_woocommerce == "TRUE" ) { ?>
                            <select name="product" id="product" style="width:240px;" class="required">
                                <option value="">Choose Product</option>
                        <?php global $wpdb;
                                
                                $psql = "SELECT * FROM {$wpdb->prefix}posts WHERE post_type = 'product' AND post_title <> 'Auto Draft' ORDER BY post_title";
                                $pres = $wpdb->get_results($psql);
                                foreach ((array) $pres as $plic): ?>
                                    <option value="<?php echo $plic->post_title; ?>"<?php if ($plic->post_title == $edit->product) echo 'selected="selected"'; ?>><?php echo $plic->post_title; ?></option>
                                <?php endforeach; ?>
                            </select>
                        <?php } else { ?><input type="text" name="product" size="40" id="product" class="required" value="<?php echo $edit->product; ?>" />
                        <?php } ?>
                        </td>
                    </tr>
                    <tr><td valign="top"><label for="status">Status:</label></td>
                        <td>
                            <select id="status" name="status" style="width:240px;">
                                <option value="">Choose status</option>
                                <option value="0" <?php if ($edit->status == 0) { ?> selected="selected" <?php } ?>>Pending</option>
                                <option value="1" <?php if ($edit->status == 1) { ?> selected="selected" <?php } ?>>Active</option>
                            </select>
                        </td>
                    </tr>
                    <tr><td scope="row"><label for="allow">Allowed Domain:</label></td>
                        <td>
                            <select name="allow" id="allow" class="required">
                                <option value="">-- Allowed Domain --</option>
                        <?php   $allowed = explode("|", $plicense_settings->key_allowdomains);
                                foreach ((array) $allowed as $allow): ?>
                                    <option value="<?php echo $allow; ?>" <?php if ($allow == $edit->allowed) echo 'selected="selected"'; ?>>
                                    <?php plicensing_disp_word($allow, "Domain"); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select><input type="hidden" name="allowed" value="<?php echo $edit->allowed; ?>" />
                            <p class="description">Allowed Domain for this license key, "0" for Unlimited</p>
                        </td>
                    </tr>
                    <tr><td valign="top"><label for="domain">Domain Name:</label><br />
                            <small>Separate with " | "<br /><b><?php plicensing_disp_word($edit->allowed, "Domain"); ?></b></small>
                        </td>
                        <td><textarea name="domain" cols="35" rows="5" id="domain"><?php echo $edit->domain; ?></textarea></td>
                    </tr>
                    <tr><td valign="top"><label for="expired"><?php if ($edit->expire == '') echo "Expired days:"; else echo "Expired on:"; ?></label></td>
                        <td>
                            <?php if ($edit->expire == '') { ?>
                            <select name="expire" id="expire" style="width:240px;" class="required">
                                <option value="">Choose Expiration</option>
                                <?php
                                $dexpire = explode("|", $plicense_settings->key_expiredays);
                                foreach ((array) $dexpire as $pexpire):
                                ?>
                                    <option value="<?php echo $pexpire; ?>"<?php if ($pexpire == $plicense_settings->default_key_expire) echo 'selected="selected"'; ?>>
                                    <?php plicensing_disp_word($pexpire, "Day"); ?>
                                    </option>
                                 <?php endforeach; ?>
                            </select>
                            <?php } else { ?>
                                <input type="hidden" id="expire" name="expire" value="<?php echo $edit->expire; ?>">
                            <?php
                                if ($edit->expire == 0) echo "Never Expired";
                                else echo date("d/m/Y H:i", ($edit->expire));
                            } ?> 
                            <?php if ($_GET['edit']): ?> | <i><a href="admin.php?page=plicenses&extend=<?php echo $edit->id; ?>">Extend</a></i><?php endif; ?>
                        </td>
                    </tr>
                    <tr><td></td>
                        <td>
                            <input type="submit" value="Save" class="button-primary" id="save_license" name="save_license"> 
                            <a href="admin.php?page=plicenses">Cancel</a>
                        </td>
                    </tr>
                </tbody>
            </table>
            </div>
            <?php if($_GET['edit']) { ?>
            <div style="float: left; margin-left: 20px;">
            <table class="wp-list-table widefat tags ui-sortable mailsend-license-table" style="margin-top: 10px; width:540px;">
                <tbody>
                    <tr><td valign="top"><label for="email_from">From Email:</label></td>
                        <td><input type="text" id="email_from" name="email_from" size="40" class="required email" value="<?php if (isset($_REQUEST['email_from'])) echo $_REQUEST['email_from']; else echo get_option('admin_email'); ?>" /></td>
                    </tr>
                    <tr><td valign="top"><label for="email_to">To Email:</label></td>
                        <td><input type="text" id="email_to" name="email_to" size="40" class="required email" value="<?php if (isset($_REQUEST['email_to'])) echo $_REQUEST['email_to']; else echo $edit->email; ?>" /></td>
                    </tr>
                    <tr><td valign="top"><label for="email_subject">Subject:</label></td>
                        <td><input type="text" id="email_subject" name="email_subject" size="40" value="<?php if (isset($_REQUEST['email_subject'])) echo $_REQUEST['email_subject']; else echo'License Information'; ?>" class="required"/></td>
                    </tr>
                    <tr><td valign="top"><label for="email_content">From Email:</label></td>
                        <td>
                            <?php 
                            $email_content = $_REQUEST['email_content'] ? $_REQUEST['email_content'] : $plicense_settings->mail_template;
                            wp_editor(html_entity_decode(stripcslashes($email_content)), 'email_content', array('media_buttons'=>false, 'textarea_rows'=>12, 'editor_class'=>'required')); ?>
                            <div>You can change Default Email Template <a target="_blank" href="<?php echo admin_url('admin.php?page=plicensing_settings'); ?>">here</a></div>
                        </td>
                    </tr>
                    <tr><td></td><td><input type="submit" value="Send Email" class="button-primary fr" id="send_email" name="send_email"><div class="clear"></div></td></tr>
                </tbody>
            </table>
            </div>
            <?php } ?>
            <div class="clear"></div>
        </form>    
        <?php return; } ?>
    
    <table cellspacing="0" class="wp-list-table widefat fixed bookmarks" style="margin-top: 10px;">
        <thead>
            <tr>
                <th style="" class="manage-column column-cb check-column" id="cb" scope="col"><input type="checkbox"></th>
                <th style="" class="manage-column column-rel">Email</th>
                <th style="" class="manage-column column-rel">License Key</th>
                <th style="" class="manage-column" id="rel" scope="col">Related Product</th>
                <th style="" class="manage-column" scope="col">Status</th>
                <th style="" class="manage-column column-url <?php echo ($_REQUEST['orderby'] == "created") ? "sorted " : "sortable "; echo ($_REQUEST['order'] and $_REQUEST['orderby'] == "create") ? $_REQUEST['order'] : "desc"; ?> " id="url" scope="col">
                    <a href="admin.php?page=plicenses&orderby=created&order=<?php if ($_REQUEST['order'] == "asc" and $_REQUEST['orderby'] == "created") echo "desc"; else echo "asc"; ?>">
                        <span>Created</span><span class="sorting-indicator"></span>
                    </a>
                </th>
                <th style="" class="manage-column column-url <?php echo ($_REQUEST['orderby'] == "expire") ? "sorted " : "sortable "; echo ($_REQUEST['order'] and $_REQUEST['orderby'] == "expire") ? $_REQUEST['order'] : "desc"; ?> " id="url" scope="col">
                    <a href="admin.php?page=plicenses&orderby=expire&order=<?php if ($_REQUEST['order'] == "asc" and $_REQUEST['orderby'] == "expire") echo "desc"; else echo "asc"; ?>">
                        <span>Expired</span><span class="sorting-indicator"></span>
                    </a>
                </th>
                <th style="" class="manage-column" id="rel" scope="col">Allowed Domain</th>
                <?php if (plicensing_woocommerce_active() && ($plicense_settings->int_woocommerce == "TRUE")) { ?>
                    <th style="" class="manage-column" scope="col">Order ID</th>
                <?php } ?>
            </tr>
        </thead>
        <tbody id="the-list">
        <?php
            $sql = "SELECT * FROM {$wpdb->prefix}plicenses ORDER BY " . (($_REQUEST['orderby']) ? $_REQUEST['orderby'] : "created") . " " . (($_REQUEST['order']) ? $_REQUEST['order'] : "DESC LIMIT 100");
            if ($_REQUEST['slicense']) {
                $sql = "SELECT * FROM {$wpdb->prefix}plicenses WHERE email LIKE '%" . $_REQUEST['slicense'] . "%'";
            }
            $res = $wpdb->get_results($sql);
            if (!$res) echo '<tr><th colspan="8"><b>No Licenses Found!</b></th></tr>';
            else {
                foreach ((array) $res as $lic):
                    $expired = ($lic->expire < time() && $lic->expire != 0);
            ?>
                <tr valign="middle" class="alternate" id="link-<?php echo $i++; ?>">
                    <th class="check-column" scope="row"><input type="checkbox" value="<?php echo $lic->id; ?>" name="liccheck[]"></th>
                    <td class="column-url"><?php echo $lic->email; ?><br>
                        <div class="row-actions">			
                            <span class="edit"><a href="admin.php?page=plicenses&act=1&edit=<?php echo $lic->id; ?>">Edit</a> | </span>
                            <span class="delete"><a onClick="if (confirm('You are about to delete this license.\n  \'Cancel\' to stop, \'OK\' to delete.')) { return true; } return false;" href="admin.php?page=plicenses&del=<?php echo $lic->id; ?>" class="submitdelete">Delete</a></span>
                        </div>
                    </td>
                    <td class="column-rel"><?php echo $lic->license; ?></td>
                    <td class="column-rel">
                        <?php
                        if (plicensing_woocommerce_active() && $plicense_settings->int_woocommerce == "TRUE" ) {
                            $psql = $wpdb->get_row("SELECT * FROM {$wpdb->prefix}posts WHERE post_status = 'publish' AND post_title = '{$lic->product}'");
                            ?>
                            <a href="post.php?post=<?php echo $psql->ID; ?>&action=edit"><?php echo $lic->product; ?></a>
                        <?php } else { ?>
                            <a href="admin.php?page=plicenses&act=1&edit=<?php echo $lic->id; ?>"><?php echo $lic->product; ?></a>
                        <?php } ?>
                    </td>
                    <td class="column-url">
                        <?php if ($expired) { ?>
                            <span style="padding:3px; background-color:#006600; color:#FFFFFF;">Expired</span>
                        <?php } else if ($lic->status == 0) { ?>
                            <span style="background-color:#FF0000; padding:3px; color:#FFFFFF;">Pending</span><br />
                            <div class="row-actions">
                                <span class="activate"><a href="admin.php?page=plicenses&activate=<?php echo $lic->id; ?>" class="submitactivate">Activate</a></span>
                            </div>
                        <?php } else if ($lic->status == 1) { ?>
                            <span style="background-color:#00FF33; padding:3px;">Active</span>
                            <div class="row-actions">
                                <span class="deactivate"><a href="admin.php?page=plicenses&deactivate=<?php echo $lic->id; ?>" class="submitdeactivate">Deactivate</a></span>
                            </div>
                        <?php } ?>
                    </td>
                    <td class="column-url"><?php if ($lic->created) echo date("d/m/Y H:i", ($lic->created)); ?></td>
                    <td class="column-url"><?php if ($lic->expire == 0) echo "Never Expired"; else echo date("d/m/Y H:i", ($lic->expire)); ?>
                        <div class="row-actions">
                            <span class="extend"><a href="admin.php?page=plicenses&extend=<?php echo $lic->id; ?>">Extend</a></span>
                        </div>
                    </td>
                    <td class="column-url"><?php plicensing_disp_word($lic->allowed, "Domain"); ?></td>
                    <?php if (plicensing_woocommerce_active() && $plicense_settings->int_woocommerce == "TRUE" ) { ?>
                    <td class="column-rel"><a href="post.php?post=<?php $ids = str_replace("woo-#", "", $lic->order_id); echo $ids; ?>&action=edit" title="View Order"><?php echo $lic->order_id; ?></a></td>
                    <?php } ?>
                </tr>
            <?php endforeach; } ?>
        </tbody>
    </table>
</form>
</div>
<?php
}