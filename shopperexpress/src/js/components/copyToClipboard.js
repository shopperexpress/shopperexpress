import ClipboardJS from 'clipboard';

// copy to clipboard init
export default function initCopyToClipboard() {
  // fix bug with negative tabindex in modal
  jQuery.fn.modal.Constructor.prototype._enforceFocus = function () {};

  document.querySelectorAll('[data-copied]').forEach((btn) => {
    const textElem = btn.querySelector('.btn-text');

    // Set url to clipboard text if not set
    if (!btn.dataset.clipboardText) {
      btn.setAttribute('data-clipboard-text', window.location.href);
    }

    const clipboard = new ClipboardJS(btn);

    if (!textElem) return;

    const defaultText = textElem.textContent;

    btn.addEventListener('click', (e) => {
      e.preventDefault();
    });

    clipboard.on('success', function () {
      const copiedText = btn.dataset.copied || 'Copied';

      textElem.textContent = copiedText;

      setTimeout(() => {
        textElem.textContent = defaultText;
      }, 2000);
    });
  });
}
