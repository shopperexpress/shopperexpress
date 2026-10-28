/*
 * Chat module
 */
export default class Chat {
  constructor(holder, options) {
    this.options = {
      inputSelector: '.ai-chat__input',
      footerSelector: '.ai-chat__footer-holder',
      submitSelector: '.ai-chat__submit',
      focusClass: 'input-focus',
      activeClass: 'input-active',
      ...options,
    };

    if (!holder) return;

    this.holder = holder;

    this.init();
  }

  init() {
    this.inputField = this.holder.querySelector(this.options.inputSelector);
    this.footerBlock = this.holder.querySelector(this.options.footerSelector);
    this.submitButton = this.holder.querySelector(this.options.submitSelector);

    if (!this.inputField || !this.footerBlock) return;

    this.placeholderText = this.inputField.innerHTML;

    if (this.getInputValue() !== '') {
      this.footerBlock.classList.add(this.options.activeClass);
    }

    this.attachEvents();
  }

  attachEvents() {
    this.inputField.addEventListener('focus', () => {
      this.footerBlock.classList.add(this.options.focusClass);
    });

    this.inputField.addEventListener('input', () => {
      this.toggleActiveState();
    });

    this.inputField.addEventListener(
      'keydown',
      (e) => {
        this.handleEnterKey(e);
      },
      true
    );

    this.inputField.addEventListener('beforeinput', (e) => {
      this.handleBeforeInput(e);
    });

    this.inputField.addEventListener('blur', () => {
      this.footerBlock.classList.remove(this.options.focusClass);
      this.toggleActiveState();
    });
  }

  handleEnterKey(e) {
    const isEnter = e.key === 'Enter';

    if (!isEnter || e.shiftKey || e.isComposing) return;

    e.preventDefault();
    e.stopPropagation();
    this.submitMessage();
  }

  handleBeforeInput(e) {
    if (e.inputType !== 'insertParagraph') return;

    e.preventDefault();
  }

  submitMessage() {
    if (!this.submitButton) return;
    if (this.getInputValue() === '') return;

    this.submitButton.click();
    this.footerBlock.classList.remove(this.options.activeClass);
    this.inputField.blur();
  }

  getInputValue() {
    return this.inputField.textContent.replace(/\u00a0/g, ' ').trim();
  }

  toggleActiveState() {
    const hasValue = this.getInputValue() !== '';

    this.footerBlock.classList.toggle(this.options.activeClass, hasValue);

    if (!hasValue) {
      this.inputField.innerHTML = this.placeholderText;
    }
  }
}
