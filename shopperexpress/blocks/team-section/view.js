document.addEventListener('DOMContentLoaded', () => {
  initTeamFilter();
  initHoverClass();
});

function initTeamFilter() {
  jQuery('.team-section').filteringItems({
    items: '.card-team',
    filterHolder: '.team-filters',
    filterItems: 'button[data-filter]',
  });
}

function initHoverClass() {
  const activeClass = 'custom-hover';
  const holders = document.querySelectorAll('.card-team');

  if (!holders.length) return;

  function setActiveHolder(activeHolder) {
    holders.forEach((holder) => {
      holder.classList.toggle(activeClass, holder === activeHolder);
    });
  }

  holders.forEach((holder) => {
    holder.addEventListener('mouseenter', () => {
      setActiveHolder(holder);
    });

    holder.addEventListener('mouseleave', () => {
      if (!holder.contains(document.activeElement)) {
        holder.classList.remove(activeClass);
      }
    });

    holder.addEventListener('focusin', () => {
      setActiveHolder(holder);
    });

    holder.addEventListener('focusout', (e) => {
      if (!holder.contains(e.relatedTarget) && !holder.matches(':hover')) {
        holder.classList.remove(activeClass);
      }
    });
  });

  document.addEventListener('click', (e) => {
    setActiveHolder(e.target.closest('.card-team'));
  });
}
