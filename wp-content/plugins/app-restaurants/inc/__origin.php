<?php
/**
* Plugin Name: AMC Core functions
* Description: Crear un campo de búsqueda de direcciones con OpenStreetMap en ACF.
* Version: 1.0
* Author: AMC
*/

// Evitem accés directe
if (!defined('ABSPATH')) exit;






define('AMC', plugin_dir_path(__FILE__));
// define('HCTA_AREA_JS', plugins_url('/js', __FILE__));
include_once AMC . '/inc/shortcodes.php';


// Inserim el JS només si la pàgina actual coincideix amb l’slug
// function hcta_enqueue_script() {

//     wp_enqueue_script('hcta', plugin_dir_url(__FILE__) . 'hcta.js', array(), null, true);
//     wp_localize_script( 'hcta', 'hcta_vars',
//         array( 
//             'svg'   => plugin_dir_url(__FILE__) . 'loader.svg', // Ruta de la imagen del loader
//             'time'  => get_option('hcta_number', 0), // Toma el valor numérico
//             'text'  => get_option('hcta_text', ''),   // Toma el valor de text
//             'reps' => get_option('hcta_radio', 'never') // Toma el valor del radio button
//         )
// 	);
//     wp_enqueue_style('loader-style',  plugin_dir_url(__FILE__) . 'hcta.css');
//     return;


//     if (is_admin()) return;

//     $slug = get_option('hcta_slug', '');
//     if (empty($slug)) return;

//     if (is_page(sanitize_title($slug))) {
//         wp_enqueue_script('hcta', plugin_dir_url(__FILE__) . 'hcta.js', array(), null, true);
//     }


// }
// add_action('wp_enqueue_scripts', 'hcta_enqueue_script');


function amc_custom_acf_address_field( $field ) {
  ?>
  <input type="text" id="acf-address-search" placeholder="Cerca una adreça...">
  <input type="hidden" name="<?php echo esc_attr($field['name']); ?>[latitude]" id="acf-latitude" value="<?php echo esc_attr($field['value']['latitude'] ?? ''); ?>">
  <input type="hidden" name="<?php echo esc_attr($field['name']); ?>[longitude]" id="acf-longitude" value="<?php echo esc_attr($field['value']['longitude'] ?? ''); ?>">
  
  <script>
  document.addEventListener("DOMContentLoaded", function () {
    const addressInput = document.getElementById("acf-address-search");
    const latitudeInput = document.getElementById("acf-latitude");
    const longitudeInput = document.getElementById("acf-longitude");
    
    addressInput.addEventListener("input", function () {
      fetch(`https://nominatim.openstreetmap.org/search?format=json&q=${encodeURIComponent(this.value)}`)
      .then(response => response.json())
      .then(data => {
        if (data.length > 0) {
          const result = data[0];
          latitudeInput.value = result.lat;
          longitudeInput.value = result.lon;
        }
      });
    });
  });
  </script>
  <?php
}

function amc_register_acf_address_field() {
  acf_register_field_type(array(
    'name'    => 'address_search',
    'label'   => 'Cercador d’adreces',
    'render_field' => 'amc_custom_acf_address_field',
  ));
}

// add_action('acf/init', 'amc_register_acf_address_field');




function _amc_enqueue_acf_scripts() {
  ?>
  <script>
  document.addEventListener("DOMContentLoaded", function () {
    console.log('hola acf');
    const addressInput = document.querySelector('[name="acf[field_67d1c69fd08c2]"]'); // Canvia pel "field key" de ACF
    const latitudeInput = document.querySelector('[name="acf[field_67d1c6bdd08c3]"]'); // Canvia pel "field key" correcte
    const longitudeInput = document.querySelector('[name="acf[field_67d1c6dad08c4]"]'); // Canvia pel "field key" correcte
    
    console.log(addressInput, latitudeInput, longitudeInput);
    
    if (addressInput) {
      addressInput.addEventListener("blur", function () {
        if (this.value.length > 3) { // Evita crides innecessàries
          fetch(`https://nominatim.openstreetmap.org/search?format=json&q=${encodeURIComponent(this.value)}`)
          .then(response => response.json())
          .then(data => {
            if (data.length > 0) {
              const result = data[0];
              latitudeInput.value = result.lat;
              longitudeInput.value = result.lon;
              console.log("Latitud:", result.lat, "Longitud:", result.lon);
            } else {
              console.warn("No s'han trobat resultats.");
            }
          })
          .catch(error => console.error("Error a l'API de Nominatim:", error));
        }
      });
    }
  });
  </script>
  <?php
}
// add_action('acf/input/admin_footer', '_amc_enqueue_acf_scripts');

function amc_enqueue_acf_scripts() {
  ?>
  <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
  <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
  
  <script>
  document.addEventListener("DOMContentLoaded", function () {
    console.log('ACF script carregat');
    
    const addressInput = document.querySelector('[name="acf[field_67d1c69fd08c2]"]'); // Camp d'adreça
    const latitudeInput = document.querySelector('[name="acf[field_67d1c6bdd08c3]"]'); // Latitud
    const longitudeInput = document.querySelector('[name="acf[field_67d1c6dad08c4]"]'); // Longitud
    
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
    
  });
  </script>
  <?php
}
add_action('acf/input/admin_footer', 'amc_enqueue_acf_scripts');
