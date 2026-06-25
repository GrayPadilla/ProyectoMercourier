<?php
if (!defined('ABSPATH')) {
  exit; // Exit if accessed directly
}
function wpcumanage_user_table_header_user_id()
{
  echo "<th class='wpcumanage-userid-header'>" . __('User ID', 'wpcargo-umanagement') . "</th>";
}
function wpcumanage_user_table_data_user_id($user)
{
  echo '<td class="wpcumanage-user_id">' . wpcuser_prefix_id($user) . '' . wpcuser_unique_id($user) . '</td>';
}

function wpcumanage_user_table_header_username()
{
  echo "<th class='wpcumanage-usename-header'>" . __('Username', 'wpcargo-umanagement') . "</th>";
}
function wpcumanage_user_table_data_username($user)
{
  echo '<td class="wpcumanage-user_login">' . get_avatar($user->user_email, 32) . '' . $user->user_login . '</td>';
}
function wpcumanage_user_table_header_name()
{
  echo "<th class='wpcumanage-name-header'>" . __('Name', 'wpcargo-umanagement') . "</th>";
}
function wpcumanage_user_table_data_name($user)
{
  global $wpcargo;
  echo '<td class="wpcumanage-name">' . $wpcargo->user_fullname($user->ID) . '</td>';
}
function wpcumanage_user_table_header_email()
{
  echo "<th class='wpcumanage-email-header'>" . __('Email', 'wpcargo-umanagement') . "</th>";
}
function wpcumanage_user_table_data_email($user)
{
  echo '<td class="wpcumanage-email">' . $user->user_email . '</td>';
}
function wpcumanage_user_table_header_roles()
{
  echo "<th style='width:160px;' class='wpcumanage-roles-header'>" . __('Roles', 'wpcargo-umanagement') . "</th>";
}
function wpcumanage_user_table_data_roles($user)
{
  $roles = array_map(function ($role) {
    global $wp_roles;
    return translate_user_role($wp_roles->roles[$role]['name']);
  }, $user->roles);
  echo '<td class="wpcumanage-roles">' . implode(', ', $roles) . '</td>';
}
function wpcumanage_user_table_header_groups()
{
  echo "<th style='width:160px;' class='wpcumanage-groups-header'>" . __('Groups', 'wpcargo-umanagement') . "</th>";
}
function wpcumanage_user_table_data_groups($user)
{
  // 	$groups_ids = wpcumanage_get_array_groups_ids( $user->ID );
  // 	$groups = '';
  // 	$counter = 0;
  // 	if( !empty( $groups_ids ) ){
  // 		foreach( $groups_ids as $group_id => $group_name ){
  // 			$counter ++;
  // 			$comma = '';
  // 			if( count( $groups_ids ) > $counter ){
  // 				$comma .= ', ';
  // 			}
  // 			$groups .= $group_name.$comma;
  // 		}
  // 	}
  $_user_id     = $user->ID;
  $_groups      = wpcumanage_get_all_user_group_id_and_label();
  $_user_groups = wpcumanage_get_all_user_groups($_user_id);
  $_output      = '';
  foreach ($_groups as $_key => $_value) {
    if (in_array($_value->user_group_id, $_user_groups)) {
      $_output .= $_value->label . ', ';
    }
  }
  echo '<td class="wpcumanage-group">' . rtrim($_output, ', ') . '</td>';
}
function wpcumanage_user_table_header_blocked()
{
  echo "<th style='width:160px;' class='wpcumanage-blocked-header'>" . __('Bloqueado', 'wpcargo-umanagement') . "</th>";
}
function wpcumanage_user_table_data_blocked($user)
{
  $client_id = $user->ID;
  $esta_bloqueado = false;
  $bloqueo_total = ( get_user_meta($client_id, 'merc_bloqueo_total', true) === '1' );

  error_log("🔍 TABLA USUARIOS - Verificando bloqueo para User #{$client_id} ({$user->user_login})");

  if (in_array('wpcargo_client', $user->roles)) {
    error_log("   ✓ Es cliente WPCargo");

    // 0. Verificar bloqueo total explícito
    if ($bloqueo_total) {
      error_log("   ✓ Tiene BLOQUEO TOTAL explícito");
      $esta_bloqueado = true;
    }

    // 1. Verificar bloqueo manual explícito
    if (!$esta_bloqueado) {
      $bloqueado_manual = get_user_meta($client_id, 'merc_bloqueado_manual', true);
      if ($bloqueado_manual == '1') {
        error_log("   ✓ Tiene bloqueo manual explícito");
        $esta_bloqueado = true;
      }
    }

    // 2. Verificar bloqueo automático por tipo de envío
    if (!$esta_bloqueado) {
      $tipo_normal_bloqueado = get_user_meta($client_id, 'merc_tipo_normal_bloqueado', true);
      $tipo_express_bloqueado = get_user_meta($client_id, 'merc_tipo_express_bloqueado', true);
      $tipo_full_fitment_bloqueado = get_user_meta($client_id, 'merc_tipo_full_fitment_bloqueado', true);

      if ($tipo_normal_bloqueado == '1' || $tipo_express_bloqueado == '1' || $tipo_full_fitment_bloqueado == '1') {
        error_log("   ✓ Al menos un tipo de envío está bloqueado (bloqueo automático)");
        $esta_bloqueado = true;
      }
    }

    // 3. Verificar bloqueo automático por envíos pendientes
    if (!$esta_bloqueado && function_exists('merc_cliente_tiene_envios_pendientes_hoy')) {
      error_log("   ✓ Función de bloqueo por envíos pendientes existe, ejecutando...");
      $esta_bloqueado = merc_cliente_tiene_envios_pendientes_hoy($client_id);
      error_log("   📊 Bloqueo automático por envíos pendientes: " . ($esta_bloqueado ? "BLOQUEADO" : "NO BLOQUEADO"));
    }

    // 4. Verificar bloqueo automático por horario
    if (!$esta_bloqueado) {
      $bloqueado_por_hora_normal = false;
      $bloqueado_por_hora_express = false;
      $bloqueado_por_hora_full = false;

      if (function_exists('merc_check_tipo_normal_blocked')) {
        $bloqueado_por_hora_normal = merc_check_tipo_normal_blocked($client_id);
        if ($bloqueado_por_hora_normal) {
          error_log("   ✓ BLOQUEADO: Es tarde para envíos NORMAL");
        }
      }
      if (function_exists('merc_check_tipo_express_blocked')) {
        $bloqueado_por_hora_express = merc_check_tipo_express_blocked($client_id);
        if ($bloqueado_por_hora_express) {
          error_log("   ✓ BLOQUEADO: Es tarde para envíos EXPRESS");
        }
      }
      if (function_exists('merc_check_tipo_full_fitment_blocked')) {
        $bloqueado_por_hora_full = merc_check_tipo_full_fitment_blocked($client_id);
        if ($bloqueado_por_hora_full) {
          error_log("   ✓ BLOQUEADO: Es tarde para envíos FULL FITMENT");
        }
      }

      $esta_bloqueado = $bloqueado_por_hora_normal || $bloqueado_por_hora_express || $bloqueado_por_hora_full;
      if ($esta_bloqueado) {
        error_log("   📊 Bloqueo automático por horario: BLOQUEADO");
      }
    }

    error_log("   📊 Resultado final: " . ($esta_bloqueado ? "BLOQUEADO" : "NO BLOQUEADO"));
  } else {
    error_log("   ℹ️ No es cliente WPCargo (roles: " . implode(', ', $user->roles) . ")");
  }

  if ( in_array('wpcargo_client', $user->roles) ) {
    $data_total = $bloqueo_total ? '1' : '0';

    if ($esta_bloqueado) {
      echo '<td class="wpcumanage-blocked" data-merc-bloqueo-total="' . esc_attr($data_total) . '"><button class="btn btn-sm btn-danger wpcum-unblock-user px-2 m-0 text-white" data-id="' . $user->ID . '">' . __('Desbloquear', 'wpcargo-umanagement') . '</button></td>';
    } else {
      echo '<td class="wpcumanage-blocked" data-merc-bloqueo-total="' . esc_attr($data_total) . '"><button class="btn btn-sm btn-warning wpcum-block-user px-2 m-0 text-dark" data-id="' . $user->ID . '">' . __('Bloquear', 'wpcargo-umanagement') . '</button></td>';
    }
  } else {
    echo '<td class="wpcumanage-blocked"><span class="text-muted">-</span></td>';
  }
}
function wpcumanage_user_table_header_status()
{
  echo '<th class="wpcumanage-status-header">' . __('Status', 'wpcargo-umanagement') . '</th>';
}
function wpcumanage_user_table_data_status($user)
{
  $label = __('Active', 'wpcargo-umanagement');
  if (in_array('wpcargo_pending_client', $user->roles) || empty($user->roles)) {
    $label = '<button class="btn btn-sm btn-info wpcfe-approve-client px-2 m-0 text-white" data-id="' . $user->ID . '">' . __('Approve', 'wpcargo-umanagement') . '</button>';
  }
  echo '<td class="wpcumanage-status">' . $label . '</td>';
}
function wpcumanage_user_table_header_access()
{
  echo '<th class="wpcumanage-access-header">' . __('Access', 'wpcargo-umanagement') . '</th>';
}
function wpcumanage_user_table_data_access($user)
{
  $access          = wpcumanage_user_access($user->ID);
  $access_count    = is_array($access) ? count($access)  : 0;
  $str_access      = is_array($access) ? implode(',', $access)  : '';
  $class           = $access_count > 0 ? 'btn-info' : 'btn-light text-dark';
  echo '<td class="wpcumanage-access"><a data-id="' . $user->ID . '"  data-access="' . $str_access . '" class="btn ' . $class . ' btn-sm p-2 font-weight-bold wpcumange-update-access" data-toggle="modal" data-target="#wpcumanageAccessModal">(' . $access_count . ') ' . __('Access', 'wpcargo-umanagement') . '</a></td>';
}
function wpcumanage_user_table_header_defaults()
{
  echo '<th class="wpcumanage-default-header">' . __('Default Users', 'wpcargo-umanagement') . '</th>';
}
function wpcumanage_user_table_data_defaults($user)
{
  $default_users   = wpcumanage_default_users($user->ID);
  $access_count    = count($default_users);
  $class           = $access_count > 0 ? 'btn-info' : 'btn-light text-dark';
  echo '<td class="wpcumanage-default"><a data-id="' . $user->ID . '"  data-default="' . htmlspecialchars(wp_json_encode($default_users)) . '" class="btn ' . $class . ' btn-sm p-2 font-weight-bold wpcumange-update-assign_user" data-toggle="modal" data-target="#wpcumanageAssingmentModal">(' . $access_count . ') ' . __('Defaults', 'wpcargo-umanagement') . '</a></td>';
}
function wpcumanage_user_saved_callback()
{
  if (!isset($_GET['ustat']) && !isset($_GET['umsg'])) {
    return false;
  }
  $status = $_GET['ustat'] == 'success' ? 'success' : 'danger';
?>
  <div id="wpcumange-user-notification" class="alert alert-<?php echo $status; ?> p-2">
    <?php echo $_GET['umsg']; ?>
  </div>
  <script>
    setTimeout(() => {
      jQuery('#wpcumange-user-notification').remove();
    }, 6000);
    var uri = window.location.toString();
    if (uri.indexOf("?") > 0) {
      var clean_uri = uri.substring(0, uri.indexOf("?"));
      <?php if ($status != 'success'): ?>
        clean_uri = clean_uri + "?uaction=add";
      <?php endif; ?>
      window.history.replaceState({}, document.title, clean_uri);
    }
  </script>
<?php
}
// Hooks & Filters
function wpcumanage_row_action_callback($actions)
{
  $mylinks = array(
    '<a href="' . admin_url('admin.php?page=wptaskforce-helper') . '" aria-label="' . __('License', 'wpcargo-umanagement') . '">' . __('License', 'wpcargo-umanagement') . '</a>'
  );
  $actions = array_merge($actions, $mylinks);
  return $actions;
}
function wpcumanage_load_textdomain()
{
  load_plugin_textdomain('wpcargo-umanagement', false, '/wpcargo-user-management/languages');
}


