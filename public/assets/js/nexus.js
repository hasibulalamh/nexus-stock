(function () {
  // show skeleton rows while a filter/search request is in flight
  function setLoading() {
    var card = document.querySelector('[data-loading-target]');
    if (card) card.classList.add('is-loading');
  }
  window.nxSubmit = function (form) { setLoading(); form.submit(); };

  // delete confirmation via SweetAlert2 (falls back to confirm())
  document.addEventListener('submit', function (e) {
    var f = e.target;
    if (f.matches && f.matches('[data-confirm]') && !f.dataset.confirmed) {
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
    } else if (f.hasAttribute && f.hasAttribute('data-filter')) {
      setLoading();
    }
  });

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
