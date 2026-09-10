// Vanilla JS, no bundler. Confirms destructive actions and auto-dismisses
// flash messages after a few seconds.

document.addEventListener('submit', function (event) {
  var form = event.target;
  if (form.hasAttribute('data-confirm')) {
    var message = form.getAttribute('data-confirm') || 'Are you sure?';
    if (!window.confirm(message)) {
      event.preventDefault();
    }
  }
});

document.addEventListener('DOMContentLoaded', function () {
  var alerts = document.querySelectorAll('.alert');
  alerts.forEach(function (alert) {
    setTimeout(function () {
      alert.style.transition = 'opacity 0.4s ease';
      alert.style.opacity = '0';
      setTimeout(function () { alert.remove(); }, 400);
    }, 4000);
  });
});