// Update User Group
function wpcumanage_save_account_user_group_callback($user_data, $data, $user_id = "")
{
  $_user_id     = !empty($user_id) ? $user_id : $user_data->ID;
  $_user_groups = !empty($data['_groups']) ? $data['_groups'] : array();
  update_user_meta($_user_id, 'user_groups', maybe_serialize($_user_groups));
  do_action('um_after_save_user_data', $_user_id, $data);
}

// this hook will get the groups of a user and add it to user meta

add_action('wp_head', function () {
  global $wpdb;
  // get table name
  $table_name = $wpdb->prefix . WPCU_MANAGEMENT_DB_USER_GROUP;
  // get user_group_id and users
  $results = $wpdb->get_results("SELECT `user_group_id`, `users` FROM " . $table_name);
  // initialize array
  $user_groups = array();
  // loop results
  foreach ($results as $key => $value) {
    // unserialize user ids array
    $user_ids = is_serialized($value->users) ? maybe_unserialize($value->users) : array();
    // regroup user data ( from groups => users to users => groups )
    if (!empty($user_ids)) {
      foreach ($user_ids as $user_id) {
        $groups = $value->user_group_id;
        $user_groups[$user_id][] = $groups;
      }
    }
  }
  // update user meta
  foreach ($user_groups as $user_id => $groups) {
    if (!metadata_exists('user', $user_id, 'user_groups')) {
      update_user_meta($user_id, 'user_groups', maybe_serialize($groups));
    }
  }
});

