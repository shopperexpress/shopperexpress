import $ from 'jquery';

/* video plugin */
(function ($) {
  function BgVideo(options) {
    this.options = $.extend(
      {
        containerClass: 'js-video',
        btnPlay: '.btn-play',
        btnPause: '.btn-pause',
        popupBtnClose: '<a href="#" class="close"></a>',
        loadedClass: 'video-loaded',
        playingClass: 'playing',
        pausedClass: 'paused',
        popupClass: 'js-video-popup',
        activePopupClass: 'active-popup',
        activePageClass: 'active-video-popup',
        fluidVideoClass: 'fluid-video',
        autoplayVideoClass: 'bg-video',
        vimeoAPI: '//player.vimeo.com/api/player.js',
        wistiaAPI: '//fast.wistia.com/assets/external/E-v1.js',
        youtubeAPI: '//www.youtube.com/iframe_api',
      },
      options
    );

    this.init();
  }

  BgVideo.prototype = {
    init: function () {
      if (this.options.holder) {
        this.findElements();
        this.attachEvents();
        this.makeCallback('onInit', this);
      }
    },
    findElements: function () {
      const self = this;

      this.win = $(window);
      this.page = $('body');
      this.holder = $(this.options.holder);
      this.videoContainer = null;
      this.player = null;
      this.videoData = this.holder.data('video');
      this.btnPlay = this.holder.find(this.options.btnPlay);
      this.btnPause = this.holder.find(this.options.btnPause);
      this.autoplay = this.videoData.autoplay === undefined ? true : this.videoData.autoplay;
      this.loop = this.videoData.loop === undefined ? false : this.videoData.loop;
      this.fluidWidth = this.videoData.fluidWidth === undefined ? false : this.videoData.fluidWidth;
      this.lazyLoad = this.videoData.lazyLoad === undefined ? false : this.videoData.lazyLoad;
      this.isPopup = this.holder.is('a');
      this.resizeTimer = null;
      this.loaded = false;

      this.onScroll = function () {
        self.scrollHandler();
      };

      if (this.isPopup) {
        this.popup = $('<div class="' + this.options.popupClass + '"/>')
          .append($(this.options.popupBtnClose))
          .appendTo(this.page);
        this.holder = this.popup;
        this.btnOpen = $(this.options.holder);
        this.btnClose = this.popup.find('> a');
        this.autoplay = false;
      } else {
        if (this.autoplay) {
          this.holder.addClass(this.options.autoplayVideoClass);
        }

        if (this.fluidWidth) {
          this.holder.addClass(this.options.fluidVideoClass);
        }

        if (!this.lazyLoad) {
          this.initPlayer();
        } else if (!this.holder.closest('.specails-slider').length) {
          this.win.on('scroll', this.onScroll);
        }
      }
    },
    scrollHandler: function () {
      if (this.win.scrollTop() + this.win.height() * 2 >= this.holder.offset().top && !this.loaded) {
        this.initPlayer();
        this.loaded = true;
        this.win.off('scroll', this.onScroll);
      }
    },
    initPlayer: function () {
      switch (this.videoData.type) {
        case 'youtube':
          this.initYoutube();
          break;
        case 'vimeo':
          this.initVimeo();
          break;
        case 'wistia':
          this.initWistia();
          break;
        case 'html5':
          this.initHTML5();
          break;
        default:
          return false;
      }
    },
    attachEvents: function () {
      const self = this;

      this.playClickHandler = function (e) {
        e.preventDefault();
        self.playVideo();
      };

      this.pauseClickHandler = function (e) {
        e.preventDefault();
        self.pauseVideo();
      };

      this.openClickHandler = function (e) {
        e.preventDefault();
        self.showPopup();
      };

      this.closeClickHandler = function (e) {
        e.preventDefault();
        self.hidePopup();
      };

      this.resizeHandler = function () {
        if (self.videoContainer !== null && !self.fluidWidth) {
          clearTimeout(self.resizeTimer);

          self.resizeTimer = setTimeout(function () {
            self.resizeVideo();
          }, 200);
        }
      };

      this.holder
        .on('loaded.video', function () {
          self.resizeHandler();
          self.holder.addClass(self.options.loadedClass);
        })
        .on('playing.video', function () {
          // self.pauseAllVideos();
          self.holder.addClass(self.options.playingClass).removeClass(self.options.pausedClass).trigger('playingVideo');
          self.makeCallback('playingVideo', self);
        })
        .on('paused.video', function () {
          self.holder.addClass(self.options.pausedClass).trigger('pauseVideo');
          self.makeCallback('pauseVideo', self);
        })
        .on('ended.video', function () {
          self.holder.removeClass(self.options.playingClass + ' ' + self.options.pausedClass).trigger('endedVideo');
          self.makeCallback('endedVideo', self);
        });

      if (this.isPopup) {
        this.btnOpen.on('click', this.openClickHandler);
        this.btnClose.on('click', this.closeClickHandler);
      }

      this.btnPlay.on('click', this.playClickHandler);
      this.btnPause.on('click', this.pauseClickHandler);
      this.resizeHandler();
      this.win.on('load resize orientationchange', this.resizeHandler);
    },
    initYoutube: function () {
      const self = this;
      const container = $('<div />').addClass(this.options.containerClass).appendTo(this.holder);

      const opt = {
        playlist: this.autoplay ? this.videoData.video : null,
        autoplay: this.autoplay || this.isPopup ? 1 : 0,
        loop: this.loop ? 1 : 0,
        controls: this.autoplay ? 0 : 1,
        showinfo: 0,
        modestbranding: 1,
        disablekb: 1,
        fs: this.autoplay ? 0 : 1,
        cc_load_policy: 0,
        iv_load_policy: 3,
      };

      const loadPlayer = function () {
        var player = new YT.Player(container[0], {
          videoId: self.videoData.video,
          playerVars: opt,
          events: {
            onReady: function () {
              if (self.autoplay) {
                player.mute();
              }

              self.videoContainer = self.holder.find('iframe');
              self.holder.trigger('loaded.video');

              self.player = {
                play: function () {
                  player.playVideo();
                },
                pause: function () {
                  player.pauseVideo();
                },
              };
            },
            onStateChange: function (state) {
              switch (state.data) {
                case 0:
                  self.holder.trigger('ended.video');
                  break;
                case 1:
                  self.holder.trigger('playing.video');
                  break;
                case 2:
                  self.holder.trigger('paused.video');
                  break;
                default:
                  break;
              }
            },
          },
        });
      };

      if (typeof YT === 'undefined' || typeof YT.Player === 'undefined') {
        const youtubeReady = window.onYouTubeIframeAPIReady;

        window.onYouTubeIframeAPIReady = function () {
          if (youtubeReady) youtubeReady();
          loadPlayer();
        };

        $.getScript(this.options.youtubeAPI);
      } else {
        loadPlayer();
      }
    },
    initVimeo: function () {
      const self = this;
      const blockId = this.getRandomId();

      const opt = {
        id: this.videoData.video,
        autoplay: this.autoplay || this.isPopup,
        autopause: this.autoplay ? false : true,
        muted: this.autoplay ? true : false,
        loop: this.loop,
        controls: this.autoplay ? false : true,
        byline: this.autoplay ? false : true,
        title: this.autoplay ? false : true,
      };

      const loadPlayer = function () {
        self.holder.attr('id', blockId);

        const player = new Vimeo.Player(blockId, opt);

        player.ready().then(function () {
          self.videoContainer = self.holder.find('iframe').addClass(self.options.containerClass);

          self.holder.trigger('loaded.video');

          player.on('play', function () {
            self.holder.trigger('playing.video');
          });

          player.on('pause', function () {
            self.holder.trigger('paused.video');
          });

          player.on('ended', function () {
            self.holder.trigger('ended.video');
          });

          self.player = {
            play: function () {
              player.play();
            },
            pause: function () {
              player.pause();
            },
          };
        });
      };

      if (typeof Vimeo === 'undefined' || typeof Vimeo.Player === 'undefined') {
        $.getScript(this.options.vimeoAPI, loadPlayer);
      } else {
        loadPlayer();
      }
    },
    initWistia: function () {
      const self = this;
      const blockId = this.getRandomId();

      const opt = {
        autoplay: this.isPopup ? true : this.autoplay,
        loop: this.loop,
        volume: this.autoplay ? 0 : 1,
        controls: this.autoplay ? (this.fluidWidth ? true : false) : true,
        endVideoBehavior: this.loop ? 'loop' : 'reset',
      };

      const loadPlayer = function () {
        const src =
          '//fast.wistia.net/embed/iframe/' +
          self.videoData.video +
          '?controlsVisibleOnLoad=false&playbar=' +
          opt.controls +
          '&playButton=' +
          opt.controls +
          '&autoPlay=' +
          opt.autoplay +
          '&endVideoBehavior=' +
          opt.endVideoBehavior +
          '&fullscreenButton=' +
          opt.controls +
          '&smallPlayButton=false&volume=' +
          opt.volume +
          '&volumeControl=' +
          opt.controls;

        self.videoContainer = $(`<iframe
          allowtransparency="true" id="${blockId}'"
          frameborder="0"
          scrolling="no"
          class="wistia_embed"
          name="wistia_embed"
          title="Video of ${self.videoData.title || 'wistia'}"
          role="application"
          aria-label="Video of ${self.videoData.title || 'wistia'}"
        />`)
          .addClass(self.options.containerClass)
          .appendTo(self.holder)
          .attr('src', src);

        window._wq = window._wq || [];

        _wq.push({
          id: blockId,
          onReady: function (video) {
            self.holder.trigger('loaded.video');

            self.player = {
              play: function () {
                video.play();
              },
              pause: function () {
                video.pause();
              },
              mute: function () {
                video.mute();
                video.volume(0);
              },
              unmute: function () {
                video.unmute();
                video.volume(100);
              },
            };

            video
              .bind('play', function () {
                self.holder.trigger('playing.video');
              })
              .bind('pause', function () {
                self.holder.trigger('paused.video');
              })
              .bind('end', function () {
                self.holder.trigger('ended.video');

                if (opt.loop) {
                  video.play();
                }
              });
          },
        });
      };

      if (typeof Wistia === 'undefined') {
        $.getScript(this.options.wistiaAPI, loadPlayer);
      } else {
        loadPlayer();
      }
    },
    initHTML5: function () {
      const self = this;

      const opt = {
        controls: this.autoplay ? '' : 'controls',
        autoplay: this.isPopup ? 'autoplay playsinline' : this.autoplay ? 'autoplay playsinline muted' : '',
        loop: this.loop ? 'loop' : '',
      };

      this.videoContainer = $(`<video ${opt.controls} ${opt.autoplay} ${opt.loop} />`).addClass(this.options.containerClass).appendTo(this.holder);

      this.videoContainer[0].addEventListener(
        'loadeddata',
        function () {
          self.holder.trigger('loaded.video');
        },
        false
      );

      this.videoContainer[0].addEventListener(
        'progress',
        function () {
          self.holder.trigger('loaded.video');
        },
        false
      );

      this.videoContainer.prop('src', this.videoData.video);

      this.videoContainer
        .on('play', function () {
          self.holder.trigger('playing.video');
        })
        .on('pause', function () {
          self.holder.trigger('paused.video');
        })
        .on('ended', function () {
          self.holder.trigger('ended.video');
        });

      self.player = {
        play: function () {
          self.videoContainer[0].play();
        },
        pause: function () {
          self.videoContainer[0].pause();
        },
      };
    },
    pauseAllVideos: function () {
      if (this.autoplay && this.videoData.type === 'html5') {
        return;
      }

      $('[data-video].' + this.options.playingClass)
        .not(this.holder)
        .not('.' + this.options.autoplayVideoClass)
        .each(function () {
          const holder = $(this);

          holder.data('BgVideo').player.pause();
        });
    },
    getDimensions: function (data) {
      const ratio = data.videoRatio || data.videoWidth / data.videoHeight;
      let slideWidth = data.maskWidth;
      let slideHeight = slideWidth / ratio;

      if (slideHeight < data.maskHeight) {
        slideHeight = data.maskHeight;
        slideWidth = slideHeight * ratio;
      }

      return {
        width: slideWidth,
        height: slideHeight,
        top: (data.maskHeight - slideHeight) / 2,
        left: (data.maskWidth - slideWidth) / 2,
      };
    },
    getRatio: function () {
      if (this.videoContainer.attr('width') && this.videoContainer.attr('height')) {
        return this.videoContainer.attr('width') / this.videoContainer.attr('height');
      } else {
        return 16 / 9;
      }
    },
    resizeVideo: function () {
      const styles = this.getDimensions({
        videoRatio: this.getRatio(this.videoContainer),
        maskWidth: this.holder.width(),
        maskHeight: this.holder.height(),
      });

      this.videoContainer.css({
        width: styles.width,
        height: styles.height,
        marginTop: styles.top,
        marginLeft: styles.left,
      });
    },
    playVideo: function () {
      if (!this.holder.hasClass(this.options.loadedClass)) return;

      if (!this.holder.hasClass(this.options.playingClass) || this.holder.hasClass(this.options.pausedClass)) {
        this.player.play();
        this.holder.blur();
      } else {
        if (!this.btnPause.length) {
          this.player.pause();
        }
      }
    },
    pauseVideo: function () {
      this.player.pause();
    },
    showPopup: function () {
      const self = this;

      if (this.holder.hasClass(this.options.loadedClass)) {
        setTimeout(function () {
          self.player.play();
        }, 500);
      } else {
        this.initPlayer();
      }

      this.page.addClass(this.options.activePageClass);
      this.popup.addClass(this.options.activePopupClass);
    },
    hidePopup: function () {
      if (this.player) {
        this.player.pause();
      }

      this.page.removeClass(this.options.activePageClass);
      this.popup.removeClass(this.options.activePopupClass);
    },
    getRandomId: function () {
      const s4 = function () {
        return (((1 + Math.random()) * 0x10000) | 0).toString(16).substring(1);
      };

      return s4() + s4() + '-' + s4() + '-' + s4() + '-' + s4() + '-' + s4() + s4() + s4();
    },
    makeCallback: function (name) {
      if (typeof this.options[name] === 'function') {
        const args = Array.prototype.slice.call(arguments);

        args.shift();
        this.options[name].apply(this, args);
      }
    },
    destroy: function () {
      this.videoContainer.remove();
      this.btnPlay.off('click', this.playClickHandler);
      this.btnPause.off('click', this.pauseClickHandler);
      this.win.off('load resize orientationchange', this.resizeHandler);

      if (this.isPopup) {
        this.popup.remove();
        this.btnOpen.off('click', this.openClickHandler);
        this.btnClose.off('click', this.closeClickHandler);
      }

      this.holder
        .removeClass(this.options.loadedClass + ' ' + this.options.playingClass)
        .off('.video')
        .removeData('BgVideo');
    },
  };

  $.fn.bgVideo = function (opt) {
    return this.each(function () {
      $(this).data(
        'BgVideo',
        new BgVideo(
          $.extend(opt, {
            holder: this,
          })
        )
      );
    });
  };
})($);
