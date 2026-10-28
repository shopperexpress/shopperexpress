export default function initOpenPdfInNewTab() {
  document.querySelectorAll('.details-badges').forEach((holder) => {
    const links = holder.querySelectorAll('a[data-pdf]');

    if (!links.length) return;

    links.forEach((link) => {
      link.addEventListener('click', (e) => {
        e.preventDefault();

        const gaLabel = link.getAttribute('data-ga-label');
        if (gaLabel && typeof gtag === 'function') {
          gtag('event', 'vdr_download', {
            event_category: 'VDR',
            event_label: gaLabel,
          });
        }

        const url = link.getAttribute('href');
        sendRequest(url, link);
      });
    });

    async function sendRequest(url, link) {
      if (!url) return;

      try {
        const response = await fetch(url, {
          method: 'GET',
        });

        if (!response.ok) {
          throw new Error(`HTTP ${response.status}`);
        }

        const data = await response.json();

        onSuccess(data, link);
      } catch (error) {
        console.error('Error:', error);
        showVdrError(link);
      }
    }

    function onSuccess(response, link) {
      if (response.success) {
        if (response.data.url) {
          window.open(response.data.url, '_blank');
        }
      } else {
        showVdrError(link);
      }
    }

    function showVdrError(link) {
      const li = link.closest('li');
      if (li) li.style.display = 'none';

      const errorMessage = link.getAttribute('data-error-message') || 'Sorry, we could not generate the vehicle report at this time. Please try again later.';

      let modal = document.getElementById('vdrErrorModal');
      if (!modal) {
        modal = document.createElement('div');
        modal.id = 'vdrErrorModal';
        modal.className = 'modal fade';
        modal.setAttribute('tabindex', '-1');
        modal.setAttribute('role', 'dialog');
        modal.setAttribute('aria-hidden', 'true');
        modal.innerHTML =
          '<div class="modal-dialog modal-dialog-centered" role="document">' +
          '<div class="modal-content">' +
          '<div class="modal-header">' +
          '<button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>' +
          '</div>' +
          '<div class="modal-body" id="vdrErrorModalBody"></div>' +
          '</div></div>';
        document.body.appendChild(modal);
      }

      document.getElementById('vdrErrorModalBody').textContent = errorMessage;

      if (typeof jQuery !== 'undefined' && jQuery.fn.modal) {
        jQuery('#vdrErrorModal').modal('show');
      }
    }
  });
}
