<?php
/**
 * Plugin Name:     PDX Sync
 * Plugin URI:      https://www.magnic.com/
 * Description:     PDX sync plugin
 * Author:          Magnic Oy
 * Author URI:      https://www.magnic.com
 * Copyright:       2020 Magnic Oy
 * Text Domain:     pdx-sync
 * Domain Path:     /languages
 * Version:         1.0.3
 *
 * @package         Pdx_Sync
 */
 

define( 'PDX_SYNC__PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
define( 'PDX_SYNC__PLUGIN_URL', plugin_dir_url( __FILE__ ) );
define( 'PDX_SYNC__INC_DIR', PDX_SYNC__PLUGIN_DIR. 'inc' . DIRECTORY_SEPARATOR );
define( 'PDX_SYNC__WIDGETS_DIR', PDX_SYNC__PLUGIN_DIR. 'widgets' . DIRECTORY_SEPARATOR );
define( 'PDX_SYNC__CSS_URL', 'assets/css/' );
define( 'PDX_SYNC__JS_URL', 'assets/js/' );
define( 'PDX_SYNC__INC_URL', 'inc/' );


require_once PDX_SYNC__INC_DIR . 'db_structure.php';
require_once PDX_SYNC__INC_DIR . 'pdx_item.php';
require_once PDX_SYNC__INC_DIR . 'format_helper.php';
require_once PDX_SYNC__INC_DIR . 'file_handler.php';
require_once PDX_SYNC__INC_DIR . 'picture.php';
require_once PDX_SYNC__INC_DIR . 'pdx_handler_xml.php';
require_once PDX_SYNC__INC_DIR . 'pdx_handler.php';

require_once PDX_SYNC__WIDGETS_DIR . 'view_apartments.php';
require_once PDX_SYNC__WIDGETS_DIR . 'view_apartments_render.php';
require_once PDX_SYNC__WIDGETS_DIR . 'view_apartments_small_render.php';
require_once PDX_SYNC__WIDGETS_DIR . 'view_apartment_render.php';

require_once PDX_SYNC__PLUGIN_DIR . 'class.pdx-sync.php';


add_action( 'init', array( 'PDXSync', 'init' ) );
add_action( 'widgets_init', array( 'PDXSync', 'initWidgets' ));
add_action( 'plugins_loaded', array( 'PDXSync', 'pluginsLoaded' ) );
add_action( 'admin_menu', array( 'PDXSync', 'pluginMenuInit' ) );
add_action( 'admin_init', array( 'PDXSync', 'pluginAdminInit' ) );

register_activation_hook( __FILE__, array( 'PDXSync', 'install' ) );
register_deactivation_hook( __FILE__, array( 'PDXSync', 'uninstall' ) );
