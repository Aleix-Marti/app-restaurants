document.addEventListener('DOMContentLoaded', () => {


  var listView = document.getElementById("restaurant-list");
  var mapView = document.getElementById("restaurant-map");
  var viewToggleBtns = document.querySelectorAll('.view-toggle__btn');

  viewToggleBtns.forEach(function (btn) {
    btn.addEventListener("click", function () {
      var view = this.dataset.view;
      viewToggleBtns.forEach(function (b) {
        b.classList.remove('view-toggle__btn--active');
        b.setAttribute('aria-pressed', b === btn ? 'true' : 'false');
      });
      this.classList.add('view-toggle__btn--active');
      if (view === "map") {
        listView.style.display = "none";
        listView.setAttribute('aria-hidden', 'true');
        listView.classList.remove('directori-list--grid');
        mapView.style.display = "block";
        mapView.setAttribute('aria-hidden', 'false');
        mapView.classList.add('active');
        initMap();
      } else {
        listView.style.display = "grid";
        listView.setAttribute('aria-hidden', 'false');
        mapView.style.display = "none";
        mapView.setAttribute('aria-hidden', 'true');
        mapView.classList.remove('active');
        if (view === "grid") {
          listView.classList.add('directori-list--grid');
        } else {
          listView.classList.remove('directori-list--grid');
        }
      }
    });
  });

  var pillTots = document.querySelector('.filter-pill[data-filter="all"]');
  var filterForm = document.getElementById('restaurant-filter-form');
  if (pillTots && filterForm) {
    pillTots.addEventListener('click', function () {
      filterForm.querySelectorAll('input[name="category[]"]').forEach(function (cb) {
        cb.checked = false;
      });
      document.querySelectorAll('.filter-pill:not([data-filter="all"])').forEach(function (pill) {
        pill.classList.remove('filter-pill--active');
      });
      pillTots.classList.add('filter-pill--active');
      pillTots.setAttribute('aria-pressed', 'true');
      fetchRestaurants();
    });
  }
  




  const form = document.getElementById('restaurant-filter-form');
  const results = document.getElementById('restaurant-list');
  const randomBtn = document.getElementById('random-restaurant-btn');

  function getFormData() {
    const formData = new FormData(form);
    const params = new URLSearchParams();

    formData.forEach((value, key) => {
      params.append(key, value);
    });

    return params.toString();
  }

  function fetchRestaurants() {

    // console.log('fetchRestaurants');
    // console.log(ajax_object.ajaxurl);
    // console.log(ajax_object.action);

    const query = getFormData();

    results.innerHTML = '<div class="loading"><span class="spinner"></span><p>Carregant...</p></div>';

    fetch(`${ajax_object.ajaxurl}?action=filter_restaurants&${query}`)
    // const formData = new FormData(form);
    // formData.append('action', ajax_object.action);

    // fetch(ajax_object.ajaxurl, {
    //   method: 'POST',
    //   headers: {
    //     'Content-Type': 'application/x-www-form-urlencoded'
    //   },
    //   body: new URLSearchParams(formData)
    // })
    .then(response => response.text())
    .then(html => {
      results.innerHTML = html;
      return results;
    })
    .then(html => {
      calcularDistancies();
    })
    .catch(error => {
      results.innerHTML = '<p>Error carregant els restaurants.</p>';
      console.error(error);
    });
  }

  form.addEventListener('change', (e) => {
    if (e.target.matches('input[name="category[]"]')) {
      var label = e.target.closest('label.filter-pill');
      if (label) {
        label.classList.toggle('filter-pill--active', e.target.checked);
        label.setAttribute('aria-pressed', e.target.checked ? 'true' : 'false');
      }
      if (pillTots) {
        pillTots.classList.remove('filter-pill--active');
        pillTots.setAttribute('aria-pressed', 'false');
      }
      fetchRestaurants();
      initMap();
    }
  });

  // Funcionalitat del botó random
  randomBtn.addEventListener('click', () => {
    selectRandomRestaurant();
  });

  function selectRandomRestaurant() {
    const restaurants = document.querySelectorAll('#restaurant-list .restaurant-item');
    
    if (restaurants.length === 0) {
      alert('No hi ha restaurants disponibles per seleccionar.');
      return;
    }

    // Crear efecte de "rodant"
    randomBtn.innerHTML = '🎲 Seleccionant...';
    randomBtn.disabled = true;

    // Efecte visual: destacar restaurants aleatoriament durant un temps
    let counter = 0;
    const maxCounter = 15; // Nombre d'iteracions
    
    const interval = setInterval(() => {
      // Eliminar destacat anterior
      restaurants.forEach(restaurant => {
        restaurant.classList.remove('random-highlight');
      });

      // Destacar restaurant aleatori
      const randomIndex = Math.floor(Math.random() * restaurants.length);
      restaurants[randomIndex].classList.add('random-highlight');

      counter++;
      
      if (counter >= maxCounter) {
        clearInterval(interval);
        
        // Selecció final
        finalSelection(restaurants);
      }
    }, 100);
  }

  function finalSelection(restaurants) {
    // Eliminar tots els destacats temporals
    restaurants.forEach(restaurant => {
      restaurant.classList.remove('random-highlight');
    });

    // Seleccionar restaurant final
    const finalIndex = Math.floor(Math.random() * restaurants.length);
    const selectedRestaurant = restaurants[finalIndex];
    
    // Destacar restaurant seleccionat
    selectedRestaurant.classList.add('random-selected');
    
    // Scroll fins al restaurant seleccionat
    selectedRestaurant.scrollIntoView({ 
      behavior: 'smooth', 
      block: 'center' 
    });

    // Restaurar botó
    randomBtn.innerHTML = '🎲 Restaurant Aleatori';
    randomBtn.disabled = false;

    // Crear popup o destacat més visible
    showRandomResult(selectedRestaurant);
  }

  function showRandomResult(restaurant) {
    const restaurantName = restaurant.querySelector('.restaurant-name').textContent;
    const restaurantLink = restaurant.querySelector('.restaurant-link');
    
    // Crear overlay amb el resultat
    const overlay = document.createElement('div');
    overlay.classList.add('random-result-overlay');
    overlay.innerHTML = `
      <div class="random-result-content">
        <h3>🎉 Restaurant seleccionat!</h3>
        <h2>${restaurantName}</h2>
        <div class="random-result-buttons">
          <button onclick="this.closest('.random-result-overlay').remove()" class="btn-close">
            Tancar
          </button>
          <a href="${restaurantLink.href}" class="btn-details">
            Veure Detalls
          </a>
        </div>
      </div>
    `;
    
    document.body.appendChild(overlay);
    
    // Tancar overlay en fer clic fora
    overlay.addEventListener('click', (e) => {
      if (e.target === overlay) {
        overlay.remove();
      }
    });

    // Eliminar destacat després de 5 segons
    setTimeout(() => {
      restaurant.classList.remove('random-selected');
    }, 5000);
  }


  function initMap() {
    // if (window.map) return; // Evita reinicialitzar el mapa

    var restaurants = document.getElementById("restaurant-list").querySelectorAll(".restaurant-item");

    
    window.map = L.map('restaurant-map').setView([41.11786753180424, 1.2479558412472744], 12);
    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
      attribution: '&copy; OpenStreetMap contributors'
    }).addTo(map);
    
    var markers = [];
    restaurants.forEach(function (restaurant) {
      // var location = restaurant.querySelector(".restaurant-location");
      // if (location) {
      var lat = parseFloat(restaurant.dataset.lat);
      var lng = parseFloat(restaurant.dataset.long);
      var name = restaurant.querySelector(".restaurant-name").innerText;
      var url = restaurant.querySelector("a").href;

      if (!isNaN(lat) && !isNaN(lng)) {
        markers.push(L.marker([lat, lng])
          .bindPopup('<a href="' + url + '">' + name + '</a>')
          .addTo(map));
        }
      // }
    });
  }

  // Carrega inicial
  fetchRestaurants();




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
  console.log('calcularDistancies');

// Obtenir ubicació actual de l'usuari
  if (navigator.geolocation) {
    navigator.geolocation.getCurrentPosition((pos) => {
      const userLat = pos.coords.latitude;
      const userLon = pos.coords.longitude;

      // Buscar tots els restaurants dins #restaurant-list
      document.querySelectorAll("#restaurant-list .restaurant-item").forEach(item => {
        const restLat = parseFloat(item.dataset.lat);
        const restLon = parseFloat(item.dataset.long);
        const dist = calcularDistancia(userLat, userLon, restLat, restLon);

        // Afegir la distància dins del <p class="restaurant-distance">
        const distElem = item.querySelector(".restaurant-distance");
        if (distElem) {
          distElem.textContent = dist.toFixed(1) + " km";
          console.log(dist.toFixed(1));
        }
      });
    }, (err) => {
      console.error("Error de geolocalització:", err.message);
    });
  } else {
    console.log('activa geolocalització')
  }
}




});