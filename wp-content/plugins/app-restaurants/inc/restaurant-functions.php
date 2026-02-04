<?php

/**
 * Retorna la URL de la imatge de fallback per a un restaurant segons la seva primera categoria.
 * Si té diverses categories, s'usa la primera (excloent "uncategorized").
 * Les imatges de referència per categoria estan al disseny pencil-new.pen; si no hi ha fitxers
 * a assets/fallback/, es retorna un placeholder.
 *
 * @param array|null $categories Array de WP_Term (get_the_terms) o null.
 * @return string URL de la imatge de fallback.
 */
function amc_restaurant_fallback_image_url( $categories ) {
	$base_path = RESTAURANT_LIST_PATH . 'assets/fallback/';
	$base_url  = RESTAURANT_LIST_URL . 'assets/fallback/';
	$ext       = '.jpg';
	$slug      = 'default';

	if ( ! empty( $categories ) && ! is_wp_error( $categories ) ) {
		foreach ( $categories as $term ) {
			if ( $term->slug === 'uncategorized' ) {
				continue;
			}
			$slug = $term->slug;
			break;
		}
	}

	$filename = 'fallback-' . $slug . $ext;
	if ( ! file_exists( $base_path . $filename ) ) {
		$filename = 'fallback-default' . $ext;
	}

	if ( file_exists( $base_path . $filename ) ) {
		return $base_url . $filename;
	}

	// Placeholder quan no hi ha fitxers (disseny de referència a pencil-new.pen)
	$label = ( $slug === 'default' ) ? 'Restaurant' : ucfirst( $slug );
	return 'https://placehold.co/400x300/EDECEA/6D6C6A?text=' . rawurlencode( $label );
}

