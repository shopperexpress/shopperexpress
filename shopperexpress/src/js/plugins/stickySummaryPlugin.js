/*
 * Sticky Summary module
 */
export default class StickySummary {
  constructor(holder, options) {
    this.options = {
      fixedClass: 'fixed-block',
      cloneBlockClass: 'sticky-clone',
      stickyBlock: '.sticky-summary',
      triggerBlock: '.info-block--top',
      ...options,
    };

    if (holder) {
      this.holder = holder;

      this.init();
    }
  }

  init() {
    this.header = document.querySelector('#header');
    this.stickyBlock = this.holder.querySelector(this.options.stickyBlock);
    this.triggerBlock = this.holder.querySelector(this.options.triggerBlock);

    if (!this.stickyBlock || !this.triggerBlock) return;

    this.cloneBlock = null;
    this.isFixed = false;
    this.isDisabled = false;

    this.attachEvents();
  }

  attachEvents() {
    this.handleResize = this.resizeHandler.bind(this);
    this.handleScroll = this.scrollHandler.bind(this);

    this.resizeHandler();

    window.addEventListener('resize', this.debounce(this.handleResize));
    window.addEventListener('scroll', this.handleScroll);
  }

  createCloneBlock() {
    if (this.cloneBlock) return;

    this.cloneBlock = document.createElement('div');
    this.cloneBlock.classList.add(this.options.cloneBlockClass);
    this.stickyBlock.parentNode.insertBefore(this.cloneBlock, this.stickyBlock);

    this.cloneBlock.style.height = this.stickyBlock.offsetHeight + 'px';
    this.stickyBlock.style.width = this.cloneBlock.offsetWidth + 'px';
  }

  removeCloneBlock() {
    if (this.cloneBlock) {
      this.cloneBlock.parentNode.removeChild(this.cloneBlock);
      this.cloneBlock = null;
    }
  }

  scrollHandler() {
    if (this.isDisabled) {
      this.removeCloneBlock();
      this.holder.classList.remove(this.options.fixedClass);

      this.isFixed = false;

      return;
    }

    const scrollY = window.scrollY;

    if (
      this.triggerBlockOffset + this.triggerBlockHeight <= scrollY + this.headerHeight &&
      scrollY + this.windowHeight < this.holderOffset + this.holderHeight - this.stickyBlock.offsetHeight
    ) {
      if (!this.isFixed) {
        this.createCloneBlock();
        this.holder.classList.add(this.options.fixedClass);

        this.isFixed = true;
      }
    } else {
      if (this.isFixed) {
        this.removeCloneBlock();
        this.holder.classList.remove(this.options.fixedClass);

        this.isFixed = false;
      }
    }
  }

  resizeHandler() {
    this.triggerBlockOffset = this.triggerBlock.getBoundingClientRect().top + window.scrollY;
    this.triggerBlockHeight = this.triggerBlock.offsetHeight;
    this.holderOffset = this.holder.getBoundingClientRect().top + window.scrollY;
    this.holderHeight = this.holder.offsetHeight;
    this.headerHeight = this.header.offsetHeight || 0;
    this.windowHeight = window.innerHeight;

    if (this.cloneBlock) {
      this.cloneBlock.style.height = this.stickyBlock.offsetHeight + 'px';
      this.stickyBlock.style.width = this.cloneBlock.offsetWidth + 'px';
    }

    if (this.stickyBlock.offsetHeight > this.windowHeight - this.headerHeight - 200) {
      this.isDisabled = true;
    } else {
      this.isDisabled = false;
    }
    this.scrollHandler();
  }

  debounce(func, delay = 250) {
    let timer = null;

    return (...args) => {
      clearTimeout(timer);
      timer = setTimeout(() => func.apply(this, args), delay);
    };
  }
}
