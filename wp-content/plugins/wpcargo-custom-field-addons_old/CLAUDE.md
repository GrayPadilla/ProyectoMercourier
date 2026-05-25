# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## Project Overview

WPCargo Custom Field Add-ons is a WordPress plugin (v7.0.1) that extends the WPCargo plugin by allowing customizable fields for shipment tracking forms. It provides a flexible form builder system with support for multiple field types, conditional logic, and auto-calculation features.

**Dependencies:**
- Requires WPCargo plugin to be active
- PHP 8.3 compatible (as of v7.0.0)
- WordPress environment

## Code Style Requirements

**CRITICAL:** Always reference and apply the following style guides for ALL code changes:
- `GUIA_ESTILOS.md` - Comprehensive style guide
- `INSTRUCCIONES_FORMATEO_CODIGO.md` - Code formatting instructions

These files are mandatory and must be consulted before making any code modifications.

## Architecture

### Database Schema
The plugin uses a custom database table `{prefix}wpcargo_custom_fields` to store field definitions. Key columns include:
- `field_key` - Unique meta key identifier
- `field_type` - Type of field (text, select, checkbox, date, file, etc.)
- `section` - Which section the field belongs to (shipper_info, receiver_info, shipment_info, or custom)
- `field_data` - Serialized field options/configuration
- `display_flags` - Serialized array of user roles that should NOT see this field
- `weight` - Order/priority for field display
- `condition_logic_enable`, `condition_show_hide`, `condition_options`, `condition_list` - Conditional logic settings
- `formula`, `currency` - Auto-calculation and currency settings

### Core Components

**WPCCF_Fields Class** (`admin/classes/wpccf-fields.php`)
- Primary class for field retrieval and rendering
- `get_custom_fields($flag)` - Retrieves fields for a specific section with role-based filtering
- `convert_to_form_fields($fields, $post_id)` - Renders fields as HTML form elements with conditional logic support
- `get_fields_data($flag, $shipment_id)` - Retrieves and formats field values for display

**WPC_CF_Form_Builder Class** (`admin/classes/wpc-cf-form-builder.php`)
- Handles form field creation, editing, and management in admin
- Large file (50k+ tokens) - use offset/limit when reading specific portions

**WPC_CF_Hooks Class** (`classes/wpc-cf-hooks.php`)
- Manages WordPress action/filter hooks
- Overrides default WPCargo templates for tracking and printing
- Integrates custom scripts (conditional logic, auto-calculation)

**WPCargo_Custom_Fields_Install_DB Class** (`admin/classes/wpc-cf-install-db.php`)
- Database table creation and updates
- Activation hooks

### Field Types

Supported field types (see `wpccf_field_type_list()` in `includes/wpc-cf-functions.php`):
- Basic: text, textarea, email, number
- Selection: select, multiselect, radio, checkbox
- Special: address (with street/city/state/postcode/country), file (media upload), date, time, datetime, url, html, agent

### Key Features

**Conditional Logic:**
- Fields can be shown/hidden based on other field values
- Supports AND/OR conditions (`condition_options`: 'all' or 'any')
- Condition checkers: 'is', 'is-not', 'empty', 'not-empty'
- Implemented via `wpccf-conditionize` class and JavaScript

**Auto-Calculation:**
- Text/Number fields support formula-based calculations
- Uses `jAutoCalc` library
- Formula stored in `formula` column, currency in `currency` column

**Role-Based Display:**
- Fields can be hidden from specific user roles via `display_flags`
- Logic in `WPCCF_Fields::get_custom_fields()` filters by current user role

### Template System

Templates can be overridden in theme:
- Default location: `{plugin}/templates/`
- Theme override: `{theme}/wpcargo/wpcargo-custom-field-addons/{template-name}.php`
- Helper function: `wpccf_include_template($file_name)`

### Sections

Default sections:
- `shipper_info` - Shipper Information
- `receiver_info` - Receiver Information
- `shipment_info` - Shipment Information

Custom sections can be added via settings (`wpc_cf_additional_options`).

## Common Helper Functions

Located in `admin/includes/functions.php` and `includes/wpc-cf-functions.php`:

- `wpccf_get_all_custom_fields()` - Get all fields from database
- `wpccf_get_custom_fields_by_flag($flag)` - Get fields for specific section
- `wpccf_get_field_by_metakey($metakey)` - Retrieve single field by meta key
- `wpccf_get_shipment_sections()` - Get all active sections with labels
- `wpccf_extract_address($post_id, $address_metakey)` - Parse serialized address data
- `wpccf_registered_metakeys()` - Get all custom field meta keys
- `wpccf_reserve_metakeys()` - Reserved meta keys that cannot be used

## Important Notes

### Security
- All database queries use `$wpdb->prefix` for table prefix
- Fields use `maybe_unserialize()` for stored data
- Direct file access prevented with `if (!defined('ABSPATH')) exit;`

### Metakey Handling
- Some metakeys are reserved (e.g., `wpcargo_tracking_number`, `wpcargo_shipments_update`)
- Field values are stored as post meta using `field_key` as meta_key
- Array values (checkbox, multiselect) are serialized before storage

### File Organization
```
admin/
  classes/        - Admin-side class files
  includes/       - Admin helper functions and hooks
  templates/      - Admin template files (metaboxes, print, settings)
classes/          - Frontend class files (scripts, filters, hooks)
includes/         - Frontend helper functions
templates/        - Frontend templates (tracking results)
```

## Version History

Recent significant changes:
- v7.0.1 - Fixed jQuery auto-calculate script error on public pages
- v7.0.0 - PHP 8.3 compatibility fixes
- v6.1.0+ - Added HTML field, conditional logic, auto-calculation features
- v6.0.0 - Added conditional logics, Cyrillic parsing support
