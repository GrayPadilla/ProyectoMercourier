<?php
if (!defined('ABSPATH')) exit;

add_shortcode('merc_almacen_productos', function() {
    $current_user = wp_get_current_user();
    $is_admin = current_user_can('manage_options');
    $is_client = in_array('wpcargo_client', (array)$current_user->roles);
    
    if (!is_user_logged_in() || (!$is_admin && !$is_client)) {
        return '<div style="padding:40px;text-align:center;background:#fff3cd;border:2px solid #ffc107;border-radius:8px;"><h3 style="color:#856404;">⚠️ Acceso Restringido</h3><p>No tienes permisos para ver esta sección.</p></div>';
    }
    
    // Generar nonce para AJAX
    $nonce = wp_create_nonce('merc_almacen');
    
    ob_start();
    ?>
    <div class="almacen-container">
        <div class="almacen-header">
            <h2>📦 Almacén de Productos</h2>
            <?php if ($is_admin): ?>
            <button id="btn-nuevo" class="btn-primary">➕ Nuevo Producto</button>
            <?php endif; ?>
        </div>
        
        <div class="stats-grid">
            <div class="stat-card card-usuarios">
                <div class="stat-icon">👥</div>
                <div class="stat-content">
                    <span class="stat-label">Clientes con productos</span>
                    <span class="stat-value" id="stat-usuarios">0</span>
                </div>
            </div>

            <div class="stat-card card-total">
                <div class="stat-icon">📊</div>
                <div class="stat-content">
                    <span class="stat-label">Total de Productos</span>
                    <span class="stat-value" id="stat-total">0</span>
                </div>
            </div>
            <div class="stat-card card-asignados">
                <div class="stat-icon">🚚</div>
                <div class="stat-content">
                    <span class="stat-label">Productos Asignados</span>
                    <span class="stat-value" id="stat-asignados">0</span>
                </div>
            </div>
            <div class="stat-card card-entregados">
                <div class="stat-icon">✅</div>
                <div class="stat-content">
                    <span class="stat-label">Productos Entregados</span>
                    <span class="stat-value" id="stat-entregados">0</span>
                </div>
            </div>
        </div>

        <div id="almacen-tabla" style="margin-top:20px;">
            <!-- Tabla de productos cargada dinámicamente -->
        </div>
    </div>
    
    <!-- Data inlined para JavaScript -->
    <script>
    window.mercAlmacenData = {
        nonce: '<?php echo esc_js($nonce); ?>',
        isAdmin: <?php echo $is_admin ? 'true' : 'false'; ?>,
        isClient: <?php echo $is_client ? 'true' : 'false'; ?>
    };
    
    console.log('🔷 mercAlmacenData inyectado:', window.mercAlmacenData);
    
    // Custom Select Searchable
    class SelectSearchable {
        constructor(selectElement) {
            this.select = selectElement;
            this.isOpen = false;
            this.init();
        }
        
        init() {
            // Crear contenedor personalizado
            const container = document.createElement('div');
            container.style.cssText = `
                position: relative;
                width: 100%;
            `;
            
            // Crear el input de búsqueda
            const searchInput = document.createElement('input');
            searchInput.type = 'text';
            searchInput.placeholder = 'Buscar cliente...';
            searchInput.className = 'select-search-input';
            searchInput.style.cssText = `
                width: 100%;
                padding: 10px 12px;
                border: 2px solid #dfe6e9;
                border-radius: 6px;
                font-size: 14px;
                box-sizing: border-box;
                cursor: pointer;
            `;
            
            // Crear dropdown para opciones
            const dropdown = document.createElement('div');
            dropdown.className = 'select-dropdown';
            dropdown.style.cssText = `
                position: absolute;
                top: 100%;
                left: 0;
                right: 0;
                background: white;
                border: 2px solid #dfe6e9;
                border-radius: 6px;
                margin-top: 4px;
                z-index: 10000;
                max-height: 300px;
                overflow-y: auto;
                display: none;
                box-shadow: 0 4px 12px rgba(0,0,0,0.15);
            `;
            
            // Ocultar el select original
            this.select.style.display = 'none';
            
            // Insertar elementos en el DOM
            this.select.parentNode.insertBefore(container, this.select);
            container.appendChild(searchInput);
            container.appendChild(dropdown);
            container.appendChild(this.select);
            
            this.searchInput = searchInput;
            this.dropdown = dropdown;
            this.container = container;
            
            // Event listeners
            searchInput.addEventListener('focus', () => this.openDropdown());
            searchInput.addEventListener('click', () => this.openDropdown());
            searchInput.addEventListener('input', (e) => this.filterOptions(e.target.value));
            
            document.addEventListener('click', (e) => {
                if (!this.container.contains(e.target)) {
                    this.closeDropdown();
                }
            });
            
            // Poblar opciones iniciales
            this.populateOptions();
        }
        
        populateOptions() {
            this.dropdown.innerHTML = '';
            
            Array.from(this.select.options).forEach(option => {
                if (option.value === '') return; // Saltar opción vacía
                
                const div = document.createElement('div');
                div.style.cssText = `
                    padding: 10px 12px;
                    cursor: pointer;
                    border-bottom: 1px solid #ecf0f1;
                    transition: background-color 0.2s;
                `;
                div.textContent = option.textContent;
                div.className = 'select-option';
                div.dataset.value = option.value;
                
                div.addEventListener('mouseenter', () => {
                    div.style.backgroundColor = '#f0f4ff';
                });
                div.addEventListener('mouseleave', () => {
                    div.style.backgroundColor = 'transparent';
                });
                
                div.addEventListener('click', () => {
                    this.select.value = option.value;
                    this.searchInput.value = option.textContent;
                    this.closeDropdown();
                });
                
                this.dropdown.appendChild(div);
            });
        }
        
        filterOptions(valor) {
            const valorLower = valor.toLowerCase();
            Array.from(this.dropdown.querySelectorAll('.select-option')).forEach(div => {
                const visible = div.textContent.toLowerCase().includes(valorLower);
                div.style.display = visible ? 'block' : 'none';
            });
        }
        
        openDropdown() {
            this.dropdown.style.display = 'block';
            this.isOpen = true;
            this.searchInput.focus();
        }
        
        closeDropdown() {
            this.dropdown.style.display = 'none';
            this.isOpen = false;
        }
    }
    
    // Inyectar clientes en el select cuando se abra el modal
    document.addEventListener('DOMContentLoaded', function() {
        // Pre-cargar clientes desde AJAX
        function cargarClientesEnSelect() {
            const select = document.getElementById('cliente-select');
            if (!select) {
                console.error('❌ Select cliente-select no encontrado');
                return;
            }
            
            console.log('📋 Cargando clientes en select...');
            
            const ajaxUrl = '<?php echo esc_url(admin_url("admin-ajax.php")); ?>';
            const nonce = window.mercAlmacenData.nonce;
            
            console.log('🔗 URL AJAX:', ajaxUrl);
            console.log('🔐 Nonce:', nonce);
            
            // Obtener clientes del servidor
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
            .then(r => {
                console.log('📡 Respuesta HTTP:', r.status);
                return r.json();
            })
            .then(res => {
                console.log('✅ Respuesta AJAX:', res);
                if (res.success && res.data && res.data.clientes) {
                    
                    // Limpiar opciones previas (excepto la vacía)
                    while (select.options.length > 1) {
                        select.remove(1);
                    }
                    
                    res.data.clientes.forEach(cliente => {
                        let option = document.createElement('option');
                        option.value = cliente.id;
                        option.textContent = cliente.nombre;
                        select.appendChild(option);
                    });
                    
                    // Inicializar el custom select
                    setTimeout(() => {
                        if (!window.selectSearchable) {
                            window.selectSearchable = new SelectSearchable(select);
                            console.log('✓ Custom select inicializado');
                        } else {
                            window.selectSearchable.populateOptions();
                            console.log('✓ Custom select actualizado');
                        }
                    }, 100);
                } else {
                    console.warn('⚠️ Respuesta sin éxito o sin clientes:', res);
                }
            })
            .catch(err => {
                console.error('❌ Error cargando clientes:', err);
            });
        }
        
        // Cargar clientes cuando se abre el modal
        const btnNuevo = document.getElementById('btn-nuevo');
        if (btnNuevo) {
            console.log('✓ Botón "Nuevo Producto" encontrado');
            btnNuevo.addEventListener('click', function() {
                console.log('🖱️ Click en botón Nuevo Producto');
                setTimeout(() => {
                    cargarClientesEnSelect();
                }, 100);
            });
        } else {
            console.warn('⚠️ Botón "Nuevo Producto" no encontrado');
        }
    });
    </script>

    <!-- SweetAlert2 -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.all.min.js"></script>
    <?php
    $css_ver  = file_exists(MERC_WAREHOUSE_DIR . 'assets/styles.css') ? filemtime(MERC_WAREHOUSE_DIR . 'assets/styles.css') : time();
    $js_ver   = file_exists(MERC_WAREHOUSE_DIR . 'assets/scripts.js') ? filemtime(MERC_WAREHOUSE_DIR . 'assets/scripts.js') : time();
    ?>
    <link rel="stylesheet" href="<?php echo esc_url(MERC_WAREHOUSE_URL . 'assets/styles.css?ver=' . $css_ver); ?>">
    <script src="<?php echo esc_url(MERC_WAREHOUSE_URL . 'assets/scripts.js?ver=' . $js_ver); ?>"></script>
    <?php
    return ob_get_clean();
});