function restaurant_submission_form() {
  // Verificar que l'usuari està autenticat
  if (!is_user_logged_in()) {
    return '<p>Has d\'<a href="' . wp_login_url(get_permalink()) . '">iniciar sessió</a> per crear restaurants.</p>';
  }
  
  ob_start(); ?>
  
  <form id="restaurant-form">
  <input type="hidden" name="action" value="handle_restaurant_submission"> <!-- Camp afegit -->
  <input type="hidden" name="nonce" value="<?php echo wp_create_nonce('nonce-add-restaurant'); ?>"> <!-- Camp afegit -->
  <input type="hidden" name="author" value="<?php echo get_current_user_id(); ?>"> <!-- Camp afegit -->
  <label for="restaurant_name">Nom del Restaurant:</label>
  <input type="text" id="restaurant_name" name="restaurant_name" required>
  
  <label for="address">Adreça:</label>
  <input type="text" id="address" name="address" required>
  
  <ul id="suggestions"></ul> <!-- Llista per suggeriments -->
  
  <label for="description">Descripció:</label>
  <textarea id="description" name="description" rows="4" placeholder="Descriu el restaurant..."></textarea>
  
  <label for="category">Categoria:</label>
  <select id="category" name="category" required>
    <option value="">Selecciona una categoria</option>
    <?php
    $categories = get_terms(array(
      'taxonomy' => 'category',
      'hide_empty' => false,
    ));
    foreach ($categories as $category) {
      if ($category->slug !== 'uncategorized') {
        echo '<option value="' . esc_attr($category->term_id) . '">' . esc_html($category->name) . '</option>';
      }
    }
    ?>
  </select>
  
  <input type="hidden" id="latitude" name="latitude">
  <input type="hidden" id="longitude" name="longitude">
  
  <fieldset>
  <legend>Dies i horaris d'obertura:</legend>
  <?php
  $days = ['monday' => 'Dilluns', 'tuesday' => 'Dimarts', 'wednesday' => 'Dimecres', 
           'thursday' => 'Dijous', 'friday' => 'Divendres', 'saturday' => 'Dissabte', 'sunday' => 'Diumenge'];
  foreach ($days as $day_key => $day_label) :
  ?>
    <div class="opening-day-row">
      <label>
        <input type="checkbox" name="opening_days[<?php echo $day_key; ?>]" class="day-checkbox" data-day="<?php echo $day_key; ?>">
        <strong><?php echo $day_label; ?></strong>
      </label>
      <div class="opening-hours">
        <label>
          Obertura: <input type="time" name="opening_hours[<?php echo $day_key; ?>][open]">
        </label>
        <label>
          Tancament: <input type="time" name="opening_hours[<?php echo $day_key; ?>][close]">
        </label>
      </div>
    </div>
  <?php endforeach; ?>
  </fieldset>
  
  <script>
  document.addEventListener('DOMContentLoaded', function() {
    document.querySelectorAll('.day-checkbox').forEach(function(checkbox) {
      checkbox.addEventListener('change', function() {
        const dayRow = this.closest('.opening-day-row');
        const hoursDiv = dayRow.querySelector('.opening-hours');
        if (this.checked) {
          hoursDiv.style.display = 'block';
        } else {
          hoursDiv.style.display = 'none';
        }
      });
    });
  });
  </script>
  
  <button type="submit">Crear Restaurant</button>
  </form>
  
  
  <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
  <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
  
  
  <script>
  document.addEventListener("DOMContentLoaded", function() {
    
    const addressInput = document.getElementById("address");
    const latitudeInput = document.getElementById("latitude");
    const longitudeInput = document.getElementById("longitude");
    
    console.log("Camps detectats:", addressInput, latitudeInput, longitudeInput);
    
    if (!addressInput || !latitudeInput || !longitudeInput) {
      console.error("No s'han trobat els camps correctes.");
      return;
    }
    
    // Coordenades per defecte (si no n'hi ha guardades)
    let initialLat = latitudeInput.value ? parseFloat(latitudeInput.value) : 41.3879; // Barcelona
    let initialLon = longitudeInput.value ? parseFloat(longitudeInput.value) : 2.1699;
    
    // Crear contenidor per al mapa
    const mapContainerId = "restaurant-form-map";
    let mapContainer = document.getElementById(mapContainerId);
    if (!mapContainer) {
      mapContainer = document.createElement("div");
      mapContainer.id = mapContainerId;
      mapContainer.style = "height: 400px; width: 100%; margin-top: 10px; margin-bottom: 10px;";
      addressInput.parentElement.appendChild(mapContainer);
    }
    
    // Inicialitzar el mapa
    const map = L.map(mapContainerId, { zoomControl: true }).setView([initialLat, initialLon], 15);
    
    // Afegir capa de mapes de OpenStreetMap
    L.tileLayer("https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png", {
      attribution: '&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a> contributors'
    }).addTo(map);
    
    // Afegir marcador draggable
    const marker = L.marker([initialLat, initialLon], { draggable: true }).addTo(map)
    .bindPopup("Ubicació seleccionada")
    .openPopup();
    
    // Redibuixar el mapa quan es carrega
    setTimeout(() => {
      map.invalidateSize();
    }, 500);
    
    // 🔹 **ACTUALITZAR ADREÇA QUAN ES MOU EL MARCADOR**
    function updateAddressFromCoords(lat, lon) {
      fetch(`https://nominatim.openstreetmap.org/reverse?format=json&lat=${lat}&lon=${lon}`)
      .then(response => response.json())
      .then(data => {
        if (data && data.display_name) {
          addressInput.value = data.display_name;
          console.log("Nova adreça trobada:", data.display_name);
        } else {
          console.warn("No s'ha trobat cap adreça per aquestes coordenades.");
        }
      })
      .catch(error => console.error("Error a l'API de Nominatim (Reverse Geocoding):", error));
    }
    
    // Comprovar si el navegador suporta geolocalització
    if ("geolocation" in navigator) {
      navigator.geolocation.getCurrentPosition(
        function (position) {
          const userLat = position.coords.latitude;
          const userLon = position.coords.longitude;
          
          // Actualitzar mapa i marcador amb la ubicació de l'usuari
          map.setView([userLat, userLon], 15);
          marker.setLatLng([userLat, userLon]).bindPopup("La teva ubicació actual").openPopup();
          
          // Actualitzar camps
          latitudeInput.value = userLat;
          longitudeInput.value = userLon;
          
          console.log("Ubicació de l'usuari trobada:", userLat, userLon);
          
          // Obtenir l'adreça basada en les coordenades trobades
          updateAddressFromCoords(userLat, userLon);
        },
        function (error) {
          console.warn("No s'ha pogut obtenir la ubicació de l'usuari:", error.message);
        }
      );
    } else {
      console.warn("El teu navegador no suporta la geolocalització.");
    }
    
    // Quan el marcador es mou, actualitzar les coordenades i l'adreça
    marker.on("dragend", function (event) {
      const position = marker.getLatLng();
      latitudeInput.value = position.lat;
      longitudeInput.value = position.lng;
      console.log("Nova latitud:", position.lat, "Nova longitud:", position.lng);
      
      // Obtenir l'adreça basada en les noves coordenades
      updateAddressFromCoords(position.lat, position.lng);
    });
    
    // Quan l'usuari busca una adreça, actualitzar el mapa i les coordenades
    addressInput.addEventListener("blur", function () {
      if (this.value.length > 3) {
        fetch(`https://nominatim.openstreetmap.org/search?format=json&q=${encodeURIComponent(this.value)}`)
        .then(response => response.json())
        .then(data => {
          if (data.length > 0) {
            const result = data[0];
            const newLatLng = [parseFloat(result.lat), parseFloat(result.lon)];
            
            // Actualitzar mapa i marcador
            map.setView(newLatLng, 15);
            marker.setLatLng(newLatLng).bindPopup(result.display_name).openPopup();
            
            // Actualitzar camps ACF
            latitudeInput.value = result.lat;
            longitudeInput.value = result.lon;
            console.log("Ubicació trobada:", result.display_name, "Lat:", result.lat, "Lon:", result.lon);
          } else {
            alert("No s'han trobat resultats per aquesta adreça.");
          }
        })
        .catch(error => console.error("Error a l'API de Nominatim:", error));
      }
    });
    
    document.getElementById("restaurant-form").addEventListener("submit", function(e) {
      e.preventDefault();
      
      let formData = new FormData(this);
      
      // Mostrar indicador de càrrega
      const submitBtn = this.querySelector('button[type="submit"]');
      const originalBtnText = submitBtn.textContent;
      submitBtn.disabled = true;
      submitBtn.textContent = 'Creant...';
      
      fetch("<?php echo admin_url('admin-ajax.php'); ?>", {
        method: "POST",
        body: new URLSearchParams(formData) // Convertim FormData a x-www-form-urlencoded
      })
      .then(response => {
        if (!response.ok) {
          throw new Error('Error de xarxa: ' + response.status);
        }
        return response.json();
      })
      .then(data => {
        if (data.success) {
          alert("Restaurant creat correctament!");
          this.reset();
          // Opcional: redirigir a la pàgina del restaurant
          if (data.data.redirect_url) {
            window.location.href = data.data.redirect_url;
          }
        } else {
          const errorMsg = data.data && data.data.message ? data.data.message : 'Error desconegut';
          alert("Error: " + errorMsg);
        }
      })
      .catch(error => {
        console.error("Error AJAX:", error);
        alert("Error de connexió. Si us plau, torna-ho a intentar.");
      })
      .finally(() => {
        submitBtn.disabled = false;
        submitBtn.textContent = originalBtnText;
      });
    });
  });
  
  </script>
  
  
  <?php return ob_get_clean();
}
add_shortcode('restaurant_form', 'restaurant_submission_form');

