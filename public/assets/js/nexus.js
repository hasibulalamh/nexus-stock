(function () {
  // show skeleton rows while a filter/search request is in flight
  function setLoading() {
    var card = document.querySelector('[data-loading-target]');
    if (card) card.classList.add('is-loading');
  }
  window.nxSubmit = function (form) { setLoading(); form.submit(); };

  // ----- submit-button loading state -----
  // The button is disabled on the next tick so the browser has already
  // serialised the form (a submit button's own name/value still gets sent),
  // and every marked button is released again on pageshow so going back with
  // the browser history never leaves it stuck spinning.
  var loading = [];

  function markLoading(button) {
    if (!button || button.classList.contains('is-loading')) return;
    button.classList.add('is-loading');
    loading.push(button);
    setTimeout(function () { button.disabled = true; }, 0);
  }

  function clearLoading() {
    loading.forEach(function (button) {
      button.classList.remove('is-loading');
      button.disabled = false;
    });
    loading = [];
  }

  // delete confirmation via SweetAlert2 (falls back to confirm())
  document.addEventListener('submit', function (e) {
    var f = e.target;
    if (!f || !f.matches) return;

    if (f.matches('[data-confirm]') && !f.dataset.confirmed) {
      e.preventDefault();
      if (!window.Swal) {
        if (window.confirm(f.dataset.confirm)) { f.dataset.confirmed = '1'; f.submit(); }
        return;
      }
      Swal.fire({
        title: f.dataset.confirmTitle || 'Are you sure?',
        text: f.dataset.confirm,
        icon: 'warning',
        showCancelButton: true,
        confirmButtonText: f.dataset.confirmButton || 'Delete',
        cancelButtonText: 'Cancel',
        reverseButtons: true,
        focusCancel: true,
        buttonsStyling: false,
        customClass: { popup: 'nx-swal', confirmButton: 'nx-swal-danger', cancelButton: 'nx-swal-cancel' }
      }).then(function (r) {
        if (r.isConfirmed) { f.dataset.confirmed = '1'; f.submit(); }
      });
      return;
    }

    if (f.hasAttribute('data-filter')) setLoading();

    // a handler above may have cancelled the submit — then nothing is flying
    if (e.defaultPrevented) return;

    var button = e.submitter && e.submitter.type === 'submit'
      ? e.submitter
      : f.querySelector('button[type="submit"], input[type="submit"]');
    markLoading(button);
  });

  window.addEventListener('pageshow', clearLoading);

  // toast helper: nxToast('success' | 'error' | 'info', 'message')
  window.nxToast = function (type, msg) {
    if (!window.Swal) return;
    Swal.fire({
      toast: true, position: 'top-end', icon: type, title: msg,
      showConfirmButton: false, timer: 3500, timerProgressBar: true,
      customClass: { popup: 'nx-swal-toast' }
    });
  };
})();
