// Inicializar cuando el DOM esté listo
document.addEventListener('DOMContentLoaded', function() {
    merc_init_estado_entrega();
});

function merc_init_estado_entrega() {
    // Verificar que mercReturnsConfig existe
    if (typeof mercReturnsConfig === 'undefined' || !mercReturnsConfig.ajaxUrl) {
        console.error('[Merc Returns] ERROR: mercReturnsConfig no disponible', mercReturnsConfig);
        return;
    }
    
    console.log('[Merc Returns] Script cargado. AJAX URL:', mercReturnsConfig.ajaxUrl);
    
    document.querySelectorAll('.merc-estado-entrega-select').forEach(function (select) {
        // Aplicar estilo visual al cargar según el valor guardado
        merc_aplicar_estilo_select(select, select.value);

        select.addEventListener('change', function () {
            var postId = this.dataset.postId;
            var valor  = this.value;
            var fila   = this.closest('tr');
            var sel    = this;

            // 1. Feedback visual inmediato en fila y select
            merc_aplicar_estilo_fila(fila, valor);
            merc_aplicar_estilo_select(sel, valor);

            // 2. Eliminar indicador anterior si existe
            var prevIndicador = sel.parentNode.querySelector('.merc-guardado-ok');
            if (prevIndicador) prevIndicador.remove();

            // 3. Guardar en base de datos vía AJAX
            var data = new FormData();
            data.append('action',  'merc_save_estado_entrega');
            data.append('post_id', postId);
            data.append('valor',   valor);

            // Debug: Verificar que mercReturnsConfig está disponible
            if (typeof mercReturnsConfig === 'undefined' || !mercReturnsConfig.ajaxUrl) {
                console.error('[Merc Returns] ERROR: mercReturnsConfig.ajaxUrl no disponible', mercReturnsConfig);
                alert('⚠️ Error de configuración. Recarga la página.');
                return;
            }

            fetch(mercReturnsConfig.ajaxUrl, {
                method: 'POST',
                body: data
            })
            .then(function (r) { 
                if (!r.ok) {
                    console.error('[Merc Returns] Error HTTP:', r.status);
                }
                return r.json(); 
            })
            .then(function (res) {
                if (res.success) {
                    // Mostrar checkmark temporal
                    var ok = document.createElement('span');
                    ok.className = 'merc-guardado-ok';
                    ok.textContent = '✔';
                    sel.parentNode.appendChild(ok);
                    setTimeout(function () { ok.remove(); }, 2200);
                } else {
                    console.error('[Merc Returns] Error en respuesta:', res);
                    alert('⚠️ Error al guardar el estado. Intenta de nuevo.');
                }
            })
            .catch(function (error) {
                console.error('[Merc Returns] Error de conexión:', error);
                alert('⚠️ Error de conexión. Verifica tu red e intenta de nuevo.');
            });
        });
    });

    function merc_aplicar_estilo_fila(fila, valor) {
        fila.style.transition = 'background 0.3s';
        if (valor === 'Entregado') {
            fila.style.background = '#d4edda';
        } else if (valor === 'No Entregado') {
            fila.style.background = '#f8d7da';
        } else {
            fila.style.background = '';
        }
    }

    function merc_aplicar_estilo_select(sel, valor) {
        // Resetear estilos base
        sel.style.border           = '1px solid #ccc';
        sel.style.backgroundColor  = '#fff';
        sel.style.color            = '#333';
        sel.style.backgroundImage  = "url(\"data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='12' height='12' viewBox='0 0 12 12'%3E%3Cpath fill='%23333' d='M6 9L1 4h10z'/%3E%3C/svg%3E\")";
        sel.style.backgroundRepeat    = 'no-repeat';
        sel.style.backgroundPosition  = 'right 8px center';

        if (valor === 'Entregado') {
            sel.style.border          = '1px solid #28a745';
            sel.style.backgroundColor = '#28a745';
            sel.style.color           = '#fff';
            sel.style.backgroundImage = "url(\"data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='12' height='12' viewBox='0 0 12 12'%3E%3Cpath fill='%23fff' d='M6 9L1 4h10z'/%3E%3C/svg%3E\")";
        } else if (valor === 'No Entregado') {
            sel.style.border          = '1px solid #dc3545';
            sel.style.backgroundColor = '#dc3545';
            sel.style.color           = '#fff';
            sel.style.backgroundImage = "url(\"data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='12' height='12' viewBox='0 0 12 12'%3E%3Cpath fill='%23fff' d='M6 9L1 4h10z'/%3E%3C/svg%3E\")";
        }
    }
}

