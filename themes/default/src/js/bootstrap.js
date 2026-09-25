/**
 * Public theme bootstrap dependencies.
 *
 * Bootstrap 5 does not require jQuery. jQuery remains available because
 * existing JohnCMS modules use it for AJAX and legacy UI integrations.
 */

try {
  window.$ = window.jQuery = require('jquery');
  window.axios = require('axios');
  window.axios.defaults.headers.common['X-Requested-With'] = 'XMLHttpRequest';
  require('bootstrap');
} catch (e) {
  // Keep the existing application boot process tolerant of optional frontend dependencies.
}
