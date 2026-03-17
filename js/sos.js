'use strict';
document.addEventListener('DOMContentLoaded', () => {
  const locBtn = document.getElementById('sos-locate-btn');
  const locField = document.getElementById('sos-location');
  if (!locBtn || !locField) return;
  locBtn.addEventListener('click', () => {
    if (!navigator.geolocation) { locField.placeholder = 'Not supported — type location'; return; }
    locBtn.textContent = '⏳…'; locBtn.disabled = true;
    navigator.geolocation.getCurrentPosition(
      pos => { locField.value = 'Lat: ' + pos.coords.latitude.toFixed(5) + ', Lng: ' + pos.coords.longitude.toFixed(5); locBtn.textContent = '✓ Set'; },
      () => { locBtn.textContent = '📍 Retry'; locBtn.disabled = false; }
    );
  });
});
