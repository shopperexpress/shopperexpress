import './customMapPlugin';

document.addEventListener('DOMContentLoaded', async () => {
  if (!document.querySelector('.map-holder')) return;

  if (await mapsReady()) initCustomMap();
});

function mapsReady(attempts = 100) {
  return new Promise((resolve) => {
    const check = async () => {
      const maps = window.google?.maps;

      try {
        if (maps?.importLibrary) {
          await Promise.all([maps.importLibrary('maps'), maps.importLibrary('marker')]);
        }
      } catch {
        return resolve(false);
      }

      if (typeof window.google?.maps?.Map === 'function') return resolve(true);

      if (--attempts > 0) return setTimeout(check, 100);

      resolve(false);
    };

    check();
  });
}

function initCustomMap() {
  jQuery('.map-holder').each(function () {
    const holder = jQuery(this);
    const zoom = holder.data('zoom') || 16;

    holder.customMap({
      zoom,
      coordinates: 'data-coordinates',
      stylesAttr: 'data-styles',
    });
  });
}
