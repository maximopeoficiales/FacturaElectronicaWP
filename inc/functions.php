<?php
// wp_max_festatus asi se llama la bd de status
function getmfeByPostID(int $post_id): mixed
{
    global $wpdb;
    $sql = "SELECT post_id,fe_status FROM wp_max_festatus WHERE post_id = $post_id LIMIT 1";
    $result = $wpdb->get_results($wpdb->prepare($sql));
    return $result[0] != null ? $result[0] : null;
}
function getmfeStatusByPostID(int $post_id): int
{
    global $wpdb;
    $sql = "SELECT fe_status FROM wp_max_festatus WHERE post_id = $post_id LIMIT 1";
    $result = $wpdb->get_results($wpdb->prepare($sql));
    return $result[0]->fe_status != null ? intval($result[0]->fe_status) : 0;
}
function getmfeUpdateByPostID(int $post_id, int $fe_status): void
{
    global $wpdb;
    if (getmfeByPostID($post_id) != null) {
        $sql = "UPDATE wp_max_festatus SET fe_status= $fe_status WHERE post_id = $post_id";
    } else {
        $sql = "INSERT INTO wp_max_festatus(post_id,fe_status) VALUES ($post_id,$fe_status)";
    }
    $wpdb->query($wpdb->prepare($sql));
    $wpdb->flush();
}

// crea nuevo campo en tabla admin
add_filter('manage_edit-shop_order_columns', 'mfe_add_new_order_admin_list_column');
function mfe_add_new_order_admin_list_column($columns)
{
    $columns['billing_statusfe'] = 'Estado-Factura';
    return $columns;
}

add_action('manage_shop_order_posts_custom_column', 'mfe_add_new_order_admin_list_column_content');
function mfe_add_new_order_admin_list_column_content($column)
{

    global $post;
    $post_id = $post->ID;
    if ('billing_statusfe' === $column) {
        // pending 0
        // complete 1
        // canceled 2
        $html = "";
        $status = getmfeStatusByPostID($post_id);
        // if ($status == 2) $html = '<mark class="order-status" style="background-color: #c0444c; color: white;"><span>Cancelado</span></mark>';
        if ($status == 1) $html = '<mark class="order-status" style="background-color: #429b67; color: white;"><span>Completado</span></mark>';
        if ($status == 0) $html = '<mark class="order-status" style="background-color: #4d6d97; color: white;"><span>Sin Enviar</span></mark>';
        echo $html;
    }
}

// function mfe_PostFacturaElectronica()
// {
//     if ($_POST['mfe_key_activated_plugin'] == 1 && isset($_POST['mfe_post_id_plugin'])) {
//         $post_id = $_POST['mfe_post_id_plugin'];
//         getmfeUpdateByPostID($post_id, 1);
//     }
//     return null;
// }

// add_action('init', 'mfe_PostFacturaElectronica');
// inserta script de sweet alert
add_action('admin_footer', 'mfa_insert_script_sweetalert');
function mfa_insert_script_sweetalert()
{
?>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@10"></script>
<?php
} ?>

<?php
// add_action('add_meta     _boxes', 'mv_add_meta_boxes');
if (!function_exists('mv_add_meta_boxes')) {
    function mv_add_meta_boxes()
    {
        add_meta_box('mv_other_fields', __('Factura Electronica', 'woocommerce'), 'mv_add_other_fields_for_packaging', 'shop_order', 'normal', 'high');
    }
}