// PROCESSAR EL FORMULARI A PHP
function handle_restaurant_submission() {
  // Verificar que l'usuari està autenticat
  if (!is_user_logged_in()) {
    wp_send_json_error(["message" => "Has d'estar registrat per crear restaurants"]);
    wp_die();
  }
  
  // Verificar permisos: només usuaris amb capacitat de publicar posts poden crear restaurants
  if (!current_user_can('publish_posts')) {
    wp_send_json_error(["message" => "No tens permisos per crear restaurants"]);
    wp_die();
  }
  
  // Verificar nonce per seguretat CSRF
  if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'nonce-add-restaurant')) {
    wp_send_json_error(["message" => "Nonce no vàlid. Si us plau, recarrega la pàgina i torna-ho a intentar."]);
    wp_die();
  }
  
  if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST["action"]) && $_POST["action"] === "handle_restaurant_submission" ) {
    $name = sanitize_text_field($_POST["restaurant_name"]);
    $address = sanitize_text_field($_POST["address"]);
    $latitude = sanitize_text_field($_POST["latitude"]);
    $longitude = sanitize_text_field($_POST["longitude"]);
    $opening_days = isset($_POST["opening_days"]) ? $_POST["opening_days"] : [];
    $opening_hours = isset($_POST["opening_hours"]) ? $_POST["opening_hours"] : [];
    $description = isset($_POST["description"]) ? sanitize_textarea_field($_POST["description"]) : "";
    $category_id = isset($_POST["category"]) ? intval($_POST["category"]) : 0;
    $author = get_current_user_id(); // Utilitzar l'usuari actual, no el del POST
    
    // Verifiquem que els camps requerits no són buits
    if (empty($name) || empty($address) || empty($latitude) || empty($longitude) || empty($category_id)) {
      wp_send_json_error(["message" => "Falten dades obligatòries (nom, adreça, coordenades i categoria)"]);
      wp_die();
    }
    
    // Validar coordenades numèriques
    if (!is_numeric($latitude) || !is_numeric($longitude)) {
      wp_send_json_error(["message" => "Les coordenades no són vàlides"]);
      wp_die();
    }
    
    // Validar duplicats: comprovar si ja existeix un restaurant amb el mateix nom i adreça similar
    $existing_restaurants = get_posts([
      'post_type' => 'restaurant',
      'posts_per_page' => -1,
      'post_status' => 'any',
      's' => $name, // Cerca per nom
    ]);
    
    $tolerance = 0.001; // Tolerància de ~100m per coordenades
    foreach ($existing_restaurants as $existing) {
      $existing_location = get_field('location', $existing->ID);
      if ($existing_location) {
        $existing_lat = floatval($existing_location['latitude']);
        $existing_lng = floatval($existing_location['longitude']);
        $new_lat = floatval($latitude);
        $new_lng = floatval($longitude);
        
        // Comprovar si el nom és similar (insensible a majúscules/minúscules)
        if (strcasecmp($existing->post_title, $name) === 0) {
          // Comprovar si les coordenades són properes
          if (abs($existing_lat - $new_lat) < $tolerance && abs($existing_lng - $new_lng) < $tolerance) {
            wp_send_json_error(["message" => "Ja existeix un restaurant amb aquest nom i adreça"]);
            wp_die();
          }
        }
      }
    }
    
    // Crear el post del restaurant
    $post_id = wp_insert_post([
      "post_title" => $name,
      "post_content" => $description, // Guardar descripció al contingut
      "post_type" => "restaurant",
      "post_status" => "publish",
      "post_author" => $author
    ]);
    
    if ($post_id) {
      // Guardar ubicació
      update_field("location", ["latitude" => $latitude, "longitude" => $longitude, "address" => $address], $post_id);
      
      // Guardar dies d'obertura
      foreach (["monday", "tuesday", "wednesday", "thursday", "friday", "saturday", "sunday"] as $day) {
        update_field("opening_days_" . $day, isset($opening_days[$day]) ? 1 : 0, $post_id);
      }
      
      // Guardar horaris d'obertura (si existeixen)
      if (!empty($opening_hours)) {
        $hours_data = [];
        foreach ($opening_hours as $day => $times) {
          if (isset($opening_days[$day]) && $opening_days[$day]) {
            $hours_data[$day] = [
              'open' => sanitize_text_field($times['open']),
              'close' => sanitize_text_field($times['close'])
            ];
          }
        }
        if (!empty($hours_data)) {
          update_field("opening_hours", $hours_data, $post_id);
        }
      }
      
      // Assignar categoria
      if ($category_id) {
        wp_set_post_terms($post_id, [$category_id], 'category');
      }
      
      // Retornar URL del restaurant creat
      $restaurant_url = get_permalink($post_id);
      wp_send_json_success([
        "message" => "Restaurant creat correctament",
        "redirect_url" => $restaurant_url,
        "restaurant_id" => $post_id
      ]);
    } else {
      wp_send_json_error(["message" => "No s'ha pogut crear el restaurant"]);
    }
  } else {
    wp_send_json_error(["message" => "Petició no vàlida"]);
  }
  wp_die();
}
add_action("wp_ajax_handle_restaurant_submission", "handle_restaurant_submission");
// Eliminat wp_ajax_nopriv_ per forçar autenticació