function wpcumanage_user_group_narivation_callback()
{
  $page_url   = get_the_permalink(wpcumanage_users_page()) . '?umpage=group';
?>
  <div class="row border-bottom">
    <div class="col-md-8">
      <div id="wpcumanage-optpage" class="pb-2">
        <button id="wpcumanage-add-group" type="button" class="btn btn-info btn-sm waves-effect waves-light" data-toggle="modal" data-target="#addUserGroupModal"><i class="fa fa-plus text-white"></i> <?php echo wpcumanage_add_group_label(); ?></button>
      </div>
    </div>
    <div class="col-md-4">
      <form id="wpcumanage-search" class="float-md-none float-lg-right" action="<?php echo $page_url; ?>" method="get">
        <div class="form-inline">
          <label for="search-payment" class="sr-only"><?php esc_html_e('User Group', 'wpcargo-umanagement'); ?></label>
          <input type="hidden" name="umpage" value="group">
          <input type="text" class="form-control form-control-sm" name="umsearch" id="umsearch" placeholder="<?php echo wpcumanage_group_name_label(); ?>">
          <button type="submit" class="btn btn-primary btn-sm mx-md-0 ml-2"><?php esc_html_e('Search', 'wpcargo-umanagement'); ?></button>
        </div>
      </form>
    </div>
  </div>
<?php
}


