jQuery(document).ready(function($) {
  $('#restaurant-registration-form').on('submit', function(e) {
      e.preventDefault();
      $('.error-message').text('');
      $('input').removeClass('input-error');

      $.ajax({
          type: 'POST',
          url: restaurantListAjax.ajax_url, // Utilitzem la variable localitzada
          data: {
              action: 'restaurant_list_register_user',
              username: $('#username').val(),
              email: $('#email').val(),
              password: $('#password').val(),
          },
          success: function(response) {
            console.log(response);
              if (response.success) {
                  $('#restaurant-registration-form').hide();
                  $('#registration-message').html(
                      '<p style="color: green;">' + response.data.message + '</p>' +
                      '<a href="' + restaurantListAjax.login_url + '">' + restaurantListAjax.login_text + '</a>'
                  );
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