function amc_list_restaurants() {
  wp_enqueue_style('leaflet-css', 'https://unpkg.com/leaflet@1.9.4/dist/leaflet.css');
  wp_enqueue_script('leaflet-js', 'https://unpkg.com/leaflet@1.9.4/dist/leaflet.js', [], null, true);

  $terms = get_terms(array(
    'taxonomy' => 'category',
    'hide_empty' => false,
  ));

  ob_start();
  ?>
  <div class="directori-restaurants">
    <section class="directori-hero">
      <h1 class="directori-hero__title">Troba el teu restaurant ideal</h1>
      <p class="directori-hero__subtitle">Explora locals de menjar per categoria i descobreix noves adreces</p>
    </section>

    <div id="restaurant-filters" class="directori-filters">
      <form id="restaurant-filter-form">
        <span class="directori-filters__label">Categories</span>
        <div class="filter-pills">
          <button type="button" class="filter-pill filter-pill--active" data-filter="all" aria-pressed="true">Tots</button>
          <?php
          if (!empty($terms)) :
            foreach ($terms as $term) :
              if ($term->slug === 'uncategorized') continue;
              ?>
              <label class="filter-pill">
                <input type="checkbox" name="category[]" value="<?php echo esc_attr($term->slug); ?>" hidden>
                <span><?php echo esc_html($term->name); ?></span>
              </label>
            <?php endforeach;
          endif;
          ?>
        </div>
        <div class="random-section">
          <button type="button" id="random-restaurant-btn" class="random-button">🎲 Restaurant Aleatori</button>
        </div>
      </form>
    </div>

    <div class="directori-view-toggle">
      <span class="directori-view-toggle__label">Vista</span>
      <div class="view-toggle" role="group" aria-label="<?php esc_attr_e( 'Vista llistat, graella o mapa', 'amc' ); ?>">
        <button type="button" class="view-toggle__btn view-toggle__btn--active" data-view="list" aria-pressed="true"><?php esc_html_e( 'Llistat', 'amc' ); ?></button>
        <button type="button" class="view-toggle__btn" data-view="grid" aria-pressed="false"><?php esc_html_e( 'Graella', 'amc' ); ?></button>
        <button type="button" class="view-toggle__btn" data-view="map" aria-pressed="false"><?php esc_html_e( 'Mapa', 'amc' ); ?></button>
      </div>
    </div>

    <div id="restaurant-list" class="directori-list">
      <!-- Restaurants carregats via AJAX -->
    </div>

    <div id="restaurant-map" class="directori-map" aria-hidden="true"></div>
  </div>
  <?php
  return ob_get_clean();
}
    
add_shortcode("list_restaurants", "amc_list_restaurants");
  
  
  
add_action('wp_ajax_filter_restaurants', 'amc_ajax_filter_restaurants');
add_action('wp_ajax_nopriv_filter_restaurants', 'amc_ajax_filter_restaurants');
  
