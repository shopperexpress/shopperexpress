import '../plugins/filteringItemsPlugin';

export default function initFiltering() {
  jQuery('.dropdown-models').filteringItems({
    items: '.slide, .drop-model-list > li',
    filterHolder: '.drop-model-slider',
    filterItems: 'a[data-filter]',
  });
}
