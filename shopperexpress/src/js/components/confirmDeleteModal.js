// Confirm Delete Modal init
export default function initConfirmDeleteModal() {
  const $doc = jQuery(document);
  const $body = jQuery('body');
  const $editModal = jQuery('#editModal');
  const $confirmModal = jQuery('#confirmDelete');

  let savedBodyPaddingRight = null;
  let savedScrollbarWidth = null;

  function getComputedBodyPR() {
    return window.getComputedStyle(document.body).paddingRight || '';
  }

  function measureScrollbarWidth() {
    return window.innerWidth - document.documentElement.clientWidth;
  }

  function computeSafePaddingRight() {
    if (savedBodyPaddingRight && savedBodyPaddingRight !== '0px') {
      return savedBodyPaddingRight;
    }

    const sw = savedScrollbarWidth != null ? savedScrollbarWidth : measureScrollbarWidth();

    return sw > 0 ? sw + 'px' : '';
  }

  function lockBodyIfEditOpen() {
    if ($editModal.hasClass('show')) {
      $body.addClass('modal-open').css('padding-right', computeSafePaddingRight());

      return true;
    }

    return false;
  }

  function lockBodyDeferred() {
    lockBodyIfEditOpen();
    setTimeout(lockBodyIfEditOpen, 0);

    if (window.requestAnimationFrame) {
      requestAnimationFrame(lockBodyIfEditOpen);
    }
  }

  $confirmModal.on('show.bs.modal', function () {
    setTimeout(function () {
      jQuery('.modal-backdrop').last().addClass('confirm-delete-backdrop');
    }, 10);
  });

  $doc.on('show.bs.modal', '.modal', function () {
    if (jQuery('.modal.show').length === 0) {
      savedScrollbarWidth = measureScrollbarWidth();
    }
  });

  $doc.on('shown.bs.modal', '.modal', function () {
    if (jQuery('.modal.show').length === 1) {
      savedBodyPaddingRight = getComputedBodyPR();
    }
  });

  $confirmModal.on('hide.bs.modal', function () {
    if ($editModal.hasClass('show')) {
      savedBodyPaddingRight = getComputedBodyPR();
      lockBodyDeferred();
    }
  });

  $confirmModal.on('hidden.bs.modal', function () {
    if ($editModal.hasClass('show')) {
      lockBodyDeferred();
    }
  });

  $confirmModal
    .find('#confirmYes')
    .off('click.initConfirmDelete')
    .on('click.initConfirmDelete', function () {
      let remaining = 2;

      function afterHidden() {
        remaining--;
        if (remaining === 0) {
          $body.removeClass('modal-open').css('padding-right', '');
          savedBodyPaddingRight = null;
          savedScrollbarWidth = null;
        }
      }

      savedBodyPaddingRight = getComputedBodyPR();
      lockBodyDeferred();

      $confirmModal.one('hidden.bs.modal', afterHidden);
      $editModal.one('hidden.bs.modal', afterHidden);

      $confirmModal.modal('hide');
      $editModal.modal('hide');
    });

  $doc.on('show.bs.modal.stacked', '.modal', function () {
    const z = 1040 + 10 * jQuery('.modal.show').length;

    jQuery(this).css('z-index', z);

    setTimeout(function () {
      jQuery('.modal-backdrop')
        .not('.modal-stack')
        .css('z-index', z - 1)
        .addClass('modal-stack');
    }, 0);
  });

  $doc.on('hidden.bs.modal', '.modal', function () {
    setTimeout(function () {
      if (jQuery('.modal.show').length) {
        savedBodyPaddingRight = getComputedBodyPR() || savedBodyPaddingRight;
        lockBodyDeferred();
      } else {
        $body.removeClass('modal-open').css('padding-right', '');
        savedBodyPaddingRight = null;
        savedScrollbarWidth = null;
      }
    }, 0);
  });
}