function amc_ajax_filter_restaurants() {
  $tax_query = [];
  
  if (!empty($_GET['category'])) {
    $tax_query[] = array(
      'taxonomy' => 'category',
      'field' => 'slug',
      'terms' => array_map('sanitize_text_field', $_GET['category']),
    );
  }
    
  $args = array(
    'post_type' => 'restaurant',
    'posts_per_page' => -1,
    'tax_query' => $tax_query,
  );
  
  $query = new WP_Query($args);
  
  if ($query->have_posts()) :
    while ($query->have_posts()) : $query->the_post();
      $location = get_field('location');
      $address = !empty($location['address']) ? $location['address'] : '';
      $lat = !empty($location['latitude']) ? $location['latitude'] : '';
      $lng = !empty($location['longitude']) ? $location['longitude'] : '';
      $rating = get_post_meta(get_the_ID(), 'rating', true);
      $rating_value = is_numeric($rating) ? number_format((float) $rating, 1, '.', '') : '';
      $categories = get_the_terms(get_the_ID(), 'category');
      ?>
      <?php
      $fallback_url = amc_restaurant_fallback_image_url( $categories );
      ?>
      <article class="restaurant-item restaurant-card" data-lat="<?php echo esc_attr($lat); ?>" data-long="<?php echo esc_attr($lng); ?>">
        <div class="restaurant-card__image">
          <?php if (has_post_thumbnail()) : ?>
            <a href="<?php the_permalink(); ?>"><?php the_post_thumbnail('medium_large', array('alt' => get_the_title())); ?></a>
          <?php else : ?>
            <a href="<?php the_permalink(); ?>" class="restaurant-card__image-fallback">
              <img src="<?php echo esc_url( $fallback_url ); ?>" alt="" width="400" height="300" loading="lazy">
            </a>
          <?php endif; ?>
        </div>
        <div class="restaurant-card__content">
          <h2 class="restaurant-name"><a href="<?php the_permalink(); ?>"><?php the_title(); ?></a></h2>
          <?php if ($rating_value !== '') : ?>
            <div class="restaurant-rating">
              <span class="restaurant-rating__stars" aria-hidden="true"><?php echo esc_html(str_repeat('★', min(5, (int) round($rating)))); ?><?php echo esc_html(str_repeat('☆', 5 - min(5, (int) round($rating)))); ?></span>
              <span class="restaurant-rating__score"><?php echo esc_html($rating_value); ?></span>
            </div>
          <?php endif; ?>
          <?php if ($address) : ?>
            <p class="restaurant-address"><?php echo esc_html($address); ?></p>
          <?php endif; ?>
          <?php if (!empty($categories) && !is_wp_error($categories)) : ?>
            <div class="restaurant-tags">
              <?php foreach ($categories as $term) : if ($term->slug === 'uncategorized') continue; ?>
                <span class="restaurant-tag"><?php echo esc_html($term->name); ?></span>
              <?php endforeach; ?>
            </div>
          <?php endif; ?>
          <div class="restaurant-actions">
            <a class="restaurant-link" href="<?php the_permalink(); ?>">Veure Detalls</a>
            <?php if (is_user_logged_in()) : ?>
              <?php echo restaurant_favorite_button(get_the_ID()); ?>
            <?php endif; ?>
          </div>
          <p class="restaurant-distance" aria-live="polite"></p>
        </div>
      </article>
      <?php
    endwhile;
  else :
    echo '<p class="directori-empty">No s\'han trobat restaurants.</p>';
  endif;
    
  wp_die(); // sempre amb AJAX
}

// ============================================
// SISTEMA DE FAVORITS
// ============================================

/**
 * Toggle favorit d'un restaurant
 */
function handle_toggle_favorite() {
  // Verificar autenticació
  if (!is_user_logged_in()) {
    wp_send_json_error(["message" => "Has d'estar registrat per afegir favorits"]);
    wp_die();
  }
  
  // Verificar nonce
  if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'toggle-favorite-nonce')) {
    wp_send_json_error(["message" => "Nonce no vàlid"]);
    wp_die();
  }
  
  $restaurant_id = intval($_POST['restaurant_id']);
  $user_id = get_current_user_id();
  
  if (!$restaurant_id) {
    wp_send_json_error(["message" => "ID de restaurant no vàlid"]);
    wp_die();
  }
  
  // Obtenir favorits actuals de l'usuari
  $user_favorites = get_field('favorites', 'user_' . $user_id);
  if (!is_array($user_favorites)) {
    $user_favorites = [];
  }
  
  // Convertir a array d'IDs
  $favorite_ids = [];
  foreach ($user_favorites as $fav) {
    if (is_object($fav)) {
      $favorite_ids[] = $fav->ID;
    } elseif (is_numeric($fav)) {
      $favorite_ids[] = $fav;
    }
  }
  
  // Toggle: afegir o eliminar
  $is_favorite = in_array($restaurant_id, $favorite_ids);
  
  if ($is_favorite) {
    // Eliminar de favorits
    $favorite_ids = array_diff($favorite_ids, [$restaurant_id]);
    $message = "Restaurant eliminat dels favorits";
  } else {
    // Afegir a favorits
    $favorite_ids[] = $restaurant_id;
    $message = "Restaurant afegit als favorits";
  }
  
  // Guardar favorits actualitzats
  update_field('favorites', $favorite_ids, 'user_' . $user_id);
  
  wp_send_json_success([
    "message" => $message,
    "is_favorite" => !$is_favorite
  ]);
  wp_die();
}
add_action('wp_ajax_toggle_favorite', 'handle_toggle_favorite');

