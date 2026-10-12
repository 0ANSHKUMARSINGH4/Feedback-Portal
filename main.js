/**
 * SNTI Feedback Portal Client Interactions
 */
document.addEventListener('DOMContentLoaded', function () {
  // Auto-dismiss alerts after 6 seconds if present
  const alerts = document.querySelectorAll('.alert');
  alerts.forEach(function (alert) {
    setTimeout(function () {
      alert.style.transition = 'opacity 0.4s ease, transform 0.4s ease';
      alert.style.opacity = '0';
      alert.style.transform = 'translateY(-4px)';
      setTimeout(function () {
        if (alert.parentNode) {
          alert.parentNode.removeChild(alert);
        }
      }, 400);
    }, 6000);
  });
});
