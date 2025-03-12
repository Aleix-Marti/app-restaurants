<?php

/** Funcionalitats relacionades amb ACF */


/**
 * Sincronitzar mapa, coordenades i adreça a la pàgina d'edició del restaurant al backoffice ACF
 */
function amc_enqueue_acf_scripts() {
?>  
  <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
  <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />

  <script>
    console.log('hola acf');
  document.addEventListener("DOMContentLoaded", function () {
      console.log('ACF script carregat');

      const addressInput = document.querySelector('#acf-field_67880acf6022f-field_67c9e75b08871'); // Camp d'adreça
      const latitudeInput = document.querySelector('#acf-field_67880acf6022f-field_67880d9371f06'); // Latitud
      const longitudeInput = document.querySelector('#acf-field_67880acf6022f-field_67880da071f07'); // Longitud

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