/**
 * Comprovar si un restaurant és favorit
 */
function is_restaurant_favorite($restaurant_id, $user_id = null) {
  if (!$user_id) {
    $user_id = get_current_user_id();
  }
  
  if (!$user_id) {
    return false;
  }
  
  $user_favorites = get_field('favorites', 'user_' . $user_id);
  if (!is_array($user_favorites)) {
    return false;
  }
  
  foreach ($user_favorites as $fav) {
    $fav_id = is_object($fav) ? $fav->ID : $fav;
    if ($fav_id == $restaurant_id) {
      return true;
    }
  }
  
  return false;
}

/**
 * Botó de favorits per a restaurants
 */
function restaurant_favorite_button($restaurant_id = null) {
  if (!is_user_logged_in()) {
    return '<p class="favorite-login-required"><a href="' . wp_login_url(get_permalink()) . '">Inicia sessió</a> per afegir favorits</p>';
  }
  
  if (!$restaurant_id) {
    $restaurant_id = get_the_ID();
  }
  
  $is_favorite = is_restaurant_favorite($restaurant_id);
  $nonce = wp_create_nonce('toggle-favorite-nonce');
  
  ob_start();
  ?>
  <button class="favorite-btn <?php echo $is_favorite ? 'is-favorite' : ''; ?>" 
          data-restaurant-id="<?php echo esc_attr($restaurant_id); ?>"
          data-nonce="<?php echo esc_attr($nonce); ?>">
    <span class="favorite-icon"><?php echo $is_favorite ? '❤️' : '🤍'; ?></span>
    <span class="favorite-text"><?php echo $is_favorite ? 'Eliminar de favorits' : 'Afegir a favorits'; ?></span>
  </button>
  <script>
  document.addEventListener('DOMContentLoaded', function() {
    document.querySelectorAll('.favorite-btn').forEach(function(btn) {
      btn.addEventListener('click', function() {
        const restaurantId = this.dataset.restaurantId;
        const nonce = this.dataset.nonce;
        const icon = this.querySelector('.favorite-icon');
        const text = this.querySelector('.favorite-text');
        
        fetch('<?php echo admin_url('admin-ajax.php'); ?>', {
          method: 'POST',
          headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
          },
          body: new URLSearchParams({
            action: 'toggle_favorite',
            restaurant_id: restaurantId,
            nonce: nonce
          })
        })
        .then(response => response.json())
        .then(data => {
          if (data.success) {
            if (data.data.is_favorite) {
              icon.textContent = '❤️';
              text.textContent = 'Eliminar de favorits';
              this.classList.add('is-favorite');
            } else {
              icon.textContent = '🤍';
              text.textContent = 'Afegir a favorits';
              this.classList.remove('is-favorite');
            }
          } else {
            alert(data.data.message || 'Error al gestionar favorits');
          }
        })
        .catch(error => {
          console.error('Error:', error);
          alert('Error de connexió');
        });
      });
    });
  });
  </script>
  <?php
  return ob_get_clean();
}

/**
 * Shortcode per mostrar botó de favorits
 */
function restaurant_favorite_button_shortcode($atts) {
  $atts = shortcode_atts([
    'restaurant_id' => get_the_ID()
  ], $atts);
  
  return restaurant_favorite_button($atts['restaurant_id']);
}
add_shortcode('favorite_button', 'restaurant_favorite_button_shortcode');

/**
 * Shortcode per mostrar llistat de favorits de l'usuari
 */
function user_favorites_list() {
  if (!is_user_logged_in()) {
    return '<p>Has d\'<a href="' . wp_login_url(get_permalink()) . '">iniciar sessió</a> per veure els teus favorits.</p>';
  }
  
  $user_id = get_current_user_id();
  $favorites = get_field('favorites', 'user_' . $user_id);
  
  if (empty($favorites) || !is_array($favorites)) {
    return '<p>No tens cap restaurant als favorits encara.</p>';
  }
  
  ob_start();
  ?>
  <div class="user-favorites-list">
    <h2>Els meus restaurants favorits</h2>
    <ul class="favorites-list">
      <?php foreach ($favorites as $favorite) : 
        $restaurant_id = is_object($favorite) ? $favorite->ID : $favorite;
        $restaurant = get_post($restaurant_id);
        if (!$restaurant) continue;
        $location = get_field('location', $restaurant_id);
      ?>
        <li class="favorite-item">
          <h3><a href="<?php echo get_permalink($restaurant_id); ?>"><?php echo esc_html($restaurant->post_title); ?></a></h3>
          <?php if ($location && $location['address']) : ?>
            <p class="favorite-address">📍 <?php echo esc_html($location['address']); ?></p>
          <?php endif; ?>
          <?php echo restaurant_favorite_button($restaurant_id); ?>
        </li>
      <?php endforeach; ?>
    </ul>
  </div>
  <?php
  return ob_get_clean();
}
add_shortcode('user_favorites', 'user_favorites_list');

