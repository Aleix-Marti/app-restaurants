<?php

function restaurant_submission_form() {
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
  
  <!-- <input type="hidden" id="latitude" name="latitude"> -->
  <!-- <input type="hidden" id="longitude" name="longitude"> -->
  <input type="text" id="latitude" name="latitude">
  <input type="text" id="longitude" name="longitude">
  
  <fieldset>
  <legend>Dies d'obertura:</legend>
  <label><input type="checkbox" name="opening_days[monday]"> Dilluns</label>
  <label><input type="checkbox" name="opening_days[tuesday]"> Dimarts</label>
  <label><input type="checkbox" name="opening_days[wednesday]"> Dimecres</label>
  <label><input type="checkbox" name="opening_days[thursday]"> Dijous</label>
  <label><input type="checkbox" name="opening_days[friday]"> Divendres</label>
  <label><input type="checkbox" name="opening_days[saturday]"> Dissabte</label>
  <label><input type="checkbox" name="opening_days[sunday]"> Diumenge</label>
  </fieldset>
  
  <button type="submit">Crear Restaurant</button>
  </form>
  
  
  <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
  <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
  
  
  <script>
  document.addEventListener("DOMContentLoaded", function() {
    
    const addressInput = document.getElementById("address");
    const latitudeInput = document.getElementById("latitude");
    const longitudeInput = document.getElementById("longitude");
    
    
    
    // Comprovar si el navegador suporta geolocalització
    if ("geolocation" in navigator) {
      navigator.geolocation.getCurrentPosition(
        function (position) {
          const userLat = position.coords.latitude;
          const userLon = position.coords.longitude;
          
          // Actualitzar mapa i marcador amb la ubicació de l'usuari
          map.setView([userLat, userLon], 15);
          marker.setLatLng([userLat, userLon]).bindPopup("La teva ubicació actual").openPopup();
          
          // Actualitzar camps ACF
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
    
    
    
    // addressInput.addEventListener("input", function () {
    //     fetch(`https://nominatim.openstreetmap.org/search?format=json&q=${encodeURIComponent(this.value)}`)
    //         .then(response => response.json())
    //         .then(data => {
    //             if (data.length > 0) {
    //                 const result = data[0];
    //                 latitudeInput.value = result.lat;
    //                 longitudeInput.value = result.lon;
    //             }
    //         });
    // });
    
    console.log("Camps detectats:", addressInput, latitudeInput, longitudeInput);
    
    if (!addressInput || !latitudeInput || !longitudeInput) {
      console.error("No s'han trobat els camps ACF correctes.");
      return;
    }
    
    // Coordenades per defecte (si no n'hi ha guardades)
    let initialLat = latitudeInput.value ? parseFloat(latitudeInput.value) : 41.3879; // Barcelona
    let initialLon = longitudeInput.value ? parseFloat(longitudeInput.value) : 2.1699;
    
    // Crear contenidor per al mapa dins de l'editor
    const mapContainerId = "acf-leaflet-map";
    let mapContainer = document.getElementById(mapContainerId);
    if (!mapContainer) {
      mapContainer = document.createElement("div");
      mapContainer.id = mapContainerId;
      mapContainer.style = "height: 400px; width: 100%; margin-top: 10px;";
      longitudeInput.parentElement.appendChild(mapContainer); // Afegir el mapa sota el camp de longitud
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
    
    // SOLUCIÓ: Redibuixar el mapa quan l'usuari obre l'editor
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
      
      fetch("<?php echo admin_url('admin-ajax.php'); ?>", {
        method: "POST",
        body: new URLSearchParams(formData) // Convertim FormData a x-www-form-urlencoded
      })
      .then(response => response.json())
      .then(data => {
        if (data.success) {
          alert("Restaurant creat correctament!");
          this.reset();
        } else {
          alert("Error: " + data.message);
        }
      })
      .catch(error => {
        console.error("Error AJAX:", error);
      });
    });
  });
  
  </script>
  
  <style>
  #suggestions {
    list-style: none;
    padding: 0;
    border: 1px solid #ccc;
    max-height: 150px;
    overflow-y: auto;
    background: white;
    position: absolute;
  }
  #suggestions li {
    padding: 5px;
    cursor: pointer;
  }
  #suggestions li:hover {
    background: #f0f0f0;
  }
  </style>
  
  <?php return ob_get_clean();
}
add_shortcode('restaurant_form', 'restaurant_submission_form');

