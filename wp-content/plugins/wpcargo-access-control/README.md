# WPCargo Access Control

**Versión:** 1.0.0  
**Requiere:** WordPress 5.0+, PHP 7.4+

## 📋 Descripción

Plugin auxiliar para WPCargo enfocado en el desbloqueo manual diario de clientes.

La gestión de permisos, redirecciones y menús ahora se resuelve en el plugin `wpcargo-roles`.

---

## ✨ Características

### 1. **Sistema de Desbloqueo Manual**
- Toggle diario para desbloquear todos los clientes
- Control desde WP-Admin: **Herramientas > Skip Blocks Recojo**
- Aplica `merc_desbloqueado_manualmente_fecha` a todos los clientes
- Automático: el estado se resetea después de medianoche

### 2. **Bypass MON-SAT**
- El modo `mon-sat` mantiene el bypass activo de lunes a sábado
- Los domingos se desactiva automáticamente sin intervención manual

---

## 🚀 Instalación

1. **Subir plugin**
   ```bash
   wp-content/plugins/wpcargo-access-control/
   ```

2. **Activar en WP-Admin**
   ```
   Plugins > Plugins instalados > Activar "WPCargo Access Control"
   ```

3. **Verificar en logs**
   ```
   wp-content/merc_logs/merc-debug-YYYY-MM-DD.log
   # Verá: ✅ WPCargo Access Control activated
   ```

---

## ⚙️ Configuración

No requiere matriz de permisos. Si necesitas control de acceso por rol, usa `wpcargo-roles`.

---

## 🎮 Uso: Desbloqueo Manual

### Vía WP-Admin
```
Herramientas > Skip Blocks Recojo
```
- Click en **🔓 DESBLOQUEAR** para permitir todos los clientes hoy
- Click en **🔒 BLOQUEAR** para re-aplicar restricciones
- Ver log de cambios reciente

### Vía URL (admin only)
```
https://tudominio.com/wp-admin/tools.php?page=merc-skip-blocks&merc_toggle_skip_today=enable
https://tudominio.com/wp-admin/tools.php?page=merc-skip-blocks&merc_toggle_skip_today=disable
```

### Vía Código
```php
// Desbloquear todos los clientes hoy
wpcac_apply_skip_to_all_clients(true);

// Bloquear nuevamente
wpcac_apply_skip_to_all_clients(false);

// Verificar estado
if (wpcac_is_bypass_enabled_today()) {
    echo "Desbloqueo en efecto";
}
```

---

## 🔍 Funciones Públicas

### Desbloqueo Manual

```php
/**
 * Aplicar/remover desbloqueo para todos los clientes
 */
wpcac_apply_skip_to_all_clients($enable);
// $enable: bool (true = desbloquear, false = bloquear)

/**
 * Verificar si desbloqueo está activo hoy
 */
wpcac_is_bypass_enabled_today();
// Retorna: bool

/**
 * Obtener fecha actual
 */
wpcac_get_today();
// Retorna: string (Y-m-d format)

/**
 * Obtener estado de bypass
 */
wpcac_get_bypass_status();
// Retorna: bool
```

---

## 📚 Hooks Available

### Actions

```php
/**
 * Cuando cambia estado de desbloqueo
 */
do_action('wpcac_skip_status_changed', $enable, $today, $count);
```

---

## 🗑️ Desinstalación

El plugin limpia automáticamente:
- ✅ Opciones almacenadas (`merc_skip_blocks_today`, `merc_skip_blocks_mode`)
- ✅ User meta de desbloqueo manual
- ✅ Flush de rewrite rules

---

## 📝 Logs

Todos los eventos se registran en:
```
wp-content/merc_logs/merc-debug-YYYY-MM-DD.log
```

Búsqueda de eventos:
```bash
grep "wpcac" wp-content/merc_logs/merc-debug-2026-03-05.log
```

---

## 🔄 Rutas + Extensiones Previstas

Este es el primer plugin de la arquitectura modular. Próximos plugins:

1. **wpcargo-shipment-filters** - Filtros avanzados
2. **merc-penalties-system** - Sistema de sanciones
3. **merc-csv-import-pro** - Importación mejorada
4. **wpcargo-form-enhancements** - Mejoras de formulario

---

## 🐛 Troubleshooting

### Usuarios redireccionados a home
- ✅ Verificar que email está en matriz de permisos
- ✅ Verificar que rutas no tienen `/` final extra
- ✅ Revisar logs: `grep "template_redirect" merc-debug-*.log`

### Menú items no se ocultan
- ✅ El CSS se aplica en `wp_head`
- ✅ Si persiste, usar DevTools para inspeccionar
- ✅ Puede haber selectores CSS conflictivos

### Desbloqueo no funciona
- ✅ Verificar permisos del usuario (debe ser `administrator`)
- ✅ Revisar `wp_nonce_field` en formulario
- ✅ Chequear logs para errores de nonce

---

## 📞 Soporte

Para reportar problemas o sugerencias:
- Email: info@mercourier.com
- GitHub: (futura integración)

---

**© 2026 Mercourier - Todos los derechos reservados**