function wpcumanage_user_group_add_modal_callback()
{
?>
  <div class="modal fade top" id="addUserGroupModal" tabindex="-1" role="dialog" aria-labelledby="addUserGroupModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg" role="document">
      <form id="addUserGroup-form" data-type="add">
        <div class="modal-content">
          <div class="modal-header">
            <h5 class="modal-title" id="addUserGroupModalLabel"><?php echo wpcumanage_add_group_label(); ?></h5>
            <button type="button" class="close" data-dismiss="modal" aria-label="Close">
              <span aria-hidden="true">&times;</span>
            </button>
          </div>
          <div class="modal-body">
            <div class="col-md-12">
              <div class="form-group">
                <label for="wpcumanage_ug_label"><?php echo wpcumanage_group_name_label(); ?></label>
                <input id="wpcumanage_ug_label" type="text" class="form-control" name="wpcumanage_ug_label">
              </div>
            </div>
            <div class="col-md-12">
              <div class="form-group">
                <label for="wpcumanage_ug_label"><?php echo wpcumanage_description_label(); ?></label>
                <textarea rows="4" name="wpcumanage_ug_desc" id="wpcumanage_ug_desc" class="form-control wpcumanage_ug_desc" value=""></textarea>
              </div>
            </div>
            <!-- <div class="col-md-12">
                <div class="form-group">
                  <label for="wpcumanage_ug_users"><?php // echo wpcumanage_users_label(); 
                                                    ?></label>
                  <select id="wpcumanage_ug_users" type="text" class="form-control browser-default" name="wpcumanage_ug_users" multiple>
                    <?php // foreach( wpcfe_get_clients() as $user_id => $user_name ):
                    ?>
                      <option value="<?php // echo $user_id;
                                      ?>" ><?php // echo $user_name; 
                                            ?></option>
                    <?php // endforeach;
                    ?>
                  </select>
                </div>
              </div> -->
          </div>
          <div class="modal-footer">
            <button type="button" class="btn btn-sm btn-secondary" data-dismiss="modal"><?php esc_html_e('Close', 'wpcargo-umanagement'); ?></button>
            <button type="submit" class="btn btn-sm btn-primary"><?php esc_html_e('Add', 'wpcargo-umanagement'); ?></button>
          </div>
        </div>
      </form>
    </div>
  </div>
<?php
}
function wpcumanage_user_group_update_modal_callback()
{
?>
  <div class="modal fade top" id="updateUserGroupModal" tabindex="-1" role="dialog" aria-labelledby="updateUserGroupModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg" role="document">
      <form id="updateUserGroup-form" data-type="update">
        <div class="modal-content">
          <div class="modal-header">
            <h5 class="modal-title" id="updateUserGroupModalLabel"><?php echo wpcumanage_update_group_label(); ?></h5>
            <button type="button" class="close" data-dismiss="modal" aria-label="Close">
              <span aria-hidden="true">&times;</span>
            </button>
          </div>
          <div class="modal-body">
          </div>
          <div class="modal-footer">
            <button type="button" class="btn btn-sm btn-secondary" data-dismiss="modal"><?php esc_html_e('Close', 'wpcargo-umanagement'); ?></button>
            <button type="submit" class="btn btn-sm btn-primary"><?php echo wpcumanage_update_label(); ?></button>
          </div>
        </div>
      </form>
    </div>
  </div>
<?php
}
function wpcumanage_edit_user_users_group_callback($user_data)
{
  $user_group_ids    = wpcumanage_get_all_user_group_ids();
  $account_group_ids  = wpcumanage_get_array_groups_ids($user_data->ID);
?>
  <h3><?php esc_html_e('WPCargo User Group', 'wpcargo-umanagement'); ?></h3>
  <table id="wpcumanage_edit_group_table" class="form-table">
    <th><label><?php esc_html_e('Groups', 'wpcargo-umanagement'); ?></label></th>
    <td>
      <select name="_groups[]" id="wpcumanage_ug_users" class="_groups" multiple>
        <?php foreach ($user_group_ids as $group_id): ?>
          <option value="<?php echo $group_id; ?>" <?php echo (array_key_exists($group_id, $account_group_ids)) ? 'selected' : ''; ?>><?php echo wpcumanage_get_user_group_label($group_id); ?></option>
        <?php endforeach; ?>
      </select>
    </td>
  </table>
<?php
}
function wpcumanage_edit_user_update_group_callback($user_id, $old_user_data, $userdata)
{
  wpcumanage_save_account_user_group_callback($userdata, $_POST, $user_id);
}

