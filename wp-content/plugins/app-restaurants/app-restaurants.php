<?php
/**
 * Plugin Name: Restaurant List
 * Plugin URI:  https://exemple.com
 * Description: Un plugin per gestionar un llistat de restaurants amb favorits i informació personalitzada per usuari.
 * Version: 1.0.0
 * Author: El Teu Nom
 * Author URI: https://exemple.com
 * License: GPL2
 * Text Domain: restaurant-list
 */

// Evitar accés directe
if (!defined('ABSPATH')) {
    exit;
}

// Definir constants del plugin
define('RESTAURANT_LIST_VERSION', '1.0.0');
define('RESTAURANT_LIST_PATH', plugin_dir_path(__FILE__));
define('RESTAURANT_LIST_URL', plugin_dir_url(__FILE__));


require_once RESTAURANT_LIST_PATH . 'inc/acf.php';
require_once RESTAURANT_LIST_PATH . 'inc/gravity-functions.php';
require_once RESTAURANT_LIST_PATH . 'inc/restaurant-functions.php';
// require_once RESTAURANT_LIST_PATH . 'inc/user-registration.php';
// require_once RESTAURANT_LIST_PATH . 'inc/user-login.php';


/*
// En la primera versió del plugin, els CPT es creen amb ACF

// Incloure els fitxers necessaris
require_once RESTAURANT_LIST_PATH . 'inc/custom-post-type.php';

// Funció d'activació
function restaurant_list_activate() {
    require_once RESTAURANT_LIST_PATH . 'inc/custom-post-type.php';
    restaurant_list_register_cpt();
    flush_rewrite_rules();
}
register_activation_hook(__FILE__, 'restaurant_list_activate');

// Funció de desactivació
function restaurant_list_deactivate() {
    flush_rewrite_rules();
}
register_deactivation_hook(__FILE__, 'restaurant_list_deactivate');
*/