// ============================================
// SISTEMA DE COMENTARIS PRIVATS PER USUARI
// ============================================

/**
 * Afegir comentari privat a un restaurant favorit
 */
function handle_add_private_comment() {
  // Verificar autenticació
  if (!is_user_logged_in()) {
    wp_send_json_error(["message" => "Has d'estar registrat per afegir comentaris"]);
    wp_die();
  }
  
  // Verificar nonce
  if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'add-private-comment-nonce')) {
    wp_send_json_error(["message" => "Nonce no vàlid"]);
    wp_die();
  }
  
  $restaurant_id = intval($_POST['restaurant_id']);
  $comment_text = sanitize_textarea_field($_POST['comment']);
  $user_id = get_current_user_id();
  
  if (!$restaurant_id || empty($comment_text)) {
    wp_send_json_error(["message" => "Dades incompletes"]);
    wp_die();
  }
  
  // Verificar que el restaurant està als favorits de l'usuari
  if (!is_restaurant_favorite($restaurant_id, $user_id)) {
    wp_send_json_error(["message" => "Només pots afegir comentaris als teus restaurants favorits"]);
    wp_die();
  }
  
  // Obtenir comentaris existents
  $comments = get_post_meta($restaurant_id, '_user_private_comments', true);
  if (!is_array($comments)) {
    $comments = [];
  }
  
  // Afegir nou comentari
  $new_comment = [
    'id' => uniqid('comment_'),
    'user_id' => $user_id,
    'comment' => $comment_text,
    'date' => current_time('mysql'),
    'timestamp' => current_time('timestamp')
  ];
  
  $comments[] = $new_comment;
  
  // Guardar comentaris
  update_post_meta($restaurant_id, '_user_private_comments', $comments);
  
  wp_send_json_success([
    "message" => "Comentari afegit correctament",
    "comment" => $new_comment
  ]);
  wp_die();
}
add_action('wp_ajax_add_private_comment', 'handle_add_private_comment');

/**
 * Eliminar comentari privat
 */
function handle_delete_private_comment() {
  if (!is_user_logged_in()) {
    wp_send_json_error(["message" => "Has d'estar registrat"]);
    wp_die();
  }
  
  if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'delete-private-comment-nonce')) {
    wp_send_json_error(["message" => "Nonce no vàlid"]);
    wp_die();
  }
  
  $restaurant_id = intval($_POST['restaurant_id']);
  $comment_id = sanitize_text_field($_POST['comment_id']);
  $user_id = get_current_user_id();
  
  $comments = get_post_meta($restaurant_id, '_user_private_comments', true);
  if (!is_array($comments)) {
    wp_send_json_error(["message" => "Comentari no trobat"]);
    wp_die();
  }
  
  // Filtrar comentaris (només eliminar els propis)
  $updated_comments = [];
  $found = false;
  foreach ($comments as $comment) {
    if ($comment['id'] === $comment_id && $comment['user_id'] == $user_id) {
      $found = true;
      continue; // No afegir aquest comentari
    }
    $updated_comments[] = $comment;
  }
  
  if (!$found) {
    wp_send_json_error(["message" => "Comentari no trobat o no tens permisos"]);
    wp_die();
  }
  
  update_post_meta($restaurant_id, '_user_private_comments', $updated_comments);
  
  wp_send_json_success(["message" => "Comentari eliminat correctament"]);
  wp_die();
}
add_action('wp_ajax_delete_private_comment', 'handle_delete_private_comment');

/**
 * Obtenir comentaris privats d'un restaurant per l'usuari actual
 */
function get_user_private_comments($restaurant_id, $user_id = null) {
  if (!$user_id) {
    $user_id = get_current_user_id();
  }
  
  if (!$user_id) {
    return [];
  }
  
  $all_comments = get_post_meta($restaurant_id, '_user_private_comments', true);
  if (!is_array($all_comments)) {
    return [];
  }
  
  // Filtrar només els comentaris de l'usuari actual
  $user_comments = [];
  foreach ($all_comments as $comment) {
    if (isset($comment['user_id']) && $comment['user_id'] == $user_id) {
      $user_comments[] = $comment;
    }
  }
  
  // Ordenar per data (més recents primer)
  usort($user_comments, function($a, $b) {
    return ($b['timestamp'] ?? 0) - ($a['timestamp'] ?? 0);
  });
  
  return $user_comments;
}

/**
 * Formulari i llistat de comentaris privats
 */
