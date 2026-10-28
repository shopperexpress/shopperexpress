import $ from 'jquery';

/*
 * jQuery custom map plugin
 */
(function ($) {
  function CustomMap(options) {
    this.options = {
      coordinates: null,
      mapOptions: {
        zoom: 16,
        panControl: true,
        zoomControl: true,
        scrollwheel: false,
        mapTypeControl: false,
        scaleControl: true,
        fullscreenControl: false,
        streetViewControl: false,
        overviewMapControl: false,
        rotateControl: false,
      },
      ...options,
    };

    this.init();
  }

  CustomMap.prototype = {
    init: function () {
      if (!window.google || !window.google.maps) return;

      if (this.options.holder) {
        this.findElements();
        this.attachEvents();
      }
    },
    findElements: function () {
      this.win = $(window);
      this.holder = $(this.options.holder);
      this.location = this.holder.data('coordinates');
      this.zoom = this.holder.data('zoom');
      this.markersArr = [];
    },
    attachEvents: function () {
      const id = Math.random().toString(36).slice(2, 11);

      if (this.zoom) {
        this.options.mapOptions.zoom = this.zoom;
      }

      this.mapOptions = {
        mapId: `map-${id}`,
        ...this.options.mapOptions,
      };

      this.map = new google.maps.Map(this.holder[0], this.mapOptions);
      this.bounds = new google.maps.LatLngBounds();

      $.getJSON(this.holder.data('markers'), (data) => {
        this.drawMarkers(data.markers);
      });

      if (this.location) {
        const coordinate = this.location.split(',').map((c) => c.trim());

        this.createMarker({
          lat: coordinate[0],
          lng: coordinate[1],
        });
      }

      this.resizeHandler = () => {
        this.refreshMap();
      };

      this.resizeHandler();

      this.win.on('resize', this.resizeHandler);
    },
    drawMarkers: function (data) {
      for (let i = 0; i < data.length; i++) {
        this.createMarker(data[i]);
      }

      this.refreshMap();
    },
    createMarker: function (item) {
      const coordinates = new google.maps.LatLng(item.lat, item.lng);

      const marker = new google.maps.marker.AdvancedMarkerElement({
        position: coordinates,
        map: this.map,
      });

      this.markersArr.push(marker);
      this.bounds.extend(coordinates);
    },
    removeMarkers: function () {
      if (!this.markersArr.length) return;

      for (let i = 0; i < this.markersArr.length; i++) {
        this.markersArr[i].setMap(null);
      }

      this.markersArr.length = 0;
      this.bounds = new google.maps.LatLngBounds();
    },
    refreshMap: function () {
      google.maps.event.trigger(this.map, 'resize');

      this.map.fitBounds(this.bounds);

      const listener = google.maps.event.addListener(this.map, 'idle', () => {
        if (this.map.getZoom() > this.options.zoom) {
          this.map.setZoom(this.options.zoom);
        }

        google.maps.event.removeListener(listener);
      });
    },
  };

  $.fn.customMap = function (opt) {
    return this.each(function () {
      $(this).data(
        'CustomMap',
        new CustomMap(
          $.extend(opt, {
            holder: this,
          })
        )
      );
    });
  };
})($);
