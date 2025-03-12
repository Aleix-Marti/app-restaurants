<?php
// Evitar accés directe
if (!defined('ABSPATH')) {
    exit;
}

// Enqueue del JavaScript per al login
function restaurant_list_enqueue_login_script() {
    wp_enqueue_script(
        'restaurant-login-script',
        plugin_dir_url(__FILE__) . '../js/login.js',
        array('jquery'),
        RESTAURANT_LIST_VERSION,
        true
    );

    wp_localize_script('restaurant-login-script', 'restaurantListAjax', array(
        'ajax_url'     => admin_url('admin-ajax.php'),
        'profile_url'  => home_url('/perfil'), // Canvia aquest enllaç per la teva pàgina de perfil
    ));
}
add_action('wp_enqueue_scripts', 'restaurant_list_enqueue_login_script');


// Shortcode per mostrar el formulari de login
function restaurant_list_login_form() {
    if (is_user_logged_in()) {
        return '<p>' . __('Ja estàs identificat.', 'restaurant-list') . '</p>';
    }

    ob_start();
    ?>
    <form id="restaurant-login-form" method="post">
        <p>
            <label for="login-username"><?php _e('Nom d\'usuari o correu electrònic', 'restaurant-list'); ?></label>
            <input type="text" name="username" id="login-username">
            <span class="error-message" id="login-username-error"></span>
        </p>
        <p>
            <label for="login-password"><?php _e('Contrasenya', 'restaurant-list'); ?></label>
            <input type="password" name="password" id="login-password">
            <span class="error-message" id="login-password-error"></span>
        </p>
        <p>
            <button type="submit"><?php _e('Iniciar Sessió', 'restaurant-list'); ?></button>
        </p>
        <div id="login-message"></div>
    </form>
    <style>
        .input-error {
            border: 2px solid red;
        }
        .error-message {
            color: red;
            font-size: 14px;
        }
    </style>
    <?php

    return ob_get_clean();
}

// Afegir shortcode
add_shortcode('user_login', 'restaurant_list_login_form');

// Gestionar el login amb AJAX
function restaurant_list_handle_ajax_login() {
    $username = sanitize_text_field($_POST['username']);
    $password = $_POST['password'];

    $errors = array();

    if (empty($username)) {
        $errors['login-username'] = __('El nom d\'usuari és obligatori.', 'restaurant-list');
    }

    if (empty($password)) {
        $errors['login-password'] = __('La contrasenya és obligatòria.', 'restaurant-list');
    }

    if (!empty($errors)) {
        wp_send_json_error(array('errors' => $errors));
    }

    $user = wp_signon(array(
        'user_login'    => $username,
        'user_password' => $password,
        'remember'      => true,
    ));

    if (is_wp_error($user)) {
        wp_send_json_error(array('errors' => array('login-username' => __('Credencials incorrectes.', 'restaurant-list'))));
    } else {
        wp_send_json_success();
    }
}

// Registrem l'acció AJAX
add_action('wp_ajax_nopriv_restaurant_list_login_user', 'restaurant_list_handle_ajax_login');