// Adding Meta field in the meta container admin shop_order pages
if (!function_exists('mv_add_other_fields_for_packaging')) {
    function mv_add_other_fields_for_packaging()
    {
        global $post;
        $status = getmfeStatusByPostID($post->ID);
        $descripcion_status = $status == 0 ? "Sin Enviar" : "Completado";
        $cod = 0;
        // parrafo de descripcion 
        echo '<p style="margin-top: 15px; margin-top: 5px">
        <strong>Estado de envio:</strong> ' . $descripcion_status . '
      </p>';
        $cod = 0;
        //   si es completado o no lo es
        if ($status == 1) {
            $html = '
      <div style="margin-top: 5px">
        <strong>¿Desea cancelar envio?</strong>
        <hr />
        <button
          type="submit"
          class="button save_order"
          name="save"
          value="Cancelar Envio"
          style="background-color: #c0444c; color: white; display: block; width: 100%"
        >
          Cancelar Envio
        </button>
      </div>';
            $cod = 0;
        } else {
            $html = '<div style="margin-top: 5px">
            <strong>¿Desea enviar la factura?</strong>
            <hr />
            <button
              type="submit"
              class="button save_order"
              name="save"
              value="Cancelar Envio"
              style="background-color: #429b67; color: white; display: block; width: 100%"
            >
              Enviar Factura
            </button>
          </div>';
            $cod = 1;
        }


        echo '<input type="hidden" name="mv_other_meta_field_nonce" value="' . wp_create_nonce() . '">
        <input type="hidden"  name="mfe_cod" placeholder="" value="' . $cod . '">
        ';
        echo $html;add_action('save_post', 'mv_save_wc_order_other_fields', 10, 1);
        if (!function_exists('mv_save_wc_order_other_fields')) {
        
            function mv_save_wc_order_other_fields($post_id)
            {
                // We need to verify this with the proper authorization (security stuff).
                // Check if our nonce is set.
                if (!isset($_POST['mv_other_meta_field_nonce'])) {
                    return $post_id;
                }
                $nonce = $_REQUEST['mv_other_meta_field_nonce'];
        
                //Verify that the nonce is valid.
                if (!wp_verify_nonce($nonce)) {
                    return $post_id;
                }
        
                // If this is an autosave, our form has not been submitted, so we don't want to do anything.
                if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
                    return $post_id;
                }
        
                // Check the user's permissions.
                if ('page' == $_POST['post_type']) {
                    if (!current_user_can('edit_page', $post_id)) {
                        return $post_id;
                    }
                } else {
        
                    if (!current_user_can('edit_post', $post_id)) {
                        return $post_id;
                    }
                }
                // --- Its safe for us to save the data ! --- //
        
                // Sanitize user input  and update the meta field in the database.
                if (isset($_POST["mfe_cod"])) {
                    // // si es igual a 0 status a cancelado
                    if ($_POST["mfe_cod"] == 0) {
                        // getmfeUpdateByPostID($post_id, 0);
                    } else if ($_POST["mfe_cod"] == 1) {
                        // getmfeUpdateByPostID($post_id, 1);
                    }
                }
                // wp_redirect(get_site_url() . $_POST["_wp_http_referer"]);
            }
        }
    }
}
// Save the data of the Meta field
// add_action('save_post', 'mv_save_wc_order_other_fields', 10, 1);
if (!function_exists('mv_save_wc_order_other_fields')) {

    function mv_save_wc_order_other_fields($post_id)
    {
        // We need to verify this with the proper authorization (security stuff).
        // Check if our nonce is set.
        if (!isset($_POST['mv_other_meta_field_nonce'])) {
            return $post_id;
        }
        $nonce = $_REQUEST['mv_other_meta_field_nonce'];

        //Verify that the nonce is valid.
        if (!wp_verify_nonce($nonce)) {
            return $post_id;
        }

        // If this is an autosave, our form has not been submitted, so we don't want to do anything.
        if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
            return $post_id;
        }

        // Check the user's permissions.
        if ('page' == $_POST['post_type']) {
            if (!current_user_can('edit_page', $post_id)) {
                return $post_id;
            }
        } else {

            if (!current_user_can('edit_post', $post_id)) {
                return $post_id;
            }
        }
        // --- Its safe for us to save the data ! --- //

        // Sanitize user input  and update the meta field in the database.
        if (isset($_POST["mfe_cod"])) {
            // // si es igual a 0 status a cancelado
            if ($_POST["mfe_cod"] == 0) {
                // getmfeUpdateByPostID($post_id, 0);
            } else if ($_POST["mfe_cod"] == 1) {
                // getmfeUpdateByPostID($post_id, 1);
            }
        }
        // wp_redirect(get_site_url() . $_POST["_wp_http_referer"]);
    }
}

?>