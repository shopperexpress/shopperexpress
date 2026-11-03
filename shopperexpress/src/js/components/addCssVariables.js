// Add css variables
export default function initAddCssVariables() {
  const header = document.querySelector('#header');

  if (!header) return;

  setCssVariable();
  window.addEventListener('load', setCssVariable);
  window.addEventListener('resize', setCssVariable);

  function setCssVariable() {
    header.style.setProperty('--header-height', `${header.offsetHeight}px`);
  }
}