function wpcumanage_unblock_modal_callback()
{
    error_log('🔵 MODAL: Función wpcumanage_unblock_modal_callback ejecutada');
?>
  <style>
  #wpcumanageUnblockModal.show {
    display: block !important;
  }
  .modal-backdrop {
    position: fixed !important;
    top: 0 !important;
    left: 0 !important;
    width: 100% !important;
    height: 100% !important;
    background-color: rgba(0, 0, 0, 0.5) !important;
  }
  #wpcumanageUnblockModal {
    z-index: 1060 !important;
  }
  #wpcumanageBlockModal{
    z-index: 1060 !important;
  }
  </style>
  <script>
  jQuery(document).ready(function($) {
    console.log('🔵 MODAL HTML cargado');
    console.log('Bootstrap disponible?', typeof $.fn.modal);
    console.log('Modal existe?', $('#wpcumanageUnblockModal').length);
    
    // Variables para almacenar el estado del desbloqueo
    var unblockUserID = 0;
    var unblockButtonDOM = null;
    
    // Evento del botón desbloquear
    $(document).on('click', '.wpcum-unblock-user', function(e) {
      e.preventDefault();
      console.log('🔓 Click en botón desbloquear');
      unblockUserID = $(this).data('id');
      unblockButtonDOM = $(this);
      console.log('User ID:', unblockUserID);
      
      Swal.fire({
        title: '¿Desbloquear usuario?',
        text: '¿Estás seguro de que deseas desbloquear este usuario?',
        icon: 'question',
        showCancelButton: true,
        confirmButtonText: 'Sí, desbloquear',
        cancelButtonText: 'Cancelar',
        confirmButtonColor: '#27ae60',
        cancelButtonColor: '#95a5a6'
      }).then((result) => {
        if (result.isConfirmed) {
          $.ajax({
            type: "POST",
            data: {
              action: 'wpcumanage_unblock_user',
              user_id: unblockUserID
            },
            url: '<?php echo admin_url('admin-ajax.php'); ?>',
            beforeSend: function() {
              $('body').append('<div class="wpcargo-loading">Loading...</div>');
            },
            success: function(data) {
              $('body .wpcargo-loading').remove();
              
              if (data.status == 'error') {
                Swal.fire({icon:'error',title:'Error',text:data.message,confirmButtonColor:'#e74c3c'});
                return false;
              }
              
              // Reemplazar por botón Bloquear
              if (unblockButtonDOM) {
                unblockButtonDOM.closest('td').html('<button class="btn btn-sm btn-warning wpcum-block-user px-2 m-0 text-dark" data-id="' + unblockUserID + '">Bloquear</button>');
              }
              
              // Mostrar mensaje de éxito
              Swal.fire({icon:'success',title:'¡Éxito!',text:data.message || 'Usuario desbloqueado correctamente',confirmButtonColor:'#27ae60'});
              
              // Reset variables
              unblockUserID = 0;
              unblockButtonDOM = null;
            },
            error: function() {
              $('body .wpcargo-loading').remove();
              Swal.fire({icon:'error',title:'Error',text:'Error al desbloquear el usuario',confirmButtonColor:'#e74c3c'});
            }
          });
        }
      });
    });
  });
  </script>
