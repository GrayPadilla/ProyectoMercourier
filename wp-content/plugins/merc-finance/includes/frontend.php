<?php
/**
 * MERC Finance - Frontend UI para Penalidades
 * 
 * Renderiza la interfaz de penalidades en el dashboard del cliente
 */

if (!defined('ABSPATH')) exit;

/**
 * Mostrar tabla de penalidades pendientes en el dashboard cliente
 */
function merc_render_client_penalties_section() {
    if ( !is_user_logged_in() ) return;
    
    $current_user = wp_get_current_user();
    $penalties = merc_get_user_unpaid_penalties( $current_user->ID );
    
    if ( empty($penalties) ) return;
    
    $nonce_upload = wp_create_nonce('merc_cliente_pagar');
    ?>
    <!-- Penalidades pendientes (cliente) -->
    <div class="card mb-4">
        <div class="card-header"><h5 class="mb-0">🚫 Penalidades pendientes</h5></div>
        <div class="card-body">
            <table class="merc-entregas-table">
                <thead><tr><th>ID</th><th>Fecha</th><th>Monto</th><th>Acción</th></tr></thead>
                <tbody>
                <?php foreach ( $penalties as $p ) :
                    $date = esc_html( get_post_meta($p->ID, 'date', true) );
                    $amt = number_format( floatval(get_post_meta($p->ID, 'amount', true)), 2 );
                    $pay_nonce = wp_create_nonce('merc_pay_penalty_'.$p->ID);
                ?>
                <tr>
                    <td>#<?php echo esc_html($p->ID); ?></td>
                    <td><?php echo $date; ?></td>
                    <td>S/. <?php echo $amt; ?></td>
                    <td>
                        <button class="btn btn-sm btn-primary merc-btn-open-pay-modal" data-penalty-id="<?php echo esc_attr($p->ID); ?>" data-user-id="<?php echo esc_attr( $current_user->ID ); ?>" data-nonce="<?php echo esc_attr($nonce_upload); ?>">Pagar penalidad</button>
                    </td>
                </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
            <div id="merc-penalty-msg" style="margin-top:10px;"></div>
        </div>
    </div>

    <script>
    (function($){
        var ajaxurl = '<?php echo admin_url("admin-ajax.php"); ?>';

        // Modal template (single)
        var $modal = $('\<div id="merc-pay-modal" style="display:none;position:fixed;z-index:9999;left:0;top:0;width:100%;height:100%;background:rgba(0,0,0,0.6);"\>' +
            '\<div style="background:#fff;padding:20px;max-width:520px;margin:80px auto;border-radius:6px;position:relative;"\>' +
                '<button type="button" id="merc-pay-modal-close" style="position:absolute;right:10px;top:10px;border:none;background:transparent;font-size:18px;">✖</button>' +
                '<h4>Pagar penalidad</h4>' +
                '<p>Sube el comprobante de pago (S/. 5.00)</p>' +
                '<form id="merc-pay-modal-form" enctype="multipart/form-data">' +
                    '<input type="file" name="voucher" accept="image/*" required style="display:block;margin-bottom:10px;" />' +
                    '<input type="hidden" name="penalty_id" value="" />' +
                    '<input type="hidden" name="user_id" value="" />' +
                    '<input type="hidden" name="nonce" value="" />' +
                    '<div style="text-align:right;"><button class="btn btn-secondary" type="button" id="merc-pay-modal-cancel">Cancelar</button> <button class="btn btn-primary" type="submit">Enviar comprobante</button></div>' +
                '</form>' +
                '<div id="merc-pay-modal-msg" style="margin-top:10px;"></div>' +
            '</div></div>');
        $('body').append($modal);

        $(document).on('click', '.merc-btn-open-pay-modal', function(e){
            e.preventDefault();
            var pid = $(this).data('penalty-id');
            var uid = $(this).data('user-id');
            var nonce = $(this).data('nonce');
            $('#merc-pay-modal-form input[name=penalty_id]').val(pid);
            $('#merc-pay-modal-form input[name=user_id]').val(uid);
            $('#merc-pay-modal-form input[name=nonce]').val(nonce);
            $('#merc-pay-modal-msg').html('');
            $('#merc-pay-modal').fadeIn(150);
        });

        $(document).on('click', '#merc-pay-modal-close, #merc-pay-modal-cancel', function(){
            $('#merc-pay-modal').fadeOut(120);
        });

        $(document).on('submit', '#merc-pay-modal-form', function(e){
            e.preventDefault();
            var $form = $(this);
            var fd = new FormData();
            var file = $form.find('input[type=file]')[0].files[0];
            if ( ! file ) { alert('Selecciona un comprobante'); return; }
            fd.append('voucher', file);
            fd.append('action', 'merc_cliente_pagar_penalty');
            fd.append('penalty_id', $form.find('input[name=penalty_id]').val());
            fd.append('user_id', $form.find('input[name=user_id]').val());
            fd.append('nonce', $form.find('input[name=nonce]').val());

            var $btn = $form.find('button[type=submit]').prop('disabled', true).text('Subiendo...');
            $.ajax({
                url: ajaxurl,
                method: 'POST',
                data: fd,
                processData: false,
                contentType: false,
                dataType: 'json'
            }).done(function(resp){
                if ( resp && resp.success ) {
                    $('#merc-pay-modal-msg').html('<div class="alert alert-success">'+resp.data.message+'</div>');
                    // actualizar UI: ocultar modal y marcar la fila
                    setTimeout(function(){ $('#merc-pay-modal').fadeOut(150); location.reload(); }, 900);
                } else {
                    $('#merc-pay-modal-msg').html('<div class="alert alert-danger">'+(resp && resp.data?resp.data:'Error')+'</div>');
                    $btn.prop('disabled', false).text('Enviar comprobante');
                }
            }).fail(function(){ $('#merc-pay-modal-msg').html('<div class="alert alert-danger">Error de red</div>'); $btn.prop('disabled', false).text('Enviar comprobante'); });
        });
    })(jQuery);
    </script>
    <?php
}

// Hook para que aparezca en dashboard cliente
add_action('merc_cliente_dashboard_after_envios', 'merc_render_client_penalties_section', 5);

/**
 * Styles para las penalidades
 */
add_action('wp_head', function() {
    if ( !is_user_logged_in() ) return;
    ?>
    <style>
        .merc-pay-modal {
            position: fixed;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            display: none;
            z-index: 100000;
            align-items: center;
            justify-content: center;
            background: rgba(0,0,0,0.45);
        }
        .merc-pay-modal.show { display: flex; }
        .merc-pay-modal-content {
            background: #fff;
            padding: 20px;
            border-radius: 8px;
            max-width: 520px;
            width: 90%;
            box-shadow: 0 10px 30px rgba(0,0,0,0.2);
            position: relative;
        }
        .merc-pay-modal-close {
            position: absolute;
            right: 10px;
            top: 10px;
            border: none;
            background: transparent;
            font-size: 18px;
            cursor: pointer;
        }
    </style>
    <?php
});
?>
