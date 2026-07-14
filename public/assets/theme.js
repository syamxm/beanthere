(function () {
  var root = document.documentElement;
  var accentInput = document.getElementById('accentColor');
  var accentDefault = document.getElementById('accentDefault');

  function hexToTriplet(hex) {
    return parseInt(hex.slice(1, 3), 16) + ' ' +
      parseInt(hex.slice(3, 5), 16) + ' ' +
      parseInt(hex.slice(5, 7), 16);
  }

  document.querySelectorAll('[data-theme-option]').forEach(function (input) {
    input.addEventListener('change', function () {
      root.dataset.theme = input.value;
      if (accentDefault.checked) {
        accentInput.value = input.dataset.accent;
        root.style.removeProperty('--c-caramel');
      }
    });
  });

  accentInput.addEventListener('input', function () {
    accentDefault.checked = false;
    root.style.setProperty('--c-caramel', hexToTriplet(accentInput.value));
  });

  accentDefault.addEventListener('change', function () {
    if (accentDefault.checked) {
      var selected = document.querySelector('[data-theme-option]:checked');
      accentInput.value = selected.dataset.accent;
      root.style.removeProperty('--c-caramel');
    }
  });
})();
