document.addEventListener('DOMContentLoaded', () => {


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

    results.innerHTML = '<p>Carregant...</p>';

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




});