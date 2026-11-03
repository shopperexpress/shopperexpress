import Cookies from 'js-cookie';

// Cookie modal init
export default function initCookieModal() {
  const activeStepClass = 'current-step';
  const hiddenClass = 'hidden-button';

  document.querySelectorAll('.modal[data-cookie-expire-days]').forEach((modal) => {
    const ID = modal.getAttribute('id');
    const cookieKey = ID + '-is-cashed';
    const isShow = modal.dataset.show === 'true';
    const cookieExpireDays = modal.dataset.cookieExpireDays !== undefined ? Number(modal.dataset.cookieExpireDays) : 7;
    const steps = modal.querySelectorAll('.modal-steps .step');
    const btnNextStep = modal.querySelector('.btn-next');
    const modalContent = modal.querySelector('.modal-content');
    const totalSteps = steps.length;
    let currentStep = 0;

    btnNextStep?.addEventListener('click', (e) => {
      e.preventDefault();
      goToNextStep();
    });

    if (!Cookies.get(cookieKey) && isShow) {
      setTimeout(() => {
        showPopup();
      }, 1000);
    }

    // Set cookie on close modal
    jQuery(modal).on('hidden.bs.modal', () => {
      setCookie();
    });

    function goToNextStep() {
      currentStep++;

      if (currentStep > totalSteps - 1) {
        currentStep = totalSteps - 1;
      }

      if (currentStep === totalSteps - 1) {
        btnNextStep.classList.add(hiddenClass);
        modalContent?.classList.add('last-step');
      }

      steps.forEach((step, i) => {
        step.classList.remove(activeStepClass);

        if (i === currentStep) {
          step.classList.add(activeStepClass);
        }
      });
    }

    function showPopup() {
      jQuery(modal).modal('show');
    }

    function setCookie() {
      Cookies.set(cookieKey, 'true', {
        expires: cookieExpireDays,
        path: '/',
      });
    }
  });
}