// PROCESSAR EL FORMULARI A PHP
function handle_restaurant_submission() {
  // if (!( isset( $_REQUEST['_wpnonce'] ) && wp_verify_nonce( $_REQUEST['_wpnonce'], 'nonce-add-restaurant' ) )) {
  //     wp_send_json_error(["message" => "Nonce no vàlid"]);
  //     wp_die();
  // }
  
  if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST["action"]) && $_POST["action"] === "handle_restaurant_submission" ) {
    $name = sanitize_text_field($_POST["restaurant_name"]);
    $address = sanitize_text_field($_POST["address"]);
    $latitude = sanitize_text_field($_POST["latitude"]);
    $longitude = sanitize_text_field($_POST["longitude"]);
    $opening_days = isset($_POST["opening_days"]) ? $_POST["opening_days"] : [];
    $author = isset($_POST["author"]) ? $_POST["author"] : 0;
    
    // Verifiquem que els camps requerits no són buits
    if (empty($name) || empty($address) || empty($latitude) || empty($longitude)) {
      wp_send_json_error(["message" => "Falten dades obligatòries"]);
      wp_die();
    }
    
    // Crear el post del restaurant
    $post_id = wp_insert_post([
      "post_title" => $name,
      "post_type" => "restaurant",
      "post_status" => "publish",
      "post_author" => $author
    ]);
    
    if ($post_id) {
      // update_field("adress", $address, $post_id);
      update_field("location", ["latitude" => $latitude, "longitude" => $longitude, "address" => $address], $post_id);
      
      foreach (["monday", "tuesday", "wednesday", "thursday", "friday", "saturday", "sunday"] as $day) {
        update_field("opening_days_" . $day, isset($opening_days[$day]) ? 1 : 0, $post_id);
      }
      
      wp_send_json_success(["message" => "Restaurant creat correctament"]);
    } else {
      wp_send_json_error(["message" => "No s'ha pogut crear el restaurant"]);
    }
  } else {
    wp_send_json_error(["message" => "Petició no vàlida"]);
  }
  wp_die();
}
add_action("wp_ajax_handle_restaurant_submission", "handle_restaurant_submission");
add_action("wp_ajax_nopriv_handle_restaurant_submission", "handle_restaurant_submission");




