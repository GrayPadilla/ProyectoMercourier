<?php
if(!defined('ABSPATH')){
  exit;
}
function wpccf_plugins_loaded_cb() {
  $install_db = new WPCargo_Custom_Fields_Install_DB;
  $install_db->add_conditional_logic_table_columns();
  $install_db->add_html_calculator_table_columns();
}
add_action('plugins_loaded', 'wpccf_plugins_loaded_cb', 99);