<?php
}

function wpcumanage_user_form_middle_username($user_data, $is_update)
{
?>
  <div class="row mb-4">
    <div class="col-sm-12">
      <h2 class="h6 py-2 border-bottom font-weight-bold"><?php echo apply_filters('wpcfe_reg_user_info', __('Login Information', 'wpcargo-umanagement')); ?></h2>
    </div>
    <?php wpcumanage_generate_template(wpcum_personal_info_fields(), $user_data, $is_update); ?>
  </div>
<?php
}

//FM My profile Integration
function wpcum_update_user_email($user_id)
{
  $email = wp_get_current_user()->user_email ?: '';
?>
  <div class="form-group col-md-6">
    <label for="user_email" class="active">Email</label>
    <input id="user_email" class="form-control " type="text" name="user_email" value="<?php echo $email; ?>">
  </div>
<?php
}

function wpcfe_after_save_profile_email($user_id)
{
  if (!empty($_POST['user_email'])) {
    wp_update_user(array('ID' => $user_id, 'user_email' => $_POST['user_email']));
    $_POST['wpcfe-notification'] = array(
      'status'    => 'success',
      'icon'      => 'check',
      'message'   => __('User Email has been successfully updated.', 'wpcargo-frontend-manager')
    );
  }
}

//** Load Plugin text domain
add_action('plugins_loaded', 'wpcumanage_load_textdomain');
// Create plugin pages
add_action('wp_loaded', 'wpcumanage_create_default_pages');
// Add plugin action links
add_filter('plugin_action_links_' . WPCU_MANAGEMENT_BASENAME, 'wpcumanage_row_action_callback', 10);
function wpcumanage_plugins_loaded_callback()
{
  // FM Scripts
  add_filter('wpcfe_registered_styles', 'wpcumanage_registered_styles');
  add_filter('wpcfe_registered_scripts', 'wpcumanage_registered_scripts');
  // User Table Hooks
  add_action('wpcumanage_user_table_header', 'wpcumanage_user_table_header_user_id');
  add_action('wpcumanage_user_table_data', 'wpcumanage_user_table_data_user_id');
  add_action('wpcumanage_user_table_header', 'wpcumanage_user_table_header_username');
  add_action('wpcumanage_user_table_data', 'wpcumanage_user_table_data_username');
  add_action('wpcumanage_user_table_header', 'wpcumanage_user_table_header_name');
  add_action('wpcumanage_user_table_data', 'wpcumanage_user_table_data_name');
  add_action('wpcumanage_user_table_header', 'wpcumanage_user_table_header_email');
  add_action('wpcumanage_user_table_data', 'wpcumanage_user_table_data_email');
  add_action('wpcumanage_user_table_header', 'wpcumanage_user_table_header_roles');
  add_action('wpcumanage_user_table_data', 'wpcumanage_user_table_data_roles');
  // add_action('wpcumanage_user_table_header', 'wpcumanage_user_table_header_groups');
  // add_action('wpcumanage_user_table_data', 'wpcumanage_user_table_data_groups');
  add_action('wpcumanage_user_table_header', 'wpcumanage_user_table_header_blocked');
  add_action('wpcumanage_user_table_data', 'wpcumanage_user_table_data_blocked');
  add_action('wpcumanage_user_table_header', 'wpcumanage_user_table_header_status');
  add_action('wpcumanage_user_table_data', 'wpcumanage_user_table_data_status');
  add_action('wpcumanage_user_table_header', 'wpcumanage_user_table_header_access', 100);
  add_action('wpcumanage_user_table_data', 'wpcumanage_user_table_data_access', 100);
  add_action('wpcumanage_user_table_header', 'wpcumanage_user_table_header_defaults', 100);
  add_action('wpcumanage_user_table_data', 'wpcumanage_user_table_data_defaults', 100);
  add_action('wpcumanage_before_user_table', 'wpcumanage_user_saved_callback');
  add_action('wpcumanage_before_user_form', 'wpcumanage_user_saved_callback');
  add_action('wpcumanage_after_user_table_pagination', 'wpcumanage_unblock_modal_callback');
  // User Groups
  add_action('wpcumanage_user_group_before_form', 'wpcumanage_user_group_narivation_callback');
  add_action('wpcumanage_user_group_after_form', 'wpcumanage_user_group_add_modal_callback');
  add_action('wpcumanage_user_group_after_form', 'wpcumanage_user_group_update_modal_callback');
  add_action('wpcumanage_after_save_user', 'wpcumanage_save_account_user_group_callback', 10, 2);
  //#Added fields
  add_action('wpcumanage_user_form_middle', 'wpcumanage_user_form_middle_username', 10, 2);
  add_action('wpcfe_after_personal_details', 'wpcum_update_user_email', 10, 2);
  // do_action( 'wpcfe_after_save_profile', $user_id );
  add_action('wpcfe_after_save_profile', 'wpcfe_after_save_profile_email', 10, 2);

  // wp-admin edit user
  add_action('show_user_profile', 'wpcumanage_edit_user_users_group_callback');
  add_action('edit_user_profile', 'wpcumanage_edit_user_users_group_callback');
  add_action('profile_update', 'wpcumanage_edit_user_update_group_callback', 10, 3);

  //Um Roles
  add_action('wpcargo_after_assign_email', 'wpcum_select_user_roles', 99);
  // Modfied Fields
  add_action("wpcfe_billing_address_fields", "wpcfe_billing_address_fields_cb_additional", 10, 1);
}
add_action('plugins_loaded', 'wpcumanage_plugins_loaded_callback');