function amc_list_restaurants() {
  
  ?>
  <div id="restaurant-filters">
    <?php
    $terms = get_terms(array(
      'taxonomy' => 'category',
      'hide_empty' => false,
    ));
    
    if (!empty($terms)) : ?>
    <form id="restaurant-filter-form">
      <div class="filter-buttons">
      <?php foreach ($terms as $term) : ?>
        <?php if ($term->slug === 'uncategorized') continue; ?>
        <label class="filter-button">
          <input type="checkbox" name="category[]" value="<?php echo esc_attr($term->slug); ?>" hidden>
          <?php echo esc_html($term->name); ?>
        </label>
      <?php endforeach; ?>
      </div>
    </form>
    <?php endif; ?>
  </div>
      
  <div id="restaurant-list">
  <!-- Aquí es carregaran els restaurants amb AJAX -->
  </div>
    
      
  <?php
  
  
  // $restaurants = get_posts([
  //     "post_type"      => "restaurant",
  //     "posts_per_page" => -1
  // ]);
  
  $tax_query = array();
  
  if ( isset($_GET['category']) && is_array($_GET['category']) ) {
    $tax_query[] = array(
      'taxonomy' => 'category',
      'field'    => 'slug',
      'terms'    => array_map('sanitize_text_field', $_GET['category']),
    );
  }
      
  $args = array(
    'post_type' => 'restaurant',
    'posts_per_page' => -1,
    'tax_query' => $tax_query,
  );
  
  $restaurants = new WP_Query($args);
    
    
  // Carregar scripts de Leaflet només quan calgui el mapa
  wp_enqueue_style('leaflet-css', 'https://unpkg.com/leaflet@1.9.4/dist/leaflet.css');
  wp_enqueue_script('leaflet-js', 'https://unpkg.com/leaflet@1.9.4/dist/leaflet.js', [], null, true);
  
  ob_start(); ?>
    
  <div>
    <label>
      <input type="radio" name="view-toggle" value="list" checked> Vista Llista
    </label>
    <label>
      <input type="radio" name="view-toggle" value="map"> Vista Mapa
    </label>
  </div>
      
        
  <div id="restaurant-map" style="height: 500px; display: none;"></div>
      
  <?php return ob_get_clean();
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
    while ($query->have_posts()) : $query->the_post(); ?>
      <div class="restaurant-item" data-long="<?php echo get_field('location')['longitude']; ?>" data-lat="<?php echo get_field('location')['latitude']; ?>">
        <h2 class="restaurant-name"><?php the_title(); ?></h2>
        <p class="restaurant-location"><?php echo get_field('location')['address']; ?></p>
        <a class="restaurant-link" href="<?php echo get_permalink(); ?>">Veure Detalls</a>
      </div>
    <?php endwhile;
  else :
    echo '<p>No s\'han trobat restaurants.</p>';
  endif;
    
  wp_die(); // sempre amb AJAX
}
    
      
      
      
      
            
/*
function amc_list_restaurants() {
$restaurants = get_posts([
"post_type"      => "restaurant",
"posts_per_page" => -1
]);

// Carregar scripts de Leaflet només quan calgui el mapa
wp_enqueue_style('leaflet-css', 'https://unpkg.com/leaflet@1.9.4/dist/leaflet.css');
wp_enqueue_script('leaflet-js', 'https://unpkg.com/leaflet@1.9.4/dist/leaflet.js', [], null, true);

ob_start(); ?>

<div>
<label>
<input type="radio" name="view-toggle" value="list" checked> Vista Llista
</label>
<label>
<input type="radio" name="view-toggle" value="map"> Vista Mapa
</label>
</div>

<div id="restaurant-list">
<ul>
<?php foreach ($restaurants as $restaurant) : ?>
  <li>
  <a href="<?php echo get_permalink($restaurant); ?>">
  <?php echo esc_html($restaurant->post_title); ?>
  </a>
  </li>
  <?php endforeach; ?>
  </ul>
  </div>
  
  <div id="restaurant-map" style="height: 500px; display: none;"></div>
  
  <script>
  document.addEventListener("DOMContentLoaded", function () {
  var listView = document.getElementById("restaurant-list");
  var mapView = document.getElementById("restaurant-map");
  var radioButtons = document.querySelectorAll('input[name="view-toggle"]');
  
  radioButtons.forEach(function (radio) {
  radio.addEventListener("change", function () {
  if (this.value === "map") {
  listView.style.display = "none";
  mapView.style.display = "block";
  initMap();
  } else {
  listView.style.display = "block";
  mapView.style.display = "none";
  }
  });
  });
  
  function initMap() {
  if (window.map) return; // Evita reinicialitzar el mapa
  
  window.map = L.map('restaurant-map').setView([41.3851, 2.1734], 12);
  L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
  attribution: '&copy; OpenStreetMap contributors'
  }).addTo(map);
  
  var markers = [];
  
  <?php foreach ($restaurants as $restaurant) :
    $location = get_field('location', $restaurant->ID);
    if ($location && !empty($location['latitude']) && !empty($location['longitude'])) :
      $lat = esc_js($location['latitude']);
      $lng = esc_js($location['longitude']);
      $name = esc_js($restaurant->post_title);
      $url = esc_url(get_permalink($restaurant->ID));
      ?>
      markers.push(L.marker([<?php echo $lat; ?>, <?php echo $lng; ?>])
      .bindPopup('<a href="<?php echo $url; ?>"><?php echo $name; ?></a>')
      .addTo(map));
      <?php endif; endforeach; ?>
      }
      });
      </script>
      
      <?php return ob_get_clean();
      }
      
      add_shortcode("list_restaurants", "amc_list_restaurants");
      */