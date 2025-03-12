jQuery(document).ready(function($) {
  $('#restaurant-login-form').on('submit', function(e) {
      e.preventDefault();
      $('.error-message').text('');
      $('input').removeClass('input-error');

      $.ajax({
          type: 'POST',
          url: restaurantListAjax.ajax_url, // Usar el `admin-ajax.php`
          data: {
              action: 'restaurant_list_login_user',
              username: $('#login-username').val(),
              password: $('#login-password').val(),
          },
          success: function(response) {
              if (response.success) {
                  window.location.href = restaurantListAjax.profile_url; // Redirigir al perfil
              } else {
                  $.each(response.data.errors, function(field, message) {
                      $('#' + field).addClass('input-error');
                      $('#' + field + '-error').text(message);
                  });
              }
          }
      });
  });
});
