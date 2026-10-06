// GPS + Google Maps for the "add address" form. Works in two tiers:
//  - No Google Maps key configured: "use my location" just fills the
//    hidden lat/lng fields from the browser's own Geolocation API --
//    no external service, no key, always available.
//  - A key configured (config/config.php -> maps.google_maps_key): adds
//    Places autocomplete on the address line, a draggable pin on a small
//    map, and reverse-geocoding so city/state/pincode fill themselves in.

(function () {
  var latField = document.getElementById('lat');
  var lngField = document.getElementById('lng');
  var statusEl = document.getElementById('location-status');
  var useLocationBtn = document.getElementById('use-location-btn');
  if (!useLocationBtn) return;

  var map = null;
  var marker = null;
  var geocoder = null;

  function setCoords(lat, lng) {
    if (latField) latField.value = lat;
    if (lngField) lngField.value = lng;
    if (statusEl) statusEl.textContent = 'Location captured: ' + lat.toFixed(5) + ', ' + lng.toFixed(5);
  }

  function fillAddressComponents(components) {
    var fieldMap = { locality: 'city', administrative_area_level_1: 'state', postal_code: 'pincode' };
    components.forEach(function (comp) {
      comp.types.forEach(function (type) {
        if (fieldMap[type]) {
          var field = document.getElementById(fieldMap[type]);
          if (field && !field.value) field.value = comp.long_name;
        }
      });
    });
  }

  useLocationBtn.addEventListener('click', function () {
    if (!navigator.geolocation) {
      if (statusEl) statusEl.textContent = 'Your browser does not support GPS location.';
      return;
    }
    if (statusEl) statusEl.textContent = 'Getting your location…';
    navigator.geolocation.getCurrentPosition(function (pos) {
      var lat = pos.coords.latitude, lng = pos.coords.longitude;
      setCoords(lat, lng);
      if (map && marker) {
        var point = new google.maps.LatLng(lat, lng);
        map.setCenter(point);
        map.setZoom(16);
        marker.setPosition(point);
        reverseGeocode(point);
      }
    }, function () {
      if (statusEl) statusEl.textContent = 'Could not get your location. Allow location access and try again.';
    });
  });

  function reverseGeocode(latLng) {
    if (!geocoder) return;
    geocoder.geocode({ location: latLng }, function (results, status) {
      if (status === 'OK' && results[0]) {
        fillAddressComponents(results[0].address_components);
      }
    });
  }

  // Called by the Google Maps script tag's callback=initAddressMap, only
  // when a maps key is configured and the script actually loaded.
  window.initAddressMap = function () {
    var mapEl = document.getElementById('address-map');
    if (!mapEl) return;

    var start = { lat: 20.5937, lng: 78.9629 }; // center of India, just a default
    map = new google.maps.Map(mapEl, { center: start, zoom: 5, streetViewControl: false, mapTypeControl: false });
    marker = new google.maps.Marker({ position: start, map: map, draggable: true });
    geocoder = new google.maps.Geocoder();

    marker.addListener('dragend', function () {
      var pos = marker.getPosition();
      setCoords(pos.lat(), pos.lng());
      reverseGeocode(pos);
    });

    var line1 = document.getElementById('line1');
    if (line1 && google.maps.places) {
      var autocomplete = new google.maps.places.Autocomplete(line1, { types: ['geocode'] });
      autocomplete.addListener('place_changed', function () {
        var place = autocomplete.getPlace();
        if (!place.geometry) return;
        var point = place.geometry.location;
        map.setCenter(point);
        map.setZoom(16);
        marker.setPosition(point);
        setCoords(point.lat(), point.lng());
        fillAddressComponents(place.address_components || []);
      });
    }
  };
})();
