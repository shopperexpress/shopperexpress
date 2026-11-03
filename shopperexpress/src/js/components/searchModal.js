import SearchModal from '../plugins/searchModalPlugin';

// Search Modal init
export default function initSearchModal() {
  document.querySelectorAll('.toggle-search-modal').forEach((holder) => {
    new SearchModal(holder);
  });
}
