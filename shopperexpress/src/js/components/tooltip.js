export default function initTooltip() {
  if (jQuery('[data-toggle="tooltip"]').length && bootstrap.Tooltip) {
    jQuery('[data-toggle="tooltip"]').tooltip();
  }
}
