# 🚀 Guía de Instalación: WPCargo Access Control

## ✅ Pre-requisitos

- WordPress 5.0 o superior
- PHP 7.4 o superior
- WPCargo plugin instalado y activo
- Acceso a WP-Admin

---

## 📦 Pasos de Instalación

### 1. **Plugin ya está en la carpeta correcta**
```
✓ d:\Descargas\public_html\wp-content\plugins\wpcargo-access-control\
```

### 2. **Activar Plugin en WP-Admin**

```
Dashboard > Plugins > Plugins Instalados
↓
Buscar: "WPCargo Access Control"
↓
Click en "Activar"
```

O vía URL:
```
https://tudominio.com/wp-admin/plugins.php
```

### 3. **Verificar Activación**

✅ **Método 1: Ver en Plugins**
- El plugin aparecerá en la lista de "Activos"

✅ **Método 2: Revisar Logs**
```bash
tail -20 wp-content/merc_logs/merc-debug-$(date +%Y-%m-%d).log
# Debería ver: ✅ WPCargo Access Control activated
```

✅ **Método 3: Ir a Herramientas**
```
wp-admin > Herramientas > Skip Blocks Recojo
```
Si ve esta página, ¡el plugin está activo!

---

## ⚙️ Configuración Inicial

Este plugin ya no administra matrices de permisos ni restricciones de rutas. Esa responsabilidad se movió a `wpcargo-roles`.

La configuración aquí se limita al desbloqueo manual desde:

```
WP-Admin > Herramientas > Skip Blocks Recojo
```

---

## 🎯 Primeros Pasos

### 1. Activar Bypass Manual (Opcional)
```
WP-Admin > Herramientas > Skip Blocks Recojo
↓
Click: 🔓 DESBLOQUEAR TODOS LOS CLIENTES
```

Esto permite que todos los clientes creen envíos sin restricciones por hoy.

### 2. Revisar Logs
```bash
grep "wpcac" wp-content/merc_logs/merc-debug-*.log
```

---

## 🔄 Migración desde functions.php

### Paso 1: Remover código del plugin de functions.php

El siguiente código debe ser removido si todavía vive en tu theme:
- Matriz de permisos antigua
- Filtros de acceso (`template_redirect`)
- Filtros de sidebar
- Page de desbloqueo (`merc_render_skip_blocks_page()`)
- Handlers de toggle

**Guardar functions.php actualizado después:**
```bash
cd d:\Descargas\public_html\wp-content\themes\blocksy-child
# Hacer backup primero
cp functions.php functions.php.backup
```

### Paso 2: Limpiar functions.php

Buscar y remover estas líneas:
```php
// ============================================================
// PERMITIR PERMISOS TOTALES A mercourier2019@gmail.com
// ============================================================
// ... hasta línea 442

// Dejar solo lo esencial:
- Enqueue de styles
- Formato de fechas
- Otros hooks NO relacionados a access control
```

### Paso 3: Verificar que el plugin está cargando

Usar función de test:
```php
if (function_exists('wpcac_get_bypass_status')) {
    echo "✅ Plugin está activo y funcional";
} else {
    echo "❌ Plugin no está cargado";
}
```

---

## 📋 Checklist de Verificación

- [ ] Plugin visible en `/wp-admin/plugins.php`
- [ ] Plugin está activado (color verde)
- [ ] Página "Skip Blocks Recojo" accesible en Herramientas
- [ ] Log muestra "activated"
- [ ] Bypass manual funciona
- [ ] functions.php limpio de código de acceso

---

## 🆘 Troubleshooting

### ❌ Plugin no aparece en la lista
- Verificar que la carpeta existe:
  ```bash
  ls -la wp-content/plugins/wpcargo-access-control/
  ```
- Verificar que `wpcargo-access-control.php` existe
- Hacer `wp-admin > Plugins > Actualizar lista`

### ❌ "Fatal error: Call to undefined function"
- Plugin no está cargado correctamente
- Verificar archivo principal no tiene errores de sintaxis:
  ```bash
  php -l wp-content/plugins/wpcargo-access-control/wpcargo-access-control.php
  ```

### ❌ El bypass no cambia de estado
- Verificar que el usuario tenga acceso a Herramientas
- Revisar logs para confirmar el toggle
- Verificar opciones `merc_skip_blocks_today` y `merc_skip_blocks_mode`

---

## 📞 Soporte

Si encuentra problemas:
1. Revisar `/wp-content/merc_logs/` para mensajes
2. Activar `WP_DEBUG` en `wp-config.php`:
   ```php
   define('WP_DEBUG', true);
   define('WP_DEBUG_LOG', true);
   define('WP_DEBUG_DISPLAY', false);
   ```
3. Revisar `/wp-content/debug.log`

---

## 🎉 ¡Completado!

El plugin está instalado y funcional. Ahora puede:
- ✅ Desbloquear clientes manualmente
- ✅ Mantener el bypass MON-SAT
- ✅ Mantener functions.php limpio

**Próximo paso:** Crear siguiente plugin `wpcargo-shipment-filters`

---

**Última actualización:** 5 de marzo, 2026  
**Versión de plugin:** 1.0.0
