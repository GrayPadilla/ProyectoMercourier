document.addEventListener('DOMContentLoaded', function () {
    console.log('Merc Warehouse cargado');

    // Si mercAlmacenData no está disponible, intentamos sin nonce
    const nonce = (typeof window.mercAlmacenData !== 'undefined') ? window.mercAlmacenData.nonce : '';
    const isAdmin = (typeof window.mercAlmacenData !== 'undefined') ? window.mercAlmacenData.isAdmin : false;

    // Obtener URL AJAX
    const ajaxUrl = (typeof ajaxurl !== 'undefined') ? ajaxurl : '/wp-admin/admin-ajax.php';

    console.log('Configuración:', { nonce: !!nonce, isAdmin, ajaxUrl });

    // Función para cargar productos
    function cargarProductos() {
        console.log('Iniciando carga de productos...');

        // Obtener el ID real del cliente/remitente
        const shipperIdInput = document.getElementById('shipper_id');

        // Preparar datos para el AJAX
        const formData = new URLSearchParams({
            action: 'merc_almacen_get_productos',

            // Enviar ID numérico del cliente
            cliente_id: shipperIdInput ? shipperIdInput.value : ''
        });
        // Añadir nonce si está disponible
        if (nonce) {
            formData.append('nonce', nonce);
        }

        // Cargar datos del almacén
        fetch(ajaxUrl, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: formData.toString()
        })
            .then(r => {
                console.log('Respuesta HTTP:', r.status);
                return r.json();
            })
            .then(res => {
                console.log('Respuesta AJAX:', res);
                if (res.success && res.data && res.data.productos) {
                    console.log('Productos cargados:', res.data.productos.length);
                    renderProducts(res.data.productos);
                    updateStats(res.data.productos);
                } else {
                    console.warn('No hay productos o error en respuesta:', res);
                    const tabla = document.getElementById('almacen-tabla');
                    if (tabla) {
                        tabla.innerHTML = '<p style="padding:20px;color:#e74c3c;">Error al cargar productos</p>';
                    }
                }
            })
            .catch(error => {
                console.error('Error en AJAX:', error);
                const tabla = document.getElementById('almacen-tabla');
                if (tabla) {
                    tabla.innerHTML = '<p style="padding:20px;color:#e74c3c;">Error de conexión: ' + error.message + '</p>';
                }
            });
    }

    function renderProducts(productos) {
        const container = document.getElementById('almacen-tabla');
        if (!container) {
            console.error('Contenedor almacen-tabla no encontrado');
            return;
        }

        console.log('Renderizando productos:', productos.length);

        if (!productos || productos.length === 0) {
            container.innerHTML = '<div style="padding:40px;text-align:center;color:#7f8c8d;"><p>📦 No hay productos en el almacén</p></div>';
            return;
        }

        // Agrupar productos por cliente (billing_company o cliente_nombre)
        const grupos = {};
        productos.forEach(p => {
            let cliente = '';
            if (p.billing_company && p.billing_company.trim() !== '') {
                cliente = p.billing_company.trim();
            } else if (p.cliente_nombre && p.cliente_nombre.trim() !== '') {
                cliente = p.cliente_nombre.trim();
            } else {
                cliente = 'Sin Cliente';
            }
            if (!grupos[cliente]) grupos[cliente] = [];
            grupos[cliente].push(p);
        });

        let html = '';
        const clientesOrdenados = Object.keys(grupos).sort();

        clientesOrdenados.forEach(cliente => {
            const prods = grupos[cliente];
            const totalCliente = prods.reduce((a, p) => a + (parseInt(p.cantidad) || 0), 0);
            const grupoId = 'grupo-' + cliente.replace(/[^a-z0-9]/gi, '_');

            html += `<div class="grupo-cliente" style="margin-bottom: 15px; border-radius: 8px; overflow: hidden; box-shadow: 0 2px 8px rgba(0,0,0,0.1);">`;
            html += `<button class="grupo-cliente-header" data-grupo="${grupoId}" style="display: flex; align-items: center; gap: 14px; padding: 16px 20px; background: linear-gradient(90deg, #2c3e50 0%, #34495e 100%); color: white; cursor: pointer; user-select: none; font-size: 16px; font-weight: 700; border: none; width: 100%; text-align: left; transition: background 0.3s;">`;
            html += `👤 ${cliente} <span style="font-size: 13px; font-weight: 400; opacity: 0.85; margin-left: auto;">${prods.length} producto(s) · ${totalCliente} unidades</span>`;
            html += `<span class="grupo-cliente-chevron" style="font-size: 14px; margin-left: auto; transition: transform 0.25s; transform: rotate(-90deg);">▼</span>`;
            html += `</button>`;
            html += `<div class="grupo-cliente-body" id="${grupoId}" style="background: white; display: none;">`;

            // Tabla de productos del grupo
            html += '<table style="width: 100%; border-collapse: collapse;">';
            html += '<thead><tr style="background: #f8f9fa; border-bottom: 2px solid #dee2e6;">';
            html += '<th style="padding: 12px 20px; text-align: left; font-weight: 600; font-size: 13px; color: #495057;">Producto</th>';
            html += '<th style="padding: 12px 20px; text-align: center; font-weight: 600; font-size: 13px; color: #495057;">Stock</th>';
            html += '<th style="padding: 12px 20px; text-align: left; font-weight: 600; font-size: 13px; color: #495057;">Tipo + Medida</th>';
            html += '<th style="padding: 12px 20px; text-align: left; font-weight: 600; font-size: 13px; color: #495057;">Creado</th>';
            html += '<th style="padding: 12px 20px; text-align: left; font-weight: 600; font-size: 13px; color: #495057;">Modificado</th>';
            html += '<th style="padding: 12px 20px; text-align: center; font-weight: 600; font-size: 13px; color: #495057;">Estado</th>';
            html += '<th style="padding: 12px 20px; text-align: center; font-weight: 600; font-size: 13px; color: #495057;">Motorizado</th>';
            if (isAdmin) {
                html += '<th style="padding: 12px 20px; text-align: center; font-weight: 600; font-size: 13px; color: #495057;">Acciones</th>';
            }
            html += '</tr></thead>';
            html += '<tbody>';

            prods.forEach((prod, idx) => {
                const estadoText = prod.estado === 'asignado' ? '🚚 Asignado' : (prod.estado === 'entregado' ? '✅ Entregado' : '📦 Sin Asignar');
                const bgcolor = idx % 2 === 0 ? 'white' : '#f9f9f9';

                // Tipo + valor de medida combinados
                let tipoMedidaLabel = '-';
                if (prod.tipo_medida && prod.tipo_medida !== '') {
                    const tipoCapital = prod.tipo_medida.charAt(0).toUpperCase() + prod.tipo_medida.slice(1);
                    tipoMedidaLabel = prod.valor_medida && prod.valor_medida !== ''
                        ? `${tipoCapital}: ${prod.valor_medida}`
                        : tipoCapital;
                } else if (prod.valor_medida && prod.valor_medida !== '') {
                    tipoMedidaLabel = prod.valor_medida;
                }

                const motorizado = prod.motorizado && prod.motorizado !== '-' ? prod.motorizado : '-';

                html += `<tr style="background: ${bgcolor}; border-bottom: 1px solid #ecf0f1;">`;
                html += `<td style="padding: 12px 20px;"><strong>${prod.nombre || 'Sin nombre'}</strong></td>`;
                html += `<td style="padding: 12px 20px; text-align: center;"><button class="btn-ver-cantidad" data-product-id="${prod.id}" data-product-name="${prod.nombre}" style="background: none; border: none; cursor: pointer; color: #1976d2; font-weight: 700; font-size: 14px; padding: 4px 10px; border-radius: 4px; transition: background-color 0.3s;" title="Ver detalles de envíos">📋 ${prod.cantidad || 0}</button></td>`;
                html += `<td style="padding: 12px 20px; font-size: 13px; color: #7f8c8d;">${tipoMedidaLabel}</td>`;
                html += `<td style="padding: 12px 20px; font-size: 13px; color: #7f8c8d;">${prod.fecha_creacion || '-'}</td>`;
                html += `<td style="padding: 12px 20px; font-size: 13px; color: #7f8c8d;">${prod.fecha_modificacion || '-'}</td>`;
                html += `<td style="padding: 12px 20px; text-align: center;"><span style="display: inline-block; padding: 6px 12px; background: #e9ecef; color: #495057; border-radius: 6px; font-size: 12px; font-weight: 600;">${estadoText}</span></td>`;
                html += `<td style="padding: 12px 20px; text-align: center; color: #2c3e50; font-weight: 600;">${motorizado}</td>`;
                if (isAdmin) {
                    html += `<td style="padding: 12px 20px; text-align: center; display: flex; gap: 8px; justify-content: center;">`;
                    html += `<button class="btn-edit" onclick="window.editarProducto(${prod.id})" style="background: #f39c12; color: white; border: none; padding: 6px 12px; border-radius: 4px; cursor: pointer; font-weight: 600; transition: background 0.3s;">✏️ Editar</button>`;
                    html += `<button class="btn-delete" onclick="window.eliminarProducto(${prod.id}, '${prod.nombre.replace(/'/g, "\\'")}', ${parseInt(prod.cantidad) || 0})" style="background: #e74c3c; color: white; border: none; padding: 6px 12px; border-radius: 4px; cursor: pointer; font-weight: 600; transition: background 0.3s;" title="Eliminar producto">🗑️ Eliminar</button>`;
                    html += `</td>`;
                }
                html += '</tr>';
            });

            html += '</tbody>';
            html += '</table>';
            html += '</div>';
            html += '</div>';
        });

        container.innerHTML = html;

        // Event listeners para botones de cantidad (ver envíos)
        document.querySelectorAll('.btn-ver-cantidad').forEach(btn => {
            btn.addEventListener('click', function (e) {
                e.preventDefault();
                const productId = this.dataset.productId;
                const productName = this.dataset.productName;
                window.openUnitsModal(productId, productName);
            });
        });

        // Agregar listeners para colapsar/expandir grupos
        document.querySelectorAll('.grupo-cliente-header').forEach(btn => {
            btn.addEventListener('click', function () {
                const grupoId = this.getAttribute('data-grupo');
                const body = document.getElementById(grupoId);
                const chevron = this.querySelector('.grupo-cliente-chevron');

                if (body.style.display === 'none') {
                    body.style.display = 'block';
                    chevron.style.transform = 'rotate(0deg)';
                } else {
                    body.style.display = 'none';
                    chevron.style.transform = 'rotate(-90deg)';
                }
            });
        });
    }

    function updateStats(productos) {
        console.log('Actualizando estadísticas...');

        // Contar usuarios únicos
        const usuarios = new Set();
        productos.forEach(p => {
            if (p.cliente_asignado) usuarios.add(p.cliente_asignado);
        });

        // Contar totales
        const total = productos.reduce((acc, p) => acc + (parseInt(p.cantidad) || 0), 0);
        const asignados = productos.reduce((acc, p) => acc + (p.estado === 'asignado' ? (parseInt(p.cantidad) || 0) : 0), 0);
        const entregados = productos.reduce((acc, p) => acc + (p.estado === 'entregado' ? (parseInt(p.cantidad) || 0) : 0), 0);

        const statUsuarios = document.getElementById('stat-usuarios');
        const statTotal = document.getElementById('stat-total');
        const statAsignados = document.getElementById('stat-asignados');
        const statEntregados = document.getElementById('stat-entregados');

        if (statUsuarios) statUsuarios.textContent = usuarios.size;
        if (statTotal) statTotal.textContent = total;
        if (statAsignados) statAsignados.textContent = asignados;
        if (statEntregados) statEntregados.textContent = entregados;

        console.log('Estadísticas actualizadas:', { usuarios: usuarios.size, total, asignados, entregados });
    }

    // Cargar productos inicialmente
    cargarProductos();

    // Recargar productos automáticamente cuando cambie el cliente del envío
    const clienteInput = document.getElementById('shipper_id');

    if (clienteInput) {
        clienteInput.addEventListener('change', function () {
            console.log('Cliente cambiado, recargando productos...');
            cargarProductos();
        });
    }

    // Handler para botón "Nuevo Producto"
    const btnNuevo = document.getElementById('btn-nuevo');
    if (btnNuevo) {
        btnNuevo.addEventListener('click', function () {
            console.log('Abriendo modal de nuevo producto');
            abrirModalNuevoProducto();
        });
    } else {
        console.warn('Botón btn-nuevo no encontrado');
    }

    // Función para abrir modal
    function abrirModalNuevoProducto() {
        let modal = document.getElementById('modal-nuevo-producto');

        // Si el modal ya existe (por ejemplo no fue removido), asegurarnos de resetear el formulario
        if (modal) {
            try {
                const existingForm = modal.querySelector('#form-nuevo-producto');
                if (existingForm) {
                    existingForm.reset();
                    // establecer valor por defecto para cantidad
                    const cantidadInput = existingForm.querySelector('input[name="cantidad"]');
                    if (cantidadInput) cantidadInput.value = 1;
                }
                // recargar clientes en el select
                cargarClientesParaNuevoProducto(modal);
            } catch (e) {
                console.warn('No se pudo resetear modal existente:', e);
            }
        }

        if (!modal) {
            // Crear modal si no existe (similar al de functions.php)
            modal = document.createElement('div');
            modal.id = 'modal-nuevo-producto';
            modal.style.cssText = `
                position: fixed;
                top: 0;
                left: 0;
                width: 100%;
                height: 100%;
                background: rgba(0, 0, 0, 0.6);
                display: flex;
                justify-content: center;
                align-items: center;
                z-index: 9999;
            `;

            modal.innerHTML = `
                <div class="modal-box" style="position: relative; background: white; border-radius: 12px; width: 90%; max-width: 500px; max-height: 90vh; display: flex; flex-direction: column; box-shadow: 0 10px 40px rgba(0,0,0,0.3);">
                    <div class="modal-header" style="display: flex; justify-content: space-between; align-items: center; padding: 15px 20px; border-bottom: 1px solid #ecf0f1; flex-shrink: 0;">
                        <h3 style="margin: 0; font-size: 20px; color: #2c3e50;">📦 Crear Nuevo Producto</h3>
                        <button class="modal-close-btn" style="background: none; border: none; font-size: 24px; color: #7f8c8d; cursor: pointer; padding: 0; line-height: 1; width: 30px; height: 30px; display: flex; align-items: center; justify-content: center;">&times;</button>
                    </div>
                    
                    <form id="form-nuevo-producto" style="overflow-y: auto; flex: 1;">
                        <div class="form-group" style="padding: 12px 20px;">
                            <label style="display: block; margin-bottom: 5px; font-weight: 600; color: #2c3e50; font-size: 14px;">Nombre del Producto *</label>
                            <input type="text" name="nombre" required placeholder="Nombre del producto" style="width: 100%; padding: 10px 12px; border: 2px solid #dfe6e9; border-radius: 6px; font-size: 14px; box-sizing: border-box;">
                        </div>
                        
                        <div class="form-group" style="padding: 12px 20px;">
                            <label style="display: block; margin-bottom: 5px; font-weight: 600; color: #2c3e50; font-size: 14px;">Código de Barras <small>(opcional)</small></label>
                            <input type="text" name="codigo_barras" placeholder="Código o SKU" style="width: 100%; padding: 10px 12px; border: 2px solid #dfe6e9; border-radius: 6px; font-size: 14px; box-sizing: border-box;">
                            <small style="color: #7f8c8d; font-size: 12px; display: block; margin-top: 3px;">📦 Código único para identificar el producto</small>
                        </div>
                        
                        <div class="form-group" style="padding: 12px 20px;">
                            <label style="display: block; margin-bottom: 5px; font-weight: 600; color: #2c3e50; font-size: 14px;">Cantidad *</label>
                            <input type="number" name="cantidad" min="1" required placeholder="0" value="1" style="width: 100%; padding: 10px 12px; border: 2px solid #dfe6e9; border-radius: 6px; font-size: 14px; box-sizing: border-box;">
                        </div>
                        
                        <div class="form-group" style="padding: 12px 20px;">
                            <label style="display: block; margin-bottom: 5px; font-weight: 600; color: #2c3e50; font-size: 14px;">Cliente Asignado <small>(opcional)</small></label>
                            <select id="cliente-select" name="cliente_asignado" style="width: 100%; padding: 10px 12px; border: 2px solid #dfe6e9; border-radius: 6px; font-size: 14px; box-sizing: border-box;">
                                <option value="">-- Seleccionar cliente --</option>
                            </select>
                            <small style="color: #7f8c8d; font-size: 12px; display: block; margin-top: 3px;">👤 Selecciona un cliente para asignar este producto</small>
                        </div>
                        
                        <div class="form-group" style="padding: 12px 20px;">
                            <label style="display: block; margin-bottom: 5px; font-weight: 600; color: #2c3e50; font-size: 14px;">Peso (kg) <small>(opcional)</small></label>
                            <input type="number" name="peso" min="0" step="0.01" placeholder="0.00" style="width: 100%; padding: 10px 12px; border: 2px solid #dfe6e9; border-radius: 6px; font-size: 14px; box-sizing: border-box;">
                            <small style="color: #7f8c8d; font-size: 12px; display: block; margin-top: 3px;">⚖️ Peso del producto en kilogramos</small>
                        </div>
                        
                        <div class="form-group" style="padding: 12px 20px;">
                            <label style="display: block; margin-bottom: 5px; font-weight: 600; color: #2c3e50; font-size: 14px;">Tipo de Medida <small>(opcional)</small></label>
                            <select name="tipo_medida" style="width: 100%; padding: 10px 12px; border: 2px solid #dfe6e9; border-radius: 6px; font-size: 14px; box-sizing: border-box;">
                                <option value="">-- Seleccionar --</option>
                                <option value="talla">Talla</option>
                                <option value="color">Color</option>
                                <option value="modelo">Modelo</option>
                                <option value="otro">Otro</option>
                            </select>
                            <small style="color: #7f8c8d; font-size: 12px; display: block; margin-top: 3px;">📏 Tipo de medida del producto</small>
                        </div>
                        
                        <div class="form-group" style="padding: 12px 20px;">
                            <label style="display: block; margin-bottom: 5px; font-weight: 600; color: #2c3e50; font-size: 14px;">Valor de Medida <small>(opcional)</small></label>
                            <input type="text" name="valor_medida" placeholder="Ej: S, M, L, XL o 100ml" style="width: 100%; padding: 10px 12px; border: 2px solid #dfe6e9; border-radius: 6px; font-size: 14px; box-sizing: border-box;">
                            <small style="color: #7f8c8d; font-size: 12px; display: block; margin-top: 3px;">📐 Valor específico de la medida (talla, color, etc.)</small>
                        </div>
                        
                        <div class="form-group" style="padding: 12px 20px;">
                            <label style="display: block; margin-bottom: 5px; font-weight: 600; color: #2c3e50; font-size: 14px;">Dimensiones (cm) <small>(opcional)</small></label>
                            <div style="display: flex; flex-direction: column; gap: 8px;">
                                <input type="number" name="largo" min="0" step="0.1" placeholder="Largo (cm)" style="width: 100%; padding: 10px 12px; border: 2px solid #dfe6e9; border-radius: 6px; font-size: 14px; box-sizing: border-box;">
                                <input type="number" name="ancho" min="0" step="0.1" placeholder="Ancho (cm)" style="width: 100%; padding: 10px 12px; border: 2px solid #dfe6e9; border-radius: 6px; font-size: 14px; box-sizing: border-box;">
                                <input type="number" name="alto" min="0" step="0.1" placeholder="Alto (cm)" style="width: 100%; padding: 10px 12px; border: 2px solid #dfe6e9; border-radius: 6px; font-size: 14px; box-sizing: border-box;">
                            </div>
                            <small style="color: #7f8c8d; font-size: 12px; display: block; margin-top: 3px;">📦 Ingresa las dimensiones en centímetros</small>
                        </div>
                    </form>
                    
                    <div class="modal-footer" style="display: flex; gap: 10px; justify-content: flex-end; padding: 15px 20px; border-top: 1px solid #ecf0f1; background: #f8f9fa; flex-shrink: 0;">
                        <button type="button" class="modal-close-btn" style="background: #6c757d; color: white; box-shadow: 0 2px 6px rgba(108, 117, 125, 0.3); padding: 10px 24px; font-size: 14px; border: none; border-radius: 6px; font-weight: 600; cursor: pointer;">Cancelar</button>
                        <button type="submit" form="form-nuevo-producto" style="background: #3498db; color: white; box-shadow: 0 2px 6px rgba(52, 152, 219, 0.3); padding: 10px 24px; font-size: 14px; border: none; border-radius: 6px; font-weight: 600; cursor: pointer;">Guardar Producto</button>
                    </div>
                </div>
            `;

            document.body.appendChild(modal);

            // Cargar clientes en el select del modal nuevo
            cargarClientesParaNuevoProducto(modal);

            // Cerrar modal: removemos el nodo para limpiar estado
            document.querySelectorAll('.modal-close-btn').forEach(btn => {
                btn.addEventListener('click', function () {
                    modal.style.display = 'none';
                    modal.remove();
                });
            });

            // Cerrar al hacer click en el backdrop: remover nodo
            modal.addEventListener('click', function (e) {
                if (e.target === modal) {
                    modal.style.display = 'none';
                    modal.remove();
                }
            });

            // Submit del formulario
            document.getElementById('form-nuevo-producto').addEventListener('submit', function (e) {
                e.preventDefault();
                console.log('Guardando nuevo producto...');
                const formData = new FormData(this);

                // Obtener cliente seleccionado directamente del select
                const clienteSelect = document.getElementById('cliente-select');
                const clienteId = clienteSelect ? clienteSelect.value : 0;

                const datos = {
                    action: 'merc_guardar_producto',
                    nombre: formData.get('nombre'),
                    codigo_barras: formData.get('codigo_barras'),
                    cantidad: parseInt(formData.get('cantidad')) || 1,
                    cliente_asignado: clienteId,
                    tipo_medida: formData.get('tipo_medida') || '',
                    valor_medida: formData.get('valor_medida') || '',
                    peso: parseFloat(formData.get('peso')) || 0,
                    largo: parseFloat(formData.get('largo')) || 0,
                    ancho: parseFloat(formData.get('ancho')) || 0,
                    alto: parseFloat(formData.get('alto')) || 0
                };

                if (nonce) {
                    datos.nonce = nonce;
                }

                console.log('Datos a enviar:', datos);

                fetch(ajaxUrl, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                    },
                    body: new URLSearchParams(datos)
                })
                    .then(r => r.json())
                    .then(res => {
                        console.log('Respuesta guardado:', res);
                        if (res.success) {
                            Swal.fire({
                                icon: 'success',
                                title: 'Éxito',
                                text: 'Producto creado exitosamente',
                                confirmButtonColor: '#3498db'
                            }).then(() => {
                                // Resetear formulario para garantizar estado limpio
                                try {
                                    const f = document.getElementById('form-nuevo-producto');
                                    if (f) f.reset();
                                } catch (e) { /* noop */ }
                                // Remover modal y recargar tabla
                                modal.style.display = 'none';
                                try { modal.remove(); } catch (err) { /* noop */ }
                                cargarProductos(); // Recargar tabla
                            });
                        } else {
                            Swal.fire({
                                icon: 'error',
                                title: 'Error',
                                text: res.data || 'Error desconocido',
                                confirmButtonColor: '#e74c3c'
                            });
                        }
                    })
                    .catch(err => {
                        console.error('Error:', err);
                        Swal.fire({
                            icon: 'error',
                            title: 'Error de conexión',
                            text: 'No se pudo conectar con el servidor',
                            confirmButtonColor: '#e74c3c'
                        });
                    });
            });
        }

        // Mostrar modal (si fue creado ahora o ya existía)
        if (modal) modal.style.display = 'flex';
    }

    // Carga la lista de clientes y la inyecta en el select dentro del modal (nuevo)
    function cargarClientesParaNuevoProducto(modalElement) {
        if (!modalElement) return;
        const select = modalElement.querySelector('#cliente-select');
        if (!select) return;

        // Vaciar opciones previas y dejar placeholder
        select.innerHTML = '<option value="">-- Seleccionar cliente --</option>';

        fetch(ajaxUrl, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: new URLSearchParams({ action: 'merc_obtener_clientes_lista', nonce: nonce })
        })
            .then(r => r.json())
            .then(res => {
                if (res.success && res.data && res.data.clientes) {
                    res.data.clientes.forEach(cliente => {
                        const opt = document.createElement('option');
                        opt.value = String(cliente.id);
                        opt.textContent = cliente.nombre;
                        select.appendChild(opt);
                    });
                } else {
                    console.warn('No se obtuvieron clientes para nuevo producto', res);
                }
            })
            .catch(err => {
                console.error('Error cargando clientes para nuevo producto:', err);
            });
    }

    // Función global para editar producto
    window.editarProducto = function (id) {
        console.log('Editando producto ID:', id);
        abrirModalEditarProducto(id);
    };

    // Función para abrir modal de edición
    function abrirModalEditarProducto(productId) {
        console.log('Abriendo modal de edición para producto:', productId);

        // Primero obtener los datos del producto
        fetch(ajaxUrl, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: new URLSearchParams({
                action: 'merc_obtener_producto',
                product_id: productId,
                nonce: nonce
            })
        })
            .then(r => r.json())
            .then(res => {
                console.log('Datos del producto:', res);
                if (res.success && res.data) {
                    mostrarModalEdicion(res.data);
                } else {
                    Swal.fire({
                        icon: 'error',
                        title: 'Error',
                        text: 'Error al cargar los datos del producto',
                        confirmButtonColor: '#e74c3c'
                    });
                }
            })
            .catch(err => {
                console.error('Error:', err);
                Swal.fire({
                    icon: 'error',
                    title: 'Error de conexión',
                    text: 'No se pudo conectar con el servidor',
                    confirmButtonColor: '#e74c3c'
                });
            });
    }

    function mostrarModalEdicion(producto) {
        let modal = document.getElementById('modal-editar-producto');

        // Remover modal anterior si existe
        if (modal) {
            modal.remove();
        }

        // Crear nuevo modal
        modal = document.createElement('div');
        modal.id = 'modal-editar-producto';
        modal.style.cssText = `
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.6);
            display: flex;
            justify-content: center;
            align-items: center;
            z-index: 9999;
        `;

        modal.innerHTML = `
            <div class="modal-box" style="position: relative; background: white; border-radius: 12px; width: 90%; max-width: 500px; max-height: 90vh; display: flex; flex-direction: column; box-shadow: 0 10px 40px rgba(0,0,0,0.3);">
                <div class="modal-header" style="display: flex; justify-content: space-between; align-items: center; padding: 15px 20px; border-bottom: 1px solid #ecf0f1; flex-shrink: 0;">
                    <h3 style="margin: 0; font-size: 20px; color: #2c3e50;">✏️ Editar Producto</h3>
                    <button class="modal-close-btn" style="background: none; border: none; font-size: 24px; color: #7f8c8d; cursor: pointer; padding: 0; line-height: 1; width: 30px; height: 30px; display: flex; align-items: center; justify-content: center;">&times;</button>
                </div>
                
                <form id="form-editar-producto" style="overflow-y: auto; flex: 1;">
                    <input type="hidden" id="producto-id" value="${producto.id}">
                    
                    <div class="form-group" style="padding: 12px 20px;">
                        <label style="display: block; margin-bottom: 5px; font-weight: 600; color: #2c3e50; font-size: 14px;">Nombre del Producto *</label>
                        <input type="text" id="edit-nombre" value="${producto.nombre || ''}" required style="width: 100%; padding: 10px 12px; border: 2px solid #dfe6e9; border-radius: 6px; font-size: 14px; box-sizing: border-box;">
                    </div>
                    
                    <div class="form-group" style="padding: 12px 20px;">
                        <label style="display: block; margin-bottom: 5px; font-weight: 600; color: #2c3e50; font-size: 14px;">Código de Barras <small>(opcional)</small></label>
                        <input type="text" id="edit-codigo" value="${producto.codigo_barras || ''}" style="width: 100%; padding: 10px 12px; border: 2px solid #dfe6e9; border-radius: 6px; font-size: 14px; box-sizing: border-box;">
                    </div>
                    
                    <div class="form-group" style="padding: 12px 20px;">
                        <label style="display: block; margin-bottom: 5px; font-weight: 600; color: #2c3e50; font-size: 14px;">Cantidad *</label>
                        <input type="number" id="edit-cantidad" min="0" value="${producto.cantidad || 0}" required style="width: 100%; padding: 10px 12px; border: 2px solid #dfe6e9; border-radius: 6px; font-size: 14px; box-sizing: border-box;">
                    </div>
                    
                    <div class="form-group" style="padding: 12px 20px;">
                        <label style="display: block; margin-bottom: 5px; font-weight: 600; color: #2c3e50; font-size: 14px;">Cliente Asignado <small>(opcional)</small></label>
                        <select id="edit-cliente" style="width: 100%; padding: 10px 12px; border: 2px solid #dfe6e9; border-radius: 6px; font-size: 14px; box-sizing: border-box;">
                            <option value="">-- Seleccionar cliente --</option>
                        </select>
                    </div>
                    
                    <div class="form-group" style="padding: 12px 20px;">
                        <label style="display: block; margin-bottom: 5px; font-weight: 600; color: #2c3e50; font-size: 14px;">Estado</label>
                        <select id="edit-estado" style="width: 100%; padding: 10px 12px; border: 2px solid #dfe6e9; border-radius: 6px; font-size: 14px; box-sizing: border-box;">
                            <option value="sin_asignar">📦 Sin Asignar</option>
                            <option value="asignado">🚚 Asignado</option>
                            <option value="entregado">✅ Entregado</option>
                        </select>
                    </div>
                    
                    <div class="form-group" style="padding: 12px 20px;">
                        <label style="display: block; margin-bottom: 5px; font-weight: 600; color: #2c3e50; font-size: 14px;">Peso (kg) <small>(opcional)</small></label>
                        <input type="number" id="edit-peso" min="0" step="0.01" value="${producto.peso || 0}" style="width: 100%; padding: 10px 12px; border: 2px solid #dfe6e9; border-radius: 6px; font-size: 14px; box-sizing: border-box;">
                    </div>
                    
                    <div class="form-group" style="padding: 12px 20px;">
                        <label style="display: block; margin-bottom: 5px; font-weight: 600; color: #2c3e50; font-size: 14px;">Tipo de Medida <small>(opcional)</small></label>
                        <select id="edit-tipo_medida" style="width: 100%; padding: 10px 12px; border: 2px solid #dfe6e9; border-radius: 6px; font-size: 14px; box-sizing: border-box;">
                            <option value="">-- Seleccionar --</option>
                            <option value="talla">Talla</option>
                            <option value="color">Color</option>
                            <option value="modelo">Modelo</option>
                            <option value="otro">Otro</option>
                        </select>
                    </div>
                    
                    <div class="form-group" style="padding: 12px 20px;">
                        <label style="display: block; margin-bottom: 5px; font-weight: 600; color: #2c3e50; font-size: 14px;">Valor de Medida <small>(opcional)</small></label>
                        <input type="text" id="edit-valor_medida" placeholder="Ej: S, M, L, XL o 100ml" value="${producto.valor_medida || ''}" style="width: 100%; padding: 10px 12px; border: 2px solid #dfe6e9; border-radius: 6px; font-size: 14px; box-sizing: border-box;">
                    </div>
                    
                    <div class="form-group" style="padding: 12px 20px;">
                        <label style="display: block; margin-bottom: 5px; font-weight: 600; color: #2c3e50; font-size: 14px;">Dimensiones (cm) <small>(opcional)</small></label>
                        <div style="display: flex; flex-direction: column; gap: 8px;">
                            <input type="number" id="edit-largo" min="0" step="0.1" placeholder="Largo (cm)" value="${producto.largo || 0}" style="width: 100%; padding: 10px 12px; border: 2px solid #dfe6e9; border-radius: 6px; font-size: 14px; box-sizing: border-box;">
                            <input type="number" id="edit-ancho" min="0" step="0.1" placeholder="Ancho (cm)" value="${producto.ancho || 0}" style="width: 100%; padding: 10px 12px; border: 2px solid #dfe6e9; border-radius: 6px; font-size: 14px; box-sizing: border-box;">
                            <input type="number" id="edit-alto" min="0" step="0.1" placeholder="Alto (cm)" value="${producto.alto || 0}" style="width: 100%; padding: 10px 12px; border: 2px solid #dfe6e9; border-radius: 6px; font-size: 14px; box-sizing: border-box;">
                        </div>
                    </div>
                </form>
                
                <div class="modal-footer" style="display: flex; gap: 10px; justify-content: flex-end; padding: 15px 20px; border-top: 1px solid #ecf0f1; background: #f8f9fa; flex-shrink: 0;">
                    <button type="button" class="modal-close-btn" style="background: #6c757d; color: white; box-shadow: 0 2px 6px rgba(108, 117, 125, 0.3); padding: 10px 24px; font-size: 14px; border: none; border-radius: 6px; font-weight: 600; cursor: pointer;">Cancelar</button>
                    <button type="button" id="btn-eliminar" style="background: #dc3545; color: white; box-shadow: 0 2px 6px rgba(220, 53, 69, 0.3); padding: 10px 24px; font-size: 14px; border: none; border-radius: 6px; font-weight: 600; cursor: pointer;">🗑️ Eliminar</button>
                    <button type="submit" form="form-editar-producto" style="background: #3498db; color: white; box-shadow: 0 2px 6px rgba(52, 152, 219, 0.3); padding: 10px 24px; font-size: 14px; border: none; border-radius: 6px; font-weight: 600; cursor: pointer;">💾 Guardar</button>
                </div>
            </div>
        `;

        document.body.appendChild(modal);

        // Cargar clientes en el select pasando el objeto producto
        cargarClientesParaEdicion(producto);

        // Establecer estado actual
        document.getElementById('edit-estado').value = producto.estado || 'sin_asignar';
        document.getElementById('edit-tipo_medida').value = producto.tipo_medida || '';

        // Cerrar modal
        document.querySelectorAll('.modal-close-btn').forEach(btn => {
            btn.addEventListener('click', function () {
                modal.style.display = 'none';
                modal.remove();
            });
        });

        // Cerrar al hacer click en el backdrop
        modal.addEventListener('click', function (e) {
            if (e.target === modal) {
                modal.style.display = 'none';
                modal.remove();
            }
        });

        // Eliminar producto
        document.getElementById('btn-eliminar').addEventListener('click', function () {
            Swal.fire({
                icon: 'warning',
                title: '¿Estás seguro?',
                text: 'Esta acción no se puede deshacer',
                showCancelButton: true,
                confirmButtonColor: '#dc3545',
                cancelButtonColor: '#6c757d',
                confirmButtonText: 'Sí, eliminar',
                cancelButtonText: 'Cancelar'
            }).then((result) => {
                if (result.isConfirmed) {
                    eliminarProducto(producto.id);
                }
            });
        });

        // Submit del formulario
        document.getElementById('form-editar-producto').addEventListener('submit', function (e) {
            e.preventDefault();
            guardarProductoEditado(producto.id);
        });
    }

    function cargarClientesParaEdicion(producto) {
        const select = document.getElementById('edit-cliente');
        if (!select) return;

        console.log('Producto recibido:', producto);
        console.log('Cliente asignado (valor):', producto.cliente_asignado);
        console.log('Cliente asignado (tipo):', typeof producto.cliente_asignado);

        fetch(ajaxUrl, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: new URLSearchParams({
                action: 'merc_obtener_clientes_lista',
                nonce: nonce
            })
        })
            .then(r => r.json())
            .then(res => {
                if (res.success && res.data && res.data.clientes) {
                    // Limpiar opciones previas
                    select.innerHTML = '<option value="">-- Seleccionar cliente --</option>';

                    res.data.clientes.forEach(cliente => {
                        let option = document.createElement('option');
                        option.value = String(cliente.id); // Convertir a string
                        option.textContent = cliente.nombre;
                        select.appendChild(option);
                    });

                    // Convertir cliente_asignado a string para comparación consistente
                    const clienteId = String(producto.cliente_asignado || '');
                    console.log('Cliente ID a buscar (string):', clienteId);

                    // Establecer el cliente actual ANTES de inicializar SelectSearchable
                    if (clienteId && clienteId !== '0' && clienteId !== '') {
                        console.log('Estableciendo valor del select a:', clienteId);
                        select.value = clienteId;
                    }

                    // Inicializar SelectSearchable después
                    setTimeout(() => {
                        if (typeof window.SelectSearchable !== 'undefined') {
                            console.log('Inicializando SelectSearchable para edit-cliente');
                            const searchable = new window.SelectSearchable(select);
                            console.log('✓ SelectSearchable inicializado en modal de edición');
                            console.log('Valor del select después de init:', select.value);

                            // Actualizar el input de búsqueda con el nombre del cliente
                            setTimeout(() => {
                                const searchInput = select.parentNode.querySelector('.select-search-input');
                                console.log('SearchInput encontrado:', !!searchInput);

                                if (searchInput && select.value) {
                                    const selectedOption = Array.from(select.options).find(o => o.value === select.value);
                                    console.log('Opción seleccionada:', selectedOption?.textContent);

                                    if (selectedOption) {
                                        searchInput.value = selectedOption.textContent;
                                        console.log('Input de búsqueda actualizado a:', selectedOption.textContent);
                                    }
                                }
                            }, 50);
                        } else {
                            console.warn('⚠️ SelectSearchable no disponible aún');
                        }
                    }, 200);
                }
            })
            .catch(err => {
                console.error('Error cargando clientes:', err);
            });
    }

    function guardarProductoEditado(productId) {
        const datos = {
            action: 'merc_actualizar_producto',
            product_id: productId,
            nombre: document.getElementById('edit-nombre').value,
            codigo_barras: document.getElementById('edit-codigo').value,
            cantidad: parseInt(document.getElementById('edit-cantidad').value) || 0,
            cliente_asignado: document.getElementById('edit-cliente').value,
            estado: document.getElementById('edit-estado').value,
            peso: parseFloat(document.getElementById('edit-peso').value) || 0,
            tipo_medida: document.getElementById('edit-tipo_medida').value,
            valor_medida: document.getElementById('edit-valor_medida').value,
            largo: parseFloat(document.getElementById('edit-largo').value) || 0,
            ancho: parseFloat(document.getElementById('edit-ancho').value) || 0,
            alto: parseFloat(document.getElementById('edit-alto').value) || 0,
            nonce: nonce
        };

        fetch(ajaxUrl, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: new URLSearchParams(datos)
        })
            .then(r => r.json())
            .then(res => {
                console.log('Respuesta actualización:', res);
                if (res.success) {
                    Swal.fire({
                        icon: 'success',
                        title: 'Éxito',
                        text: 'Producto actualizado correctamente',
                        confirmButtonColor: '#3498db'
                    }).then(() => {
                        try {
                            const fe = document.getElementById('form-editar-producto');
                            if (fe) fe.reset();
                        } catch (e) { /* noop */ }
                        const modalEdit = document.getElementById('modal-editar-producto');
                        if (modalEdit) try { modalEdit.remove(); } catch (err) { /* noop */ }
                        cargarProductos(); // Recargar tabla
                    });
                } else {
                    Swal.fire({
                        icon: 'error',
                        title: 'Error',
                        text: res.data || 'Error desconocido',
                        confirmButtonColor: '#e74c3c'
                    });
                }
            })
            .catch(err => {
                console.error('Error:', err);
                Swal.fire({
                    icon: 'error',
                    title: 'Error de conexión',
                    text: 'No se pudo conectar con el servidor',
                    confirmButtonColor: '#e74c3c'
                });
            });
    }

    function eliminarProducto(productId) {
        const datos = {
            action: 'merc_eliminar_producto',
            product_id: productId,
            nonce: nonce
        };

        fetch(ajaxUrl, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: new URLSearchParams(datos)
        })
            .then(r => r.json())
            .then(res => {
                console.log('Respuesta eliminación:', res);
                if (res.success) {
                    Swal.fire({
                        icon: 'success',
                        title: 'Éxito',
                        text: 'Producto eliminado correctamente',
                        confirmButtonColor: '#3498db'
                    }).then(() => {
                        document.getElementById('modal-editar-producto').remove();
                        cargarProductos(); // Recargar tabla
                    });
                } else {
                    Swal.fire({
                        icon: 'error',
                        title: 'Error',
                        text: res.data || 'Error desconocido',
                        confirmButtonColor: '#e74c3c'
                    });
                }
            })
            .catch(err => {
                console.error('Error:', err);
                Swal.fire({
                    icon: 'error',
                    title: 'Error de conexión',
                    text: 'No se pudo conectar con el servidor',
                    confirmButtonColor: '#e74c3c'
                });
            });
    }

    // Función global para refrescar productos
    window.refrescarProductos = function () {
        cargarProductos();
    };

    // Función global para eliminar producto
    window.eliminarProducto = function (productId, productName, cantidad) {
        Swal.fire({
            icon: 'warning',
            title: 'Confirmar eliminación',
            html: `¿Está seguro de que desea eliminar el producto <strong>"${productName}"</strong> con <strong>${cantidad}</strong> unidades?<br><small style="color: #e74c3c;">Esta acción no se puede deshacer.</small>`,
            showCancelButton: true,
            confirmButtonColor: '#e74c3c',
            confirmButtonText: '🗑️ Sí, eliminar',
            cancelButtonText: 'Cancelar',
            cancelButtonColor: '#95a5a6'
        }).then((result) => {
            if (result.isConfirmed) {
                const datos = {
                    action: 'merc_eliminar_producto',
                    product_id: productId,
                    nonce: nonce
                };

                fetch(ajaxUrl, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                    },
                    body: new URLSearchParams(datos)
                })
                    .then(r => r.json())
                    .then(res => {
                        console.log('Respuesta eliminación:', res);
                        if (res.success) {
                            Swal.fire({
                                icon: 'success',
                                title: 'Eliminado',
                                text: 'Producto eliminado correctamente',
                                confirmButtonColor: '#27ae60'
                            }).then(() => {
                                cargarProductos(); // Recargar tabla
                            });
                        } else {
                            Swal.fire({
                                icon: 'error',
                                title: 'Error',
                                text: res.data || 'No se pudo eliminar el producto',
                                confirmButtonColor: '#e74c3c'
                            });
                        }
                    })
                    .catch(err => {
                        console.error('Error al eliminar:', err);
                        Swal.fire({
                            icon: 'error',
                            title: 'Error de conexión',
                            text: 'No se pudo conectar con el servidor',
                            confirmButtonColor: '#e74c3c'
                        });
                    });
            }
        });
    };

    // Función global para abrir modal de unidades/envíos
    window.openUnitsModal = function (productId, productName) {
        console.log('Abriendo modal de unidades para producto:', productId, productName);

        const datos = {
            action: 'merc_get_product_units',
            product_id: productId,
            nonce: nonce
        };

        // Mostrar loading mientras se cargan los datos
        Swal.fire({
            title: 'Cargando envíos...',
            didOpen: () => {
                Swal.showLoading();
            },
            allowOutsideClick: false,
            allowEscapeKey: false
        });

        fetch(ajaxUrl, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: new URLSearchParams(datos)
        })
            .then(r => r.json())
            .then(res => {
                console.log('Unidades obtenidas:', res);

                if (res.success && res.data && res.data.length > 0) {
                    let unitsHtml = `
                    <div style="text-align: left; max-height: 400px; overflow-y: auto;">
                        <h3 style="margin-bottom: 15px; color: #2c3e50;">📦 Envíos de "${productName}"</h3>
                        <table style="width: 100%; border-collapse: collapse; font-size: 13px;">
                            <thead>
                                <tr style="background: #ecf0f1; border-bottom: 2px solid #bdc3c7;">
                                    <th style="padding: 10px; text-align: left; font-weight: 600;">SKU</th>
                                    <th style="padding: 10px; text-align: left; font-weight: 600;">Estado</th>
                                    <th style="padding: 10px; text-align: left; font-weight: 600;">Envío #</th>
                                    <th style="padding: 10px; text-align: left; font-weight: 600;">Motorizado</th>
                                    <th style="padding: 10px; text-align: left; font-weight: 600;">Fecha</th>
                                </tr>
                            </thead>
                            <tbody>
                `;

                    res.data.forEach((unit, idx) => {
                        const bgcolor = idx % 2 === 0 ? '#ffffff' : '#f9f9f9';
                        const unitStatusRaw = String(unit.status_effective || unit.status || unit.estado || '').toLowerCase().trim();
                        const unitStatus = (unit.shipment_id && ['available', 'disponible', 'sin_asignar', ''].includes(unitStatusRaw)) ? 'assigned' : unitStatusRaw;

                        const isDelivered = ['entregado', 'delivered'].includes(unitStatus);
                        const isAssigned = ['asignado', 'assigned'].includes(unitStatus);
                        const statusText = isDelivered ? '✅ Entregado' : (isAssigned ? '🚚 Asignado' : '📦 Disponible');
                        const statusColor = isDelivered ? '#27ae60' : (isAssigned ? '#3498db' : '#95a5a6');
                        unitsHtml += `
                        <tr style="background: ${bgcolor}; border-bottom: 1px solid #ecf0f1;">
                            <td style="padding: 10px; font-weight: 600; color: #2c3e50;">${unit.sku || unit.id}</td>
                            <td style="padding: 10px;">
                                <span style="display: inline-block; padding: 4px 8px; background: ${statusColor}; color: white; border-radius: 4px; font-weight: 600; font-size: 12px;">
                                    ${statusText}
                                </span>
                            </td>
                            <td style="padding: 10px; color: #7f8c8d;">${unit.shipment_id ? '#' + unit.shipment_id : '-'}</td>
                            <td style="padding: 10px; color: #2c3e50;">${unit.motorizado || '-'}</td>
                            <td style="padding: 10px; color: #7f8c8d; font-size: 12px;">${unit.created_at || unit.fecha_creacion || '-'}</td>
                        </tr>
                    `;
                    });

                    unitsHtml += `
                            </tbody>
                        </table>
                    </div>
                `;

                    Swal.fire({
                        title: '📋 Detalles de Envíos',
                        html: unitsHtml,
                        icon: 'info',
                        confirmButtonColor: '#3498db',
                        confirmButtonText: 'Cerrar',
                        width: '700px'
                    });
                } else if (res.success && (!res.data || res.data.length === 0)) {
                    Swal.fire({
                        icon: 'info',
                        title: 'Sin envíos',
                        text: `El producto "${productName}" no tiene unidades registradas.`,
                        confirmButtonColor: '#3498db'
                    });
                } else {
                    Swal.fire({
                        icon: 'error',
                        title: 'Error',
                        text: res.data || 'No se pudieron cargar los envíos',
                        confirmButtonColor: '#e74c3c'
                    });
                }
            })
            .catch(err => {
                console.error('Error cargando unidades:', err);
                Swal.fire({
                    icon: 'error',
                    title: 'Error de conexión',
                    text: 'No se pudo conectar con el servidor',
                    confirmButtonColor: '#e74c3c'
                });
            });
    };
});







