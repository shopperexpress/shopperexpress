import 'jquery-mask-plugin';

export default function initInputMask() {
  jQuery('.phone').mask('(000)000-0000');
  jQuery('.zip-code').mask('00000');
}