function restaurant_private_comments($restaurant_id = null) {
  if (!is_user_logged_in()) {
    return '<p>Has d\'<a href="' . wp_login_url(get_permalink()) . '">iniciar sessió</a> per veure i afegir comentaris.</p>';
  }
  
  if (!$restaurant_id) {
    $restaurant_id = get_the_ID();
  }
  
  $user_id = get_current_user_id();
  
  // Verificar que el restaurant està als favorits
  if (!is_restaurant_favorite($restaurant_id, $user_id)) {
    return '<p class="comments-favorite-required">Afegeix aquest restaurant als teus favorits per poder afegir comentaris privats.</p>';
  }
  
  $comments = get_user_private_comments($restaurant_id, $user_id);
  $nonce = wp_create_nonce('add-private-comment-nonce');
  $delete_nonce = wp_create_nonce('delete-private-comment-nonce');
  
  ob_start();
  ?>
  <div class="private-comments-section">
    <h3>Els meus comentaris privats</h3>
    
    <form class="private-comment-form" data-restaurant-id="<?php echo esc_attr($restaurant_id); ?>" data-nonce="<?php echo esc_attr($nonce); ?>">
      <textarea name="comment" rows="3" placeholder="Escriu el teu comentari privat..." required></textarea>
      <button type="submit">Afegir Comentari</button>
    </form>
    
    <div class="private-comments-list">
      <?php if (empty($comments)) : ?>
        <p class="no-comments">Encara no has afegit cap comentari.</p>
      <?php else : ?>
        <?php foreach ($comments as $comment) : ?>
          <div class="private-comment-item" data-comment-id="<?php echo esc_attr($comment['id']); ?>">
            <div class="comment-content">
              <p><?php echo nl2br(esc_html($comment['comment'])); ?></p>
              <span class="comment-date"><?php echo date_i18n(get_option('date_format') . ' ' . get_option('time_format'), $comment['timestamp']); ?></span>
            </div>
            <button class="delete-comment-btn" 
                    data-restaurant-id="<?php echo esc_attr($restaurant_id); ?>"
                    data-comment-id="<?php echo esc_attr($comment['id']); ?>"
                    data-nonce="<?php echo esc_attr($delete_nonce); ?>">
              Eliminar
            </button>
          </div>
        <?php endforeach; ?>
      <?php endif; ?>
    </div>
  </div>
  
  <script>
  document.addEventListener('DOMContentLoaded', function() {
    // Afegir comentari
    document.querySelectorAll('.private-comment-form').forEach(function(form) {
      form.addEventListener('submit', function(e) {
        e.preventDefault();
        
        const restaurantId = this.dataset.restaurantId;
        const nonce = this.dataset.nonce;
        const textarea = this.querySelector('textarea');
        const commentText = textarea.value.trim();
        
        if (!commentText) {
          alert('Escriu un comentari abans d\'enviar');
          return;
        }
        
        fetch('<?php echo admin_url('admin-ajax.php'); ?>', {
          method: 'POST',
          headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
          },
          body: new URLSearchParams({
            action: 'add_private_comment',
            restaurant_id: restaurantId,
            comment: commentText,
            nonce: nonce
          })
        })
        .then(response => response.json())
        .then(data => {
          if (data.success) {
            textarea.value = '';
            location.reload(); // Recarregar per mostrar el nou comentari
          } else {
            alert(data.data.message || 'Error al afegir el comentari');
          }
        })
        .catch(error => {
          console.error('Error:', error);
          alert('Error de connexió');
        });
      });
    });
    
    // Eliminar comentari
    document.querySelectorAll('.delete-comment-btn').forEach(function(btn) {
      btn.addEventListener('click', function() {
        if (!confirm('Estàs segur que vols eliminar aquest comentari?')) {
          return;
        }
        
        const restaurantId = this.dataset.restaurantId;
        const commentId = this.dataset.commentId;
        const nonce = this.dataset.nonce;
        
        fetch('<?php echo admin_url('admin-ajax.php'); ?>', {
          method: 'POST',
          headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
          },
          body: new URLSearchParams({
            action: 'delete_private_comment',
            restaurant_id: restaurantId,
            comment_id: commentId,
            nonce: nonce
          })
        })
        .then(response => response.json())
        .then(data => {
          if (data.success) {
            this.closest('.private-comment-item').remove();
            // Si no queden comentaris, mostrar missatge
            const commentsList = document.querySelector('.private-comments-list');
            if (commentsList && commentsList.querySelectorAll('.private-comment-item').length === 0) {
              commentsList.innerHTML = '<p class="no-comments">Encara no has afegit cap comentari.</p>';
            }
          } else {
            alert(data.data.message || 'Error al eliminar el comentari');
          }
        })
        .catch(error => {
          console.error('Error:', error);
          alert('Error de connexió');
        });
      });
    });
  });
  </script>
  <?php
  return ob_get_clean();
}

/**
 * Shortcode per mostrar comentaris privats
 */
function restaurant_private_comments_shortcode($atts) {
  $atts = shortcode_atts([
    'restaurant_id' => get_the_ID()
  ], $atts);
  
  return restaurant_private_comments($atts['restaurant_id']);
}
add_shortcode('private_comments', 'restaurant_private_comments_shortcode');