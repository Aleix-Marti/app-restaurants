document.addEventListener('DOMContentLoaded', () => {
  let userPosition = null; // Guardar la posició de l'usuari

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

  const form = document.getElementById('restaurant-filter-form');
  const results = document.getElementById('restaurant-list');

  // Obtenir la geolocalització una sola vegada al carregar la pàgina
  function initGeolocation() {
    if (navigator.geolocation) {
      navigator.geolocation.getCurrentPosition(
        (position) => {
          userPosition = {
            lat: position.coords.latitude,
            lon: position.coords.longitude
          };
          console.log('Ubicació obtinguda:', userPosition);
          // Calcular distàncies si ja hi ha restaurants carregats
          if (document.querySelectorAll("#restaurant-list .restaurant-item").length > 0) {
            calcularDistancies();
          }
        },
        (error) => {
          console.warn('Error obtenint geolocalització:', error.message);
          userPosition = null;
        },
        {
          enableHighAccuracy: true,
          timeout: 10000,
          maximumAge: 300000 // Cache per 5 minuts
        }
      );
    }
  }

  function getFormData() {
    const formData = new FormData(form);
    const params = new URLSearchParams();

    formData.forEach((value, key) => {
      params.append(key, value);
    });

    return params.toString();
  }

  function fetchRestaurants() {
    const query = getFormData();

    results.innerHTML = '<div class="loading"><span class="spinner"></span><p>Carregant...</p></div>';

    fetch(`${ajax_object.ajaxurl}?action=filter_restaurants&${query}`)
      .then(response => response.text())
      .then(html => {
        results.innerHTML = html;
        return results;
      })
      .then(() => {
        // Calcular distàncies només si tenim la ubicació de l'usuari
        if (userPosition) {
          calcularDistancies();
        }
      })
      .catch(error => {
        results.innerHTML = '<p>Error carregant els restaurants.</p>';
        console.error(error);
      });
  }

  form.addEventListener('change', (e) => {
    if (e.target.matches('input[type="checkbox"]')) {
      e.target.closest('label').classList.toggle('active');
      fetchRestaurants();
      initMap();
    }
  });

  function initMap() {
    var restaurants = document.getElementById("restaurant-list").querySelectorAll(".restaurant-item");

    // Esborrar mapa anterior si existeix
    if (window.map) {
      window.map.remove();
    }
    
    window.map = L.map('restaurant-map').setView([41.11786753180424, 1.2479558412472744], 12);
    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
      attribution: '&copy; OpenStreetMap contributors'
    }).addTo(map);
    
    var markers = [];
    restaurants.forEach(function (restaurant) {
      var lat = parseFloat(restaurant.dataset.lat);
      var lng = parseFloat(restaurant.dataset.long);
      var name = restaurant.querySelector(".restaurant-name").innerText;
      var url = restaurant.querySelector("a").href;

      if (!isNaN(lat) && !isNaN(lng)) {
        markers.push(L.marker([lat, lng])
          .bindPopup('<a href="' + url + '">' + name + '</a>')
          .addTo(map));
      }
    });

    // Centrar mapa en la ubicació de l'usuari si està disponible
    if (userPosition) {
      map.setView([userPosition.lat, userPosition.lon], 12);
      // Afegir marcador de l'usuari
      L.marker([userPosition.lat, userPosition.lon], {
        icon: L.icon({
          iconUrl: 'https://raw.githubusercontent.com/pointhi/leaflet-color-markers/master/img/marker-icon-red.png',
          shadowUrl: 'https://cdnjs.cloudflare.com/ajax/libs/leaflet/0.7.7/images/marker-shadow.png',
          iconSize: [25, 41],
          iconAnchor: [12, 41],
          popupAnchor: [1, -34],
          shadowSize: [41, 41]
        })
      }).bindPopup('La teva ubicació').addTo(map);
    }
  }

  // Funció per calcular distància entre dues coordenades (Haversine)
  function calcularDistancia(lat1, lon1, lat2, lon2) {
    const R = 6371; // radi de la Terra en km
    const dLat = (lat2 - lat1) * Math.PI / 180;
    const dLon = (lon2 - lon1) * Math.PI / 180;
    const a = 
      Math.sin(dLat/2) * Math.sin(dLat/2) +
      Math.cos(lat1 * Math.PI / 180) * Math.cos(lat2 * Math.PI / 180) *
      Math.sin(dLon/2) * Math.sin(dLon/2);
    const c = 2 * Math.atan2(Math.sqrt(a), Math.sqrt(1-a));
    return R * c; // km
  }

  function calcularDistancies() {
    if (!userPosition) {
      console.log('Ubicació no disponible');
      return;
    }

    console.log('Calculant distàncies...');

    // Buscar tots els restaurants dins #restaurant-list
    const restaurants = document.querySelectorAll("#restaurant-list .restaurant-item");
    const restaurantsWithDistance = [];

    restaurants.forEach(item => {
      const restLat = parseFloat(item.dataset.lat);
      const restLon = parseFloat(item.dataset.long);
      
      if (!isNaN(restLat) && !isNaN(restLon)) {
        const dist = calcularDistancia(userPosition.lat, userPosition.lon, restLat, restLon);
        
        // Afegir la distància dins del <p class="restaurant-distance">
        const distElem = item.querySelector(".restaurant-distance");
        if (distElem) {
          distElem.textContent = dist.toFixed(1) + " km";
        }

        // Guardar per ordenar
        restaurantsWithDistance.push({
          element: item,
          distance: dist
        });
      }
    });

    // Ordenar restaurants per distància (opcional)
    orderRestaurantsByDistance(restaurantsWithDistance);
  }

  // Funció per ordenar els restaurants per distància
  function orderRestaurantsByDistance(restaurantsWithDistance) {
    // Ordenar per distància
    restaurantsWithDistance.sort((a, b) => a.distance - b.distance);
    
    const container = document.getElementById('restaurant-list');
    
    // Reordenar elements al DOM
    restaurantsWithDistance.forEach(item => {
      container.appendChild(item.element);
    });
  }

  // Inicialitzar geolocalització al carregar la pàgina
  initGeolocation();
  
  // Carrega inicial
  fetchRestaurants();
});