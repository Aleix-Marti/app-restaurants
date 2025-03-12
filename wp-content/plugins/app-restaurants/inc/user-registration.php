<?php
// Evitar accés directe
if (!defined('ABSPATH')) {
    exit;
}

// Enqueue del JavaScript
function restaurant_list_enqueue_scripts() {
  wp_enqueue_script(
      'restaurant-registration-script',
      plugin_dir_url(__FILE__) . '../js/registration.js', // Ruta correcta al fitxer JS
      array('jquery'), 
      RESTAURANT_LIST_VERSION, 
      true
  );

  // Localitzem les dades per utilitzar-les en el JavaScript
  wp_localize_script('restaurant-registration-script', 'restaurantListAjax', array(
      'ajax_url'   => admin_url('admin-ajax.php'),
      'login_url'  => wp_login_url(),
      'login_text' => __('Iniciar sessió', 'restaurant-list'),
  ));
}
add_action('wp_enqueue_scripts', 'restaurant_list_enqueue_scripts');


// Shortcode per mostrar el formulari de registre amb AJAX
function restaurant_list_registration_form() {
  if (is_user_logged_in()) {
      return '<p>' . __('Ja estàs registrat.', 'restaurant-list') . '</p>';
  }

  ob_start();
  ?>
  <form id="restaurant-registration-form" method="post">
      <p>
          <label for="username"><?php _e('Nom d\'usuari', 'restaurant-list'); ?></label>
          <input type="text" name="username" id="username">
          <span class="error-message" id="username-error"></span>
      </p>
      <p>
          <label for="email"><?php _e('Correu electrònic', 'restaurant-list'); ?></label>
          <input type="email" name="email" id="email">
          <span class="error-message" id="email-error"></span>
      </p>
      <p>
          <label for="password"><?php _e('Contrasenya', 'restaurant-list'); ?></label>
          <input type="password" name="password" id="password">
          <span class="error-message" id="password-error"></span>
      </p>
      <p>
          <button type="submit" id="register-button"><?php _e('Registrar-se', 'restaurant-list'); ?></button>
      </p>
  </form>
  <div id="registration-message"></div>
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
add_shortcode('user_registration', 'restaurant_list_registration_form');

// Processar el registre amb AJAX
function restaurant_list_handle_ajax_registration() {
    $username = sanitize_user($_POST['username']);
    $email = sanitize_email($_POST['email']);
    $password = $_POST['password'];

    $errors = array();

    if (empty($username)) {
        $errors['username'] = __('El nom d\'usuari és obligatori.', 'restaurant-list');
    } elseif (username_exists($username)) {
        $errors['username'] = __('Aquest nom d\'usuari ja existeix.', 'restaurant-list');
    }

    if (empty($email)) {
        $errors['email'] = __('El correu electrònic és obligatori.', 'restaurant-list');
    } elseif (!is_email($email)) {
        $errors['email'] = __('El correu electrònic no és vàlid.', 'restaurant-list');
    } elseif (email_exists($email)) {
        $errors['email'] = __('Aquest correu electrònic ja està en ús.', 'restaurant-list');
    }

    if (empty($password)) {
        $errors['password'] = __('La contrasenya no pot estar buida.', 'restaurant-list');
    }

    if (!empty($errors)) {
        wp_send_json_error(array('errors' => $errors));
    }

    $user_id = wp_create_user($username, $password, $email);

    if (is_wp_error($user_id)) {
        wp_send_json_error(array('errors' => array('username' => $user_id->get_error_message())));
    } else {
        wp_send_json_success(array('message' => __('Registre complet! Pots iniciar sessió.', 'restaurant-list')));
    }
}

// Registrem l'acció AJAX
add_action('wp_ajax_nopriv_restaurant_list_register_user', 'restaurant_list_handle_ajax_registration');