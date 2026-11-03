import '@fancyapps/fancybox';
import './responsiveHelperPlugin';

/*
 * SpinPopup module
 */
export default class SpinPopup {
  constructor(button, options) {
    this.options = {
      vinSelector: '.detail-info .vin',
      dealerImageApiUrl: 'https://photon360.dealerimagepro.com/v4/vdpdata',
      autoportUrl: 'https://dev-api.dealerimagepro.com/sandbox/assets',
      ...options,
    };

    if (!button) return;

    this.btnSpin = jQuery(button);
    this.detailSection = this.btnSpin.closest('.detail-section');
    this.vin = this.detailSection.find(this.options.vinSelector);
    this.isMobile = false;
    this.additionalClass = '';
    this.responsiveInitialized = false;

    this.init();
  }

  init() {
    this.initResponsive();
    this.initButton();
    this.bindEvents();
  }

  bindEvents() {
    this.btnSpin.fancybox({
      parentEl: 'body',
      margin: [50, 0],
      backFocus: false,
      iframe: {
        tpl: '<iframe id="fancybox-frame{rnd}" name="fancybox-frame{rnd}" class="fancybox-iframe" allow="autoplay; fullscreen" src=""></iframe>',
        preload: false,
      },
      beforeShow: () => {
        jQuery('.fancybox-container').addClass(`spin-360 ${this.additionalClass}`);
      },
      afterShow: function () {
        // Trigger window resize for update iframes
        setTimeout(() => {
          jQuery(window).trigger('resize');
        }, 200);
      },
    });
  }

  initResponsive() {
    if (this.responsiveInitialized) return;

    this.responsiveInitialized = true;

    ResponsiveHelper.addRange({
      '..767': {
        on: () => {
          this.isMobile = true;
        },
      },
      '768..': {
        on: () => {
          this.isMobile = false;
        },
      },
    });
  }

  initButton() {
    this.btnSpin.hide();

    if (this.btnSpin.hasClass('spin-evo')) {
      this.initEvo();
    }

    if (!this.vin.length) return;

    const fullVin = this.vin.text().trim();

    switch (true) {
      case this.btnSpin.hasClass('spin-impel'):
        this.initImpel(fullVin);
        break;
      case this.btnSpin.hasClass('spin-dealerimage'):
        this.initDealerImage(fullVin);
        break;
      case this.btnSpin.hasClass('spin-autoexact'):
        this.initAutoexact(fullVin);
        break;
      case this.btnSpin.hasClass('spin-lesa'):
        this.initLesa(fullVin);
        break;
      case this.btnSpin.hasClass('spin-video'):
        this.initVideo();
        break;
      case this.btnSpin.hasClass('spin-autoport'):
        this.initAutoport(fullVin);
        break;
    }
  }

  initEvo() {
    const href = this.btnSpin.attr('href');

    if (!href || href === '#') return;

    const evoModal = jQuery(`${href}`);

    if (evoModal) {
      this.btnSpin.attr('data-type', 'inline');
      this.btnSpin.show();
      this.additionalClass = 'evo';
    }
  }

  initImpel(fullVin) {
    const auth = this.btnSpin.data('auth');
    const clientID = this.btnSpin.data('clientid');

    if (auth !== undefined && clientID !== undefined) {
      const url = `https://wa-detection-api.spincar.com/?auth=${auth}&cid=${clientID}&vin=${fullVin}`;

      jQuery.ajax({
        url: url,
        type: 'GET',
        dataType: 'json',
        success: (data) => {
          if (data.url) {
            this.btnSpin.show().attr('data-src', data.url + '#!hidecarousel!disabledrawer');
            this.additionalClass = 'impel';
          }
        },
        error: function (data) {
          if (data.statusText === 'error') {
            console.error('Error:', data.statusText);
          }
        },
      });
    }
  }

  initDealerImage(fullVin) {
    const dealerID = this.btnSpin.data('dealer-id') || this.btnSpin.data('clientid');

    if (dealerID === undefined) return;

    const url = `https://viewer.dealerimagepro.com/?dealer=${dealerID}&vin=${fullVin}&viewer=slider&activeView=images&excludeViews=imperfections,window-sticker`;

    this.checkDealerImageURL(dealerID, fullVin, () => {
      this.btnSpin.show().attr('data-src', url);
      this.additionalClass = 'dealerimage';
    });
  }

  initAutoexact(fullVin) {
    const url = `https://s3.amazonaws.com/cdn.360booth.com/player_s1.html?vin=${fullVin}`;
    const checkingURL = `https://s3.amazonaws.com/photos.autoexact.com/photos/${fullVin.slice(0, 9)}/${fullVin}_data.json`;

    this.checkURL(checkingURL, () => {
      this.btnSpin.show().attr('data-src', url);
      this.additionalClass = 'autoexact';
    });
  }

  initLesa(fullVin) {
    const postfix = this.isMobile ? '' : '&full_size=1';
    const url = `https://player1.lesautomotive.com/?mode=vdp&vin=${fullVin}${postfix}`;

    this.checkURL(url, () => {
      this.btnSpin.show().attr('data-src', url);
      this.additionalClass = 'lesa';
    });
  }

  initVideo() {
    const videoURL = this.btnSpin.data('url');

    if (videoURL && videoURL !== '') {
      this.btnSpin.show().attr('data-src', videoURL).addClass('video-link');
      this.additionalClass = 'video';
    }
  }

  initAutoport(fullVin) {
    const headers = {
      'Content-Type': 'application/json',
      Authorization: 'Bearer YOUR_ACCESS_TOKEN',
    };

    const data = {
      autoport_id: 37,
      vin: fullVin,
      limit: 50,
      offset: 0,
      image_type: 'webp',
    };

    fetch(this.options.autoportUrl, {
      method: 'POST',
      headers: headers,
      body: JSON.stringify(data),
    })
      .then((response) => {
        if (!response.ok) {
          throw new Error(`HTTP error! status: ${response.status}`);
        }

        return response.json();
      })
      .then((data) => {
        this.btnSpin.show().attr('data-src', data.data[0].insta360).removeAttr('data-type');
      })
      .catch((error) => {
        console.error('Error:', error.message);
      });
  }

  checkURL(url, successCallback) {
    const request = new XMLHttpRequest();

    request.open('GET', url, true);

    request.onreadystatechange = function () {
      if (request.readyState === 4) {
        if (request.status !== 404) {
          successCallback();
        } else {
          console.error('Error:', request.statusText);
        }
      }
    };

    request.send();
  }

  checkDealerImageURL(dealerID, vin, successCallback) {
    jQuery.ajax({
      url: this.options.dealerImageApiUrl,
      type: 'POST',
      contentType: 'application/json',
      dataType: 'json',
      data: JSON.stringify({
        dealer: String(dealerID),
        vin: vin,
        viewer: 'slider',
        activeView: 'images',
      }),
      success: (data) => {
        successCallback();
      },
      error: function (data) {
        console.error('Error:', data.statusText);
      },
    });
  }
}
