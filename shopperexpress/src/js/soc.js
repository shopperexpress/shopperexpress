/**
 * SOC — Site Operation Center
 * Admin JS module. Requires jQuery (available as $) and socData localized object.
 *
 * socData shape:
 *   { ajaxUrl, nonce, strings: { error, loading, success, confirm_delete } }
 */
(function ($) {
  'use strict';

  const SOC = {};

  // Quiet background poll after "Flush All" — no progress UI, just detects when
  // the atomic rebuild job (running in WP-Cron) has finished so we can show a
  // single "done" toast instead of leaving the admin guessing whether anything
  // happened (the table itself looks identical the whole time by design — every
  // group only flips once ALL of them are ready).
  function pollRebuildCompletion() {
    SOC.ajax('soc_cache_rebuild_status', {}, function (state) {
      if (!state || 'running' === state.status) return;

      clearInterval(SOC._rebuildPoller);
      SOC._rebuildPoller = null;

      if ('success' === state.status) {
        const when = state.finished_at_formatted ? ' at ' + state.finished_at_formatted : '';
        SOC.showSuccess('Intice cache rebuild finished — ' + (state.steps_total || 0) + ' groups updated' + when + '.');
      } else if ('failed' === state.status) {
        SOC.showError('Intice cache rebuild failed at "' + (state.failed_step || '?') + '": ' + (state.error || 'unknown error') + '. Nothing changed.');
      }

      SOC.reloadPanel('api-settings');
    });
  }

  // ----------------------------------------------------------------
  // Init
  // ----------------------------------------------------------------
  SOC.init = function () {
    SOC.bindNav();
    SOC.bindRefresh();
    SOC.bindCronActions();
    SOC.bindCacheActions();
    SOC.bindCachePtActions();
    SOC.bindLogPagination();
    SOC.bindApiTest();
    SOC.bindTwilioTestSms();
    SOC.bindDbCleanup();
    SOC.bindApiSettings();
    SOC.bindApiFilters();

    SOC.bindLeadDelivery();
    SOC.bindGoogleReviews();
    SOC.bindCountdownTimers();
    SOC.bindToggleRows();
  };

  // ----------------------------------------------------------------
  // Toggle rows (views/partials/toggle-row.php)
  // ----------------------------------------------------------------

  /**
   * Flip a toggle row to on/off after its AJAX save — pill, note and tooltip
   * text come from the row's data-* attributes.
   *
   * @param {jQuery}  $input Row checkbox.
   * @param {boolean} on     New state.
   */
  SOC.setToggleRow = function ($input, on) {
    const $row = $input.closest('.soc-toggle-row');
    const state = on ? 'on' : 'off';

    $input.prop('checked', !!on);
    $row.toggleClass('soc-api-mode-card--api', !!on).toggleClass('soc-api-mode-card--wp', !on);
    $row
      .find('.soc-toggle-row__pill')
      .attr('class', 'soc-toggle-row__pill soc-toggle-row__pill--' + (on ? 'ok' : 'off'))
      .text($row.data('status-' + state));
    $row.find('.soc-toggle-row__note').text($row.data('note-' + state));
    $row.find('.soc-toggle-row__tip').text($row.data('desc-' + state));
  };

  // ⓘ tooltips: shown on hover/focus, pinned open by click, closed by Esc.
  SOC.bindToggleRows = function () {
    const ns = '.socToggleRows';
    $(document).off(ns);

    function setTip($btn, open) {
      const $row = $btn.closest('.soc-toggle-row');

      $btn.attr('aria-expanded', open ? 'true' : 'false');
      $row.toggleClass('soc-toggle-row--tip-open', open);
      $row.find('.soc-toggle-row__tip').prop('hidden', !open);
    }

    $(document)
      .on('mouseenter' + ns + ' focus' + ns, '.soc-toggle-row__info', function () {
        setTip($(this), true);
      })
      .on('mouseleave' + ns + ' blur' + ns, '.soc-toggle-row__info', function () {
        if (!$(this).data('pinned')) {
          setTip($(this), false);
        }
      })
      .on('click' + ns, '.soc-toggle-row__info', function () {
        const $btn = $(this);
        const pinned = !$btn.data('pinned');

        $btn.data('pinned', pinned);
        setTip($btn, pinned);
      })
      .on('keydown' + ns, function (e) {
        if ('Escape' === e.key) {
          $('.soc-toggle-row__info').each(function () {
            $(this).data('pinned', false);
            setTip($(this), false);
          });
        }
      });
  };

  // ----------------------------------------------------------------
  // Sidebar nav: collapsible groups.
  // Pure UI — every item is still a real <a href>, no routing happens here.
  // ----------------------------------------------------------------
  SOC.bindNav = function () {
    // Namespaced so reloadPanel()'s repeated SOC.init() calls replace the
    // previous binding instead of stacking duplicates — without this, each
    // panel reload (e.g. Refresh) adds another identical handler, so a click
    // toggles the class twice and the accordion looks stuck.
    const ns = '.socNav';
    $(document).off(ns);

    $(document).on('click' + ns, '[data-soc-nav-group] > .soc-nav__group-toggle', function () {
      const $group = $(this).closest('[data-soc-nav-group]');
      const isOpen = $group.hasClass('is-open');
      $group.toggleClass('is-open', !isOpen);
      $(this).attr('aria-expanded', isOpen ? 'false' : 'true');
    });

    // Mobile: sidebar collapses into a dropdown off the topbar, toggled by
    // .soc-nav-toggle. Desktop ignores .is-open (CSS only applies it under
    // the mobile breakpoint), so this is safe to leave bound at all widths.
    $(document).on('click' + ns, '.soc-nav-toggle', function () {
      const $toggle = $(this);
      const $nav = $('#soc-nav');
      const isOpen = $nav.hasClass('is-open');
      $nav.toggleClass('is-open', !isOpen);
      $toggle.attr('aria-expanded', isOpen ? 'false' : 'true');
    });

    $(document).on('click' + ns, function (e) {
      const $nav = $('#soc-nav');
      if (!$nav.hasClass('is-open')) return;
      if ($(e.target).closest('#soc-nav, .soc-nav-toggle').length) return;
      $nav.removeClass('is-open');
      $('.soc-nav-toggle').attr('aria-expanded', 'false');
    });
  };

  // ----------------------------------------------------------------
  // AJAX helper
  // ----------------------------------------------------------------
  SOC.ajax = function (action, data, successCb, errorCb) {
    $.post(socData.ajaxUrl, Object.assign({ action: action, nonce: socData.nonce }, data))
      .done(function (res) {
        if (res && res.success) {
          if (typeof successCb === 'function') successCb(res.data);
        } else {
          const msg = res && res.data && res.data.message ? res.data.message : socData.strings.error;
          if (typeof errorCb === 'function') errorCb(msg);
          else SOC.showError(msg);
        }
      })
      .fail(function (xhr) {
        // SOC_Response::error() answers with an HTTP 4xx plus {data:{message}} —
        // show that message instead of the generic one.
        let msg = socData.strings.error;

        if (xhr && xhr.responseJSON && xhr.responseJSON.data && xhr.responseJSON.data.message) {
          msg = xhr.responseJSON.data.message;
        } else if (xhr && 'timeout' === xhr.statusText) {
          msg = 'The request timed out — try again.';
        } else if (xhr && 0 === xhr.status) {
          msg = 'Could not reach the server — check your connection.';
        } else if (xhr && (-1 === xhr.responseText || '0' === xhr.responseText)) {
          msg = 'This page is out of date — reload it (Ctrl+F5) and try again.';
        } else if (xhr && xhr.status >= 500) {
          msg = 'Server error (HTTP ' + xhr.status + ') — see the PHP error log.';
        } else if (xhr && 403 === xhr.status) {
          msg = 'Session expired or access denied — reload the page.';
        }

        if (typeof errorCb === 'function') errorCb(msg);
        else SOC.showError(msg);
      });
  };

  // ----------------------------------------------------------------
  // Flash notices
  // ----------------------------------------------------------------
  SOC._noticeTimer = null;

  SOC.showNotice = function (type, message) {
    let $notice = $('#soc-action-notice');
    if (!$notice.length) {
      $notice = $('<div id="soc-action-notice" class="soc-notice" role="alert"></div>');
      $('#soc-panel-body').prepend($notice);
    }

    clearTimeout(SOC._noticeTimer);

    $notice
      .removeClass('soc-notice--success soc-notice--error soc-notice--loading soc-notice--warn is-visible')
      .addClass('soc-notice--' + type + ' is-visible')
      .text(message);

    if (type === 'success') {
      SOC._noticeTimer = setTimeout(function () {
        $notice.removeClass('is-visible');
      }, 3500);
    }
  };

  SOC.showError = function (msg) {
    SOC.showNotice('error', msg || socData.strings.error);
  };
  SOC.showSuccess = function (msg) {
    SOC.showNotice('success', msg || socData.strings.success);
  };
  // The API Settings panel stays quiet: no "Loading…" / "Refreshed." notices
  // (its cache table re-renders often, e.g. while a VDP build is polled).
  SOC.isQuietPanel = function (module) {
    return 'api-settings' === (module || $('#soc-panel').data('module'));
  };

  SOC.showLoading = function () {
    if (SOC.isQuietPanel()) return;
    SOC.showNotice('loading', socData.strings.loading);
  };

  // ----------------------------------------------------------------
  // Dynamic panel reload
  // Fetches rendered module HTML from server and swaps #soc-panel-body.
  // Re-runs SOC.init() so all event bindings work in the new markup.
  // ----------------------------------------------------------------
  SOC.reloadPanel = function (module, cb) {
    const quiet = SOC.isQuietPanel(module);

    SOC.showLoading();
    SOC.ajax('soc_load_panel', { module: module }, function (data) {
      if (data && data.html) {
        // The notice lives inside the panel body — keep an action's own toast
        // (e.g. "Cache group flushed") alive across a quiet re-render.
        const $notice = quiet ? $('#soc-action-notice').detach() : null;
        const scrollY = quiet ? window.scrollY : 0;

        $('#soc-panel-body').html(data.html);
        SOC.init(); // re-bind all handlers to new DOM

        if (quiet) window.scrollTo(0, scrollY);

        if (quiet) {
          if ($notice && $notice.length && !$notice.hasClass('soc-notice--loading')) {
            $('#soc-panel-body').prepend($notice);
          }
        } else {
          SOC.showSuccess('Refreshed.');
        }

        if (typeof cb === 'function') cb();
      }
    });
  };

  SOC.bindRefresh = function () {
    $(document).on('click', '.soc-refresh-btn', function () {
      const module = $(this).closest('[data-module]').data('module') || $('#soc-panel').data('module');
      SOC.reloadPanel(module);
    });
  };

  // ----------------------------------------------------------------
  // Cron actions
  // ----------------------------------------------------------------
  SOC.bindCronActions = function () {
    $(document).on('click', '.soc-run-cron', function () {
      const $btn = $(this);
      const hook = $btn.data('hook');
      let args = $btn.data('args') || [];
      if (typeof args === 'string') {
        try {
          args = JSON.parse(args);
        } catch (e) {
          args = [];
        }
      }
      $btn.prop('disabled', true);
      SOC.showLoading();
      SOC.ajax(
        'soc_run_cron',
        { hook: hook, args: args },
        function (data) {
          SOC.showSuccess('Cron "' + hook + '" executed at ' + (data.ran_at || ''));
          $btn.prop('disabled', false);
        },
        function (msg) {
          SOC.showError(msg);
          $btn.prop('disabled', false);
        }
      );
    });

    $(document).on('click', '.soc-delete-cron', function () {
      if (!confirm(socData.strings.confirm_delete)) return;
      const $btn = $(this);
      const hook = $btn.data('hook');
      const timestamp = $btn.data('timestamp');
      $btn.prop('disabled', true);
      SOC.showLoading();
      SOC.ajax(
        'soc_delete_cron',
        { hook: hook, timestamp: timestamp },
        function () {
          SOC.showSuccess('Cron "' + hook + '" deleted.');
          $btn.closest('tr').fadeOut(300, function () {
            $(this).remove();
          });
        },
        function (msg) {
          SOC.showError(msg);
          $btn.prop('disabled', false);
        }
      );
    });

    $(document).on('click', '.soc-reschedule-cron', function () {
      const $btn = $(this);
      const $row = $btn.closest('tr');
      const hook = $btn.data('hook');
      const timestamp = $btn.data('timestamp');
      const $select = $row.find('.soc-schedule-select');
      const recurrence = $select.val();
      const args = $row.find('.soc-run-cron').data('args') || '[]';
      $btn.prop('disabled', true);
      SOC.showLoading();
      SOC.ajax(
        'soc_reschedule_cron',
        { hook: hook, timestamp: timestamp, recurrence: recurrence, args: args },
        function (data) {
          SOC.showSuccess('Cron "' + hook + '" rescheduled as ' + recurrence + '.');
          $row.find('td:nth-child(3)').text(recurrence);
          $btn.data('timestamp', data.new_timestamp || timestamp);
          $btn.prop('disabled', false);
        },
        function (msg) {
          SOC.showError(msg);
          $btn.prop('disabled', false);
        }
      );
    });
  };

  // ----------------------------------------------------------------
  // Cache — global actions
  // ----------------------------------------------------------------
  SOC.bindCacheActions = function () {
    $(document).on('click', '#soc-clear-all-cache', function () {
      const $btn = $(this);
      $btn.prop('disabled', true);
      SOC.showLoading();
      SOC.ajax(
        'soc_clear_cache',
        {},
        function () {
          SOC.showSuccess('All cache cleared.');
          $btn.prop('disabled', false);
        },
        function (msg) {
          SOC.showError(msg);
          $btn.prop('disabled', false);
        }
      );
    });

    $(document).on('click', '#soc-clear-object-cache', function () {
      const $btn = $(this);
      $btn.prop('disabled', true);
      SOC.showLoading();
      SOC.ajax(
        'soc_clear_object_cache',
        {},
        function () {
          SOC.showSuccess('Object cache flushed.');
          $btn.prop('disabled', false);
        },
        function (msg) {
          SOC.showError(msg);
          $btn.prop('disabled', false);
        }
      );
    });

    $(document).on('click', '#soc-clear-transients', function () {
      const $btn = $(this);
      $btn.prop('disabled', true);
      SOC.showLoading();
      SOC.ajax(
        'soc_clear_transients',
        {},
        function (data) {
          const deleted = data && data.deleted_rows !== undefined ? data.deleted_rows : '?';
          SOC.showSuccess('Deleted ' + deleted + ' transients.');
          $btn.prop('disabled', false);
        },
        function (msg) {
          SOC.showError(msg);
          $btn.prop('disabled', false);
        }
      );
    });
  };

  // ----------------------------------------------------------------
  // Cache — post-type actions (cache-manager view)
  // ----------------------------------------------------------------
  const statusBadge = {
    valid: { cls: 'soc-badge--ok', label: 'Valid' },
    stale: { cls: 'soc-badge--warn', label: 'Stale' },
    expired: { cls: 'soc-badge--fail', label: 'Expired' },
    missing: { cls: 'soc-badge--neutral', label: 'Missing' },
  };

  // Timezone-safe relative time — age computed server-side, JS adds elapsed.
  const pageLoadedAt = Date.now();

  SOC.formatAge = function (secs) {
    if (secs < 60) return 'just now';
    if (secs < 3600) return Math.floor(secs / 60) + ' min ago';
    if (secs < 86400) return Math.floor(secs / 3600) + ' hr ago';
    return Math.floor(secs / 86400) + ' days ago';
  };

  SOC.tickRelativeTimes = function () {
    const elapsedSecs = Math.floor((Date.now() - pageLoadedAt) / 1000);
    $('.soc-pt-generated').each(function () {
      const age = parseInt($(this).data('age'), 10);
      $(this).text(isNaN(age) || age === 0 ? '—' : SOC.formatAge(age + elapsedSecs));
    });
  };

  SOC.pollUntilValid = function (pt, badge, generatedCell, generatedByCell, maxAttempts) {
    let attempts = 0;
    const limit = maxAttempts || 20;

    const timer = setInterval(function () {
      attempts++;

      $.post(socData.ajaxUrl, {
        action: 'soc_pt_cache_status',
        post_type: pt,
        nonce: socData.nonce,
      }).done(function (res) {
        const d = res.data;
        const status = d.status;
        const def = statusBadge[status] || statusBadge.missing;

        badge.attr('class', 'soc-badge ' + def.cls).text(def.label);

        if (status === 'valid' || status === 'stale') {
          clearInterval(timer);
          generatedCell.attr('data-age', '0').text('just now');
          if (generatedByCell && socData.userEmail) {
            generatedByCell.text(socData.userEmail).css({ color: '', fontStyle: '' });
          }
          SOC.showSuccess('Cache for "' + pt + '" regenerated successfully.');
        }
      });

      if (attempts >= limit) {
        clearInterval(timer);
        badge.attr('class', 'soc-badge soc-badge--warn').text('Timeout');
        SOC.showError('Regeneration timed out for "' + pt + '".');
      }
    }, 3000);
  };

  SOC.bindCachePtActions = function () {
    // Tick relative times immediately and every 60 s.
    SOC.tickRelativeTimes();
    clearInterval(SOC._relativeTimer);
    SOC._relativeTimer = setInterval(SOC.tickRelativeTimes, 60000);

    // Regenerate.
    $(document).on('click', '.soc-regen-pt-cache', function () {
      const $btn = $(this);
      const pt = $btn.data('pt');
      const row = $btn.closest('tr');
      const badge = row.find('.soc-badge');
      const generatedCell = row.find('.soc-pt-generated');
      const generatedByCell = row.find('.soc-pt-generated-by');

      $btn.prop('disabled', true).text('Starting…');
      SOC.showLoading();

      SOC.ajax(
        'soc_regen_pt_cache',
        { post_type: pt },
        function () {
          SOC.showNotice('loading', 'Regenerating "' + pt + '"…');
          badge.attr('class', 'soc-badge soc-badge--neutral').text('Regenerating…');
          SOC.pollUntilValid(pt, badge, generatedCell, generatedByCell, 20);
          $btn.prop('disabled', false).text('Regenerate');
        },
        function (msg) {
          SOC.showError(msg);
          $btn.prop('disabled', false).text('Regenerate');
        }
      );
    });
  };

  // ----------------------------------------------------------------
  // API test tool
  // ----------------------------------------------------------------
  SOC.bindApiTest = function () {
    $(document).on('click', '#soc-test-api-btn', function () {
      const $btn = $(this);
      const url = $('#soc-test-api-url').val().trim();
      if (!url) return;
      $btn.prop('disabled', true);
      SOC.showLoading();
      SOC.ajax(
        'soc_test_api',
        { url: url },
        function (data) {
          let badge;
          if (data && data.ok) {
            badge = '<span style="color:#00a32a;">&#10003; ' + (data.status || '') + '</span>';
          } else {
            badge = '<span style="color:#d63638;">&#10007; ' + ((data && (data.error || data.status)) || 'Error') + '</span>';
          }
          $('#soc-api-test-result')
            .html(badge + ' &mdash; ' + ((data && data.ms) || 0) + ' ms')
            .show();
          SOC.showSuccess('API test complete.');
          $btn.prop('disabled', false);
        },
        function (msg) {
          SOC.showError(msg);
          $btn.prop('disabled', false);
        }
      );
    });
  };

  // ----------------------------------------------------------------
  // Twilio test SMS
  // ----------------------------------------------------------------
  SOC.bindTwilioTestSms = function () {
    $(document).on('click', '#soc-twilio-test-sms-btn', function () {
      const $btn = $(this);
      const phone = $('#soc-twilio-test-phone').val().trim();
      if (!phone) return;
      $btn.prop('disabled', true);
      SOC.showLoading();
      SOC.ajax(
        'soc_twilio_test_sms',
        { phone: phone },
        function (data) {
          $('#soc-twilio-test-result')
            .html('<span style="color:#00a32a;">&#10003; Sent</span> &mdash; SID: ' + ((data && data.sid) || 'n/a'))
            .show();
          SOC.showSuccess('Test SMS sent.');
          $btn.prop('disabled', false);
        },
        function (msg) {
          $('#soc-twilio-test-result')
            .html('<span style="color:#d63638;">&#10007; ' + msg + '</span>')
            .show();
          SOC.showError(msg);
          $btn.prop('disabled', false);
        }
      );
    });
  };

  // ----------------------------------------------------------------
  // DB cleanup
  // ----------------------------------------------------------------
  SOC.bindDbCleanup = function () {
    $(document).on('click', '.soc-db-cleanup', function () {
      if (!confirm(socData.strings.confirm_delete)) return;
      const $btn = $(this);
      const type = $btn.data('cleanup-type');
      const $row = $btn.closest('.soc-cleanup-row');
      $btn.prop('disabled', true);
      SOC.showLoading();
      SOC.ajax(
        'soc_db_cleanup',
        { type: type },
        function (data) {
          const deleted = data && data.deleted !== undefined ? parseInt(data.deleted, 10) : 0;
          if (deleted > 0) {
            SOC.showSuccess('Cleaned up ' + deleted + ' ' + type + ' rows.');
            $row.find('.soc-cleanup-count').text('0');
            $btn.prop('disabled', true).text('Done');
          } else {
            SOC.showSuccess('Nothing to clean up for ' + type + '.');
            $btn.prop('disabled', false);
          }
        },
        function (msg) {
          SOC.showError(msg);
          $btn.prop('disabled', false);
        }
      );
    });
  };

  // ----------------------------------------------------------------
  // Cache Activity Log — client-side pagination
  // ----------------------------------------------------------------
  SOC.bindLogPagination = function () {
    const $pager = $('#soc-log-pagination');
    if (!$pager.length) return;

    const $dataEl = $('#soc-cache-log-data');
    if (!$dataEl.length) return;

    const allRows = JSON.parse($dataEl.text() || '[]');
    const perPage = parseInt($pager.data('per-page'), 10) || 10;
    const pages = parseInt($pager.data('pages'), 10) || 1;
    let current = 1;

    function renderPage(page) {
      current = page;
      const start = (page - 1) * perPage;
      const slice = allRows.slice(start, start + perPage);

      const rows = slice
        .map(function (e) {
          const time = e.time || '';
          const action = e.action ? e.action.charAt(0).toUpperCase() + e.action.slice(1) : '';
          const postType = e.post_type || '—';
          const user = e.user || '';
          return (
            '<tr>' +
            '<td>' +
            $('<span>').text(time).html() +
            '</td>' +
            '<td>' +
            $('<span>').text(action).html() +
            '</td>' +
            '<td><code>' +
            $('<span>').text(postType).html() +
            '</code></td>' +
            '<td>' +
            $('<span>').text(user).html() +
            '</td>' +
            '</tr>'
          );
        })
        .join('');

      $('#soc-cache-log-body').html(rows);
      $('#soc-log-page-num').text(page);
      $pager.find('.soc-log-prev').prop('disabled', page <= 1);
      $pager.find('.soc-log-next').prop('disabled', page >= pages);
    }

    $(document).on('click', '.soc-log-prev', function () {
      if (current > 1) renderPage(current - 1);
    });
    $(document).on('click', '.soc-log-next', function () {
      if (current < pages) renderPage(current + 1);
    });
  };

  // ----------------------------------------------------------------
  // API Settings
  // ----------------------------------------------------------------
  SOC.bindApiSettings = function () {
    // Namespaced so reloadPanel()'s repeated SOC.init() calls replace the previous
    // bindings instead of stacking duplicates — without this, each panel reload
    // (e.g. after a Flush/Build click) adds another identical handler, so the next
    // click fires the AJAX call once per accumulated binding.
    const ns = '.socApiSettings';
    $(document).off(ns);

    // Sub-tab navigation (Connection & Cache / ACF Fields Reference).
    $(document).on('click' + ns, '[data-subtab-btn]', function () {
      var tab = $(this).data('subtab-btn');
      $('[data-subtab-btn]').removeClass('soc-subtabs__btn--active');
      $(this).addClass('soc-subtabs__btn--active');
      $('[data-subtab-panel]').hide();
      $('[data-subtab-panel="' + tab + '"]').show();
    });

    // Mode toggle
    $(document).on('change' + ns, '#soc-api-mode-toggle', function () {
      const $checkbox = $(this);
      const enabled = $checkbox.is(':checked') ? 1 : 0;

      SOC.showLoading();

      SOC.ajax('soc_api_mode_toggle', { enabled: enabled }, function (data) {
        const on = data && data.enabled;

        SOC.setToggleRow($checkbox, on);

        SOC.showSuccess('Mode ' + (on ? 'enabled' : 'disabled') + '.');
      });
    });

    $(document).on('change' + ns, 'input[name="soc-video-provider"]', function () {
      const provider = $(this).val();

      SOC.showLoading();

      SOC.ajax('soc_video_provider_save', { provider: provider }, function (data) {
        SOC.showSuccess('Video provider switched to ' + (data && data.provider === 'vista' ? 'VISTA' : 'Wistia — Legacy') + '.');
      });
    });

    // Team Members mode toggle (independent of the Listings API mode above)
    $(document).on('change' + ns, '#soc-team-mode-toggle', function () {
      const $checkbox = $(this);
      const enabled = $checkbox.is(':checked') ? 1 : 0;

      SOC.showLoading();

      SOC.ajax('soc_team_mode_toggle', { enabled: enabled }, function (data) {
        const on = data && data.enabled;

        SOC.setToggleRow($checkbox, on);

        SOC.showSuccess('Team Members mode ' + (on ? 'enabled' : 'disabled') + '.');
        if ('api-settings' === $('#soc-panel').data('module')) SOC.reloadPanel('api-settings');
      });
    });

    // AI Inventory Page toggle (/llm/inventory/)
    $(document).on('change' + ns, '#soc-llm-inventory-toggle', function () {
      const $checkbox = $(this);
      const enabled = $checkbox.is(':checked') ? 1 : 0;

      SOC.showLoading();

      SOC.ajax('soc_llm_inventory_toggle', { enabled: enabled }, function (data) {
        const on = data && data.enabled;

        SOC.setToggleRow($checkbox, on);

        SOC.showSuccess('AI inventory page ' + (on ? 'enabled' : 'disabled') + '.');
        SOC.reloadPanel('api-settings');
      });
    });

    // Show key input on "Change" click
    $(document).on('click' + ns, '#soc-api-key-edit', function () {
      $(this).hide();
      $(this).closest('td').find('.soc-masked-key').hide();
      $('#soc-intice-api-key').show().trigger('focus');
    });

    // Save credentials
    $(document).on('click' + ns, '#soc-save-api-credentials', function () {
      const $btn = $(this);
      const url = $('#soc-intice-api-url').val().trim();
      const key = $('#soc-intice-api-key').val().trim();

      if (!url) {
        SOC.showError('API URL is required.');
        return;
      }

      $btn.prop('disabled', true);
      SOC.showLoading();

      SOC.ajax(
        'soc_api_save_credentials',
        { api_url: url, api_key: key },
        function () {
          SOC.showSuccess('Credentials saved.');
          $btn.prop('disabled', false);
          // Hide input, reload panel to refresh masked key display
          if (key) {
            SOC.reloadPanel('api-settings');
          }
        },
        function (msg) {
          SOC.showError(msg);
          $btn.prop('disabled', false);
        }
      );
    });

    // Cache enable / disable toggle
    $(document).on('change' + ns, '#soc-intice-cache-toggle', function () {
      const $checkbox = $(this);
      const enabled = $checkbox.is(':checked') ? 1 : 0;

      SOC.showLoading();

      SOC.ajax(
        'soc_intice_cache_toggle',
        { enabled: enabled },
        function (data) {
          const on = data && data.enabled;

          SOC.setToggleRow($checkbox, on);

          SOC.showSuccess('Intice cache ' + (on ? 'enabled' : 'disabled') + '.');
        },
        function (msg) {
          SOC.showError(msg);
          // Revert toggle on error
          $checkbox.prop('checked', !enabled);
        }
      );
    });

    // Intice tool guard ("temporarily unavailable" popup) enable / disable toggle
    $(document).on('change' + ns, '#soc-intice-guard-toggle', function () {
      const $checkbox = $(this);
      const enabled = $checkbox.is(':checked') ? 1 : 0;

      SOC.showLoading();

      SOC.ajax(
        'soc_intice_guard_toggle',
        { enabled: enabled },
        function (data) {
          const on = data && data.enabled;

          SOC.setToggleRow($checkbox, on);

          SOC.showSuccess('Intice tool guard ' + (on ? 'enabled' : 'disabled') + '.');
        },
        function (msg) {
          SOC.showError(msg);
          $checkbox.prop('checked', !enabled);
        }
      );
    });

    // Queue an atomic rebuild of every Intice cache group
    $(document).on('click' + ns, '#soc-flush-all-api-cache', function () {
      if (!confirm('Rebuild all Intice Nexus cache now? Starts immediately even if a previous rebuild is still running. Visitors keep seeing current data until it finishes.'))
        return;
      const $btn = $(this);
      $btn.prop('disabled', true);
      SOC.showLoading();
      SOC.ajax(
        'soc_flush_api_cache',
        {},
        function (data) {
          const touched = (data && data.touched) || [];
          const base = 'Cache rebuild queued.';
          const msg = touched.length ? base + ' Also cleared: ' + touched.join(', ') + '.' : base;
          SOC.showSuccess(msg);

          clearInterval(SOC._rebuildPoller);
          SOC._rebuildPoller = setInterval(pollRebuildCompletion, 3000);

          // Re-render now so every managed group shows "Pending" immediately —
          // collect_api_cache() reports that cosmetic status for as long as the
          // rebuild is running, even though nothing is actually touched yet.
          SOC.reloadPanel('api-settings');
        },
        function (msg) {
          SOC.showError(msg);
          $btn.prop('disabled', false);
        }
      );
    });

    // VDP cache lifetime
    $(document).on('change' + ns, '#soc-vdp-ttl', function () {
      const $select = $(this);
      $select.prop('disabled', true);
      SOC.showLoading();
      SOC.ajax(
        'soc_intice_vdp_ttl_save',
        { ttl: $select.val() },
        function () {
          SOC.showSuccess('VDP cache lifetime saved.');
          $select.prop('disabled', false);
        },
        function (msg) {
          SOC.showError(msg);
          $select.prop('disabled', false);
        }
      );
    });

    // Toggle the explanation row under a cache group's name
    $(document).on('click' + ns, '.soc-cache-info-toggle', function () {
      const $btn = $(this);
      const $row = $btn.closest('tr').next('.soc-cache-info-row');
      const open = $row.is(':visible');
      $row.slideToggle(120);
      $btn.attr('aria-expanded', open ? 'false' : 'true');
    });

    // Flush single cache group
    $(document).on('click' + ns, '.soc-flush-api-cache-group', function () {
      const $btn = $(this);
      const group = $btn.data('group');
      $btn.prop('disabled', true);
      SOC.showLoading();
      SOC.ajax(
        'soc_flush_api_cache_group',
        { group: group },
        function (data) {
          SOC.showSuccess('Cache group "' + group + '" flushed (' + (data.deleted || 0) + ' rows).');
          SOC.reloadPanel('api-settings');
        },
        function (msg) {
          SOC.showError(msg);
          $btn.prop('disabled', false);
        }
      );
    });

    // Build (warm) an empty cache group
    $(document).on('click' + ns, '.soc-warm-api-cache-group', function () {
      const $btn = $(this);
      const group = $btn.data('group');
      $btn.prop('disabled', true);
      SOC.showLoading();
      SOC.ajax(
        'soc_warm_api_cache_group',
        { group: group },
        function () {
          SOC.showSuccess('Cache group "' + group + '" built.');
          SOC.reloadPanel('api-settings');
        },
        function (msg) {
          SOC.showError(msg);
          $btn.prop('disabled', false);
        }
      );
    });

    // Test connection
    $(document).on('click' + ns, '#soc-test-intice-api', function () {
      const $btn = $(this);
      const $result = $('#soc-connection-result');

      $btn.prop('disabled', true);
      $result.text('Testing…').css('color', '');
      SOC.showLoading();

      SOC.ajax(
        'soc_api_test_connection',
        {},
        function (data) {
          const ok = data && data.ok;
          $result.text(ok ? '✓ ' + (data.status || 'OK') + ' — ' + (data.ms || 0) + ' ms' : '✗ ' + (data.error || data.status || 'Error')).css('color', ok ? '#00a32a' : '#d63638');
          SOC.showSuccess('Connection test complete.');
          $btn.prop('disabled', false);
        },
        function (msg) {
          $result.text('✗ ' + msg).css('color', '#d63638');
          SOC.showError(msg);
          $btn.prop('disabled', false);
        }
      );
    });
  };

  // ----------------------------------------------------------------
  // API Settings — Vehicle Filters subtab
  // ----------------------------------------------------------------
  SOC.bindApiFilters = function () {
    // Toggle the "custom key" input when Field is set to "Custom field key…".
    $(document).on('change', '.soc-filter-field', function () {
      const $row = $(this).closest('.soc-filters-row');
      $row.find('.soc-filter-custom-key').toggle($(this).val() === 'custom');
    });

    // Empty/Not Empty don't need a value — hide the input so it can't be
    // typed into and mistaken for something the operator actually reads.
    const VALUELESS_OPERATORS = ['empty', 'not_empty'];
    const toggleFilterValue = function ($row) {
      const operator = $row.find('.soc-filter-operator').val();
      $row.find('.soc-filter-value').toggle(!VALUELESS_OPERATORS.includes(operator));
    };

    $(document).on('change', '.soc-filter-operator', function () {
      toggleFilterValue($(this).closest('.soc-filters-row'));
    });

    $('.soc-filters-row').each(function () {
      toggleFilterValue($(this));
    });

    // Add a row by cloning the shared <template>.
    $(document).on('click', '.soc-filter-add-row', function () {
      const $tbody = $(this).closest('.soc-section').find('.soc-filters-rows');
      const template = document.getElementById('soc-filter-row-template');
      const $newRow = $(template.content.cloneNode(true)).find('.soc-filters-row');

      $tbody.append($newRow);
      toggleFilterValue($newRow);
    });

    // Remove a row.
    $(document).on('click', '.soc-filter-remove-row', function () {
      $(this).closest('.soc-filters-row').remove();
    });

    $(document).on('click', '#soc-save-vehicle-filters', function () {
      const $btn = $(this);
      const payload = {};

      $('.soc-filters-table').each(function () {
        const postType = $(this).data('post-type');
        const rows = [];

        $(this)
          .find('.soc-filters-row')
          .each(function () {
            const $r = $(this);
            rows.push({
              field: $r.find('.soc-filter-field').val(),
              custom_key: $r.find('.soc-filter-custom-key').val().trim(),
              operator: $r.find('.soc-filter-operator').val(),
              value: $r.find('.soc-filter-value').val().trim(),
            });
          });

        payload[postType] = JSON.stringify(rows);
      });

      $btn.prop('disabled', true);
      SOC.showLoading();

      SOC.ajax(
        'soc_api_save_filters',
        payload,
        function () {
          SOC.showSuccess('Filters saved.');
          $btn.prop('disabled', false);
        },
        function (msg) {
          SOC.showError(msg);
          $btn.prop('disabled', false);
        }
      );
    });
  };

  // ----------------------------------------------------------------
  // Countdown Timers module
  // ----------------------------------------------------------------
  SOC.bindCountdownTimers = function () {
    const ns = '.socCountdown';
    $(document).off(ns);

    const $form = function () {
      return $('#soc-cd-form');
    };

    const radio = function (name) {
      return $('input[name="' + name + '"]:checked').val();
    };

    const esc = function (str) {
      return $('<div>').text(str).html();
    };

    let syncingColor = false;

    // Highlight the active swatch and mirror a custom value into the WP color picker.
    function syncColor(key) {
      const value = $('#soc-cd-' + key + '-value').val();
      const $group = $('[data-cd-color="' + key + '"]');
      const $picker = $group.find('.soc-cd-colorpicker');
      let preset = false;

      $group.find('.soc-cd__sw').each(function () {
        const on = ($(this).data('value') || '') === value;
        $(this).toggleClass('is-active', on);
        preset = preset || on;
      });

      syncingColor = true;
      if (value && !preset) {
        $picker.wpColorPicker('color', value);
      } else {
        $picker.val('').closest('.wp-picker-container').find('.wp-color-result').css('background-color', '');
      }
      syncingColor = false;
    }

    function initPickers() {
      $('.soc-cd-colorpicker').each(function () {
        const $input = $(this);

        if ($input.data('cd-init')) return;
        $input.data('cd-init', true).wpColorPicker({
          defaultColor: false,
          change: function (e, ui) {
            if (syncingColor) return;
            $('#soc-cd-' + $input.data('key') + '-value').val(ui.color.toString());
            syncVisibility();
          },
          clear: function () {
            if (syncingColor) return;
            $('#soc-cd-' + $input.data('key') + '-value').val('');
            syncVisibility();
          },
        });
      });
    }

    function renderPreview() {
      const style = radio('soc-cd-style') || 'tiles';
      const align = $('#soc-cd-align').val() || 'center';
      const label = $('#soc-cd-label').val().trim();
      const text = $('#soc-cd-text-value').val();
      const bg = $('#soc-cd-bg-value').val();
      const sample = {
        days: ['03', 'Days', 'd'],
        hours: ['14', 'Hours', 'h'],
        minutes: ['27', 'Min', 'm'],
        seconds: ['09', 'Sec', 's'],
      };
      const units = $('.soc-cd-unit:checked')
        .map(function () {
          return $(this).val();
        })
        .get();
      const vars = (text ? '--cd-text:' + text + ';' : '') + (bg ? '--cd-bg:' + bg + ';' : '');
      let items = '';

      units.forEach(function (unit, i) {
        const s = sample[unit];

        if (style === 'tiles') {
          items += '<div class="soc-cd-pv__item"><span class="soc-cd-pv__num">' + s[0] + '</span><span class="soc-cd-pv__unit">' + s[1] + '</span></div>';
        } else if (style === 'inline') {
          items += (i ? '<span class="soc-cd-pv__sep">:</span>' : '') + '<span>' + s[0] + '</span>';
        } else {
          items += '<span>' + s[0] + s[2] + '</span>';
        }
      });

      $('#soc-cd-preview').html(
        '<div class="soc-cd-pv soc-cd-pv--' +
          style +
          ' soc-cd-pv--' +
          align +
          '" style="' +
          vars +
          '">' +
          (label ? '<p class="soc-cd-pv__label">' + esc(label) + '</p>' : '') +
          '<div class="soc-cd-pv__items">' +
          items +
          '</div></div>'
      );
    }

    // Show only the fields relevant for the selected type / expired action, refresh the preview.
    function syncVisibility() {
      const type = radio('soc-cd-type');
      const action = radio('soc-cd-expired-action');

      $('[data-cd-show]').each(function () {
        $(this).toggle($(this).data('cd-show') === type);
      });
      $('[data-cd-show-action]').each(function () {
        $(this).toggle($(this).data('cd-show-action') === action);
      });
      $('.soc-cd-align').each(function () {
        $(this).toggleClass('is-active', $(this).data('align') === $('#soc-cd-align').val());
      });
      syncColor('text');
      syncColor('bg');
      renderPreview();
    }

    function setStage(theme) {
      $('#soc-cd-stage').attr('data-theme', theme);
      $('#soc-cd-stage-toggle button').each(function () {
        $(this).toggleClass('is-active', $(this).data('theme') === theme);
      });
    }

    // "2026-11-25 18:30:00" -> "2026-11-25T18:30" for <input type="datetime-local">.
    function toLocalInput(value) {
      return value ? String(value).slice(0, 16).replace(' ', 'T') : '';
    }

    function openForm(slug, timer, title) {
      const d = Object.assign({}, $form().data('defaults'), timer || {});

      $('#soc-cd-form-title').text(title);
      $('#soc-cd-form-state').text(slug ? 'Editing' : 'Unsaved');
      $('#soc-cd-slug').val(slug);
      $('#soc-cd-name').val(d.name);
      $('input[name="soc-cd-type"][value="' + d.type + '"]').prop('checked', true);
      $('#soc-cd-end').val(toLocalInput(d.end));
      $('#soc-cd-duration').val(d.duration);
      $('#soc-cd-duration-unit').val(d.duration_unit);
      $('#soc-cd-label').val(d.label);
      $('.soc-cd-unit').each(function () {
        $(this).prop('checked', (d.units || []).indexOf($(this).val()) !== -1);
      });
      $('input[name="soc-cd-style"][value="' + (d.style === 'compact' ? 'inline' : d.style) + '"]').prop('checked', true);
      $('#soc-cd-align').val(d.align);
      $('input[name="soc-cd-expired-action"][value="' + (d.expired_action === 'close' ? 'hide' : d.expired_action) + '"]').prop('checked', true);
      $('#soc-cd-expired-text').val(d.expired_text);
      $('#soc-cd-text-value').val(d.text_color || '');
      $('#soc-cd-bg-value').val(d.bg_color || '');

      const $code = $('#soc-cd-shortcode');
      $code.text(slug ? '[countdown id="' + slug + '"]' : $code.data('empty'));

      initPickers();
      setStage($('#soc-cd-stage').attr('data-theme') || 'dark');
      syncVisibility();
      $form().show()[0].scrollIntoView({ behavior: 'smooth', block: 'nearest' });
    }

    function rowTimer($btn) {
      const $row = $btn.closest('tr');

      return { slug: $row.data('cd-slug'), timer: $row.data('cd-timer') };
    }

    $(document).on('change input' + ns, '#soc-cd-form input, #soc-cd-form select', function (e) {
      if ($(e.target).hasClass('soc-cd-colorpicker') || $(e.target).closest('.wp-picker-container').length) return;
      syncVisibility();
    });

    $(document).on('click' + ns, '.soc-cd-align', function () {
      $('#soc-cd-align').val($(this).data('align'));
      syncVisibility();
    });

    $(document).on('click' + ns, '.soc-cd__sw', function () {
      $('#soc-cd-' + $(this).closest('[data-cd-color]').data('cd-color') + '-value').val($(this).data('value') || '');
      syncVisibility();
    });

    $(document).on('click' + ns, '#soc-cd-stage-toggle button', function () {
      setStage($(this).data('theme'));
    });

    // List filter tabs + search.
    function filterRows() {
      const status = $('#soc-cd-tabs .is-active').data('filter') || 'all';
      const q = ($('#soc-cd-search').val() || '').trim().toLowerCase();
      let visible = 0;

      $('.soc-cd__tb tbody tr[data-cd-slug]').each(function () {
        const show = (status === 'all' || $(this).data('cd-status') === status) && (!q || String($(this).data('cd-search')).indexOf(q) !== -1);

        $(this).toggle(show);
        visible += show ? 1 : 0;
      });
      $('.soc-cd__none').prop('hidden', visible > 0);
    }

    $(document).on('click' + ns, '#soc-cd-tabs button', function () {
      $('#soc-cd-tabs button').removeClass('is-active');
      $(this).addClass('is-active');
      filterRows();
    });

    $(document).on('input' + ns, '#soc-cd-search', filterRows);

    $(document).on('click' + ns, '#soc-cd-add', function () {
      openForm('', null, 'New timer');
    });

    $(document).on('click' + ns, '.soc-cd-edit', function (e) {
      e.preventDefault();
      const row = rowTimer($(this));

      openForm(row.slug, row.timer, 'Edit timer');
    });

    $(document).on('click' + ns, '.soc-cd-duplicate', function () {
      const row = rowTimer($(this));
      const timer = Object.assign({}, row.timer, { name: (row.timer.name || row.slug) + ' (copy)' });

      openForm('', timer, 'New timer (copy)');
    });

    $(document).on('click' + ns, '#soc-cd-cancel', function () {
      $form().hide();
    });

    // Clipboard API needs a secure context; fall back to execCommand on plain http / local hosts.
    function copyText(text) {
      if (navigator.clipboard && window.isSecureContext) {
        return navigator.clipboard.writeText(text);
      }

      return new Promise(function (resolve, reject) {
        const $tmp = $('<textarea readonly>').val(text).css({ position: 'fixed', top: 0, left: 0, opacity: 0 }).appendTo('body');

        $tmp[0].select();

        try {
          document.execCommand('copy') ? resolve() : reject(new Error('copy failed'));
        } catch (err) {
          reject(err);
        } finally {
          $tmp.remove();
        }
      });
    }

    $(document).on('click' + ns, '.soc-cd-copy', function () {
      const $btn = $(this);

      copyText($btn.data('shortcode')).then(
        function () {
          SOC.showSuccess('Shortcode copied: ' + $btn.data('shortcode'));
        },
        function () {
          SOC.showError('Could not copy the shortcode. Please copy it manually.');
        }
      );
    });

    // Delete confirmation dialog.
    let deleteSlug = '';
    let $deleteFocus = null;

    function closeDeleteDialog() {
      $('#soc-cd-delete-dialog').prop('hidden', true);
      deleteSlug = '';
      if ($deleteFocus) $deleteFocus.trigger('focus');
    }

    $(document).on('click' + ns, '.soc-cd-delete', function () {
      const row = rowTimer($(this));

      deleteSlug = row.slug;
      $deleteFocus = $(this);
      $('#soc-cd-dialog-name').text(row.timer.name || row.slug);
      $('#soc-cd-delete-dialog').prop('hidden', false);
      $('#soc-cd-delete-confirm').trigger('focus');
    });

    $(document).on('click' + ns, '[data-cd-dialog-close]', closeDeleteDialog);

    $(document).on('keydown' + ns, function (e) {
      if (e.key === 'Escape' && !$('#soc-cd-delete-dialog').prop('hidden')) closeDeleteDialog();
    });

    $(document).on('click' + ns, '#soc-cd-delete-confirm', function () {
      const slug = deleteSlug;
      const $btn = $(this).prop('disabled', true);

      $('#soc-cd-delete-dialog').prop('hidden', true);
      SOC.showLoading();

      SOC.ajax(
        'soc_countdown_delete',
        { slug: slug },
        function () {
          SOC.showSuccess('Timer deleted.');
          SOC.reloadPanel('countdown-timers');
        },
        function (msg) {
          SOC.showError(msg);
          $btn.prop('disabled', false);
        }
      );
    });

    $(document).on('click' + ns, '#soc-cd-save', function () {
      const $btn = $(this);
      const units = $('.soc-cd-unit:checked')
        .map(function () {
          return $(this).val();
        })
        .get();

      const timer = {
        name: $('#soc-cd-name').val().trim(),
        type: radio('soc-cd-type'),
        end: $('#soc-cd-end').val(),
        duration: $('#soc-cd-duration').val(),
        duration_unit: $('#soc-cd-duration-unit').val(),
        label: $('#soc-cd-label').val().trim(),
        units: units,
        style: radio('soc-cd-style'),
        align: $('#soc-cd-align').val(),
        expired_action: radio('soc-cd-expired-action'),
        expired_text: $('#soc-cd-expired-text').val().trim(),
        text_color: $('#soc-cd-text-value').val(),
        bg_color: $('#soc-cd-bg-value').val(),
      };

      $btn.prop('disabled', true);
      SOC.showLoading();

      SOC.ajax(
        'soc_countdown_save',
        { slug: $('#soc-cd-slug').val(), timer: timer },
        function () {
          SOC.showSuccess('Timer saved.');
          SOC.reloadPanel('countdown-timers');
        },
        function (msg) {
          SOC.showError(msg);
          $btn.prop('disabled', false);
        }
      );
    });
  };

  // ----------------------------------------------------------------
  // Google Reviews module
  // ----------------------------------------------------------------
  SOC.bindGoogleReviews = function () {
    // Namespaced so re-running this on every SOC.init() (e.g. after
    // reloadPanel()) replaces the previous bindings instead of stacking a
    // new set on top of them — without this, every click fires once per
    // past panel reload (e.g. two "Sync Now" clicks → two overlapping
    // syncs, the second always rejected by the server-side lock).
    const ns = '.socGoogleReviews';
    $(document).off(ns);

    // OAuth client
    $(document).on('click' + ns, '#soc-gr-save', function () {
      const $btn = $(this);
      const client_id = $('#soc-gr-client-id').val().trim();
      const client_secret = $('#soc-gr-client-secret').val().trim();

      $btn.prop('disabled', true);
      SOC.showLoading();

      SOC.ajax(
        'soc_google_reviews_save',
        {
          client_id: client_id,
          client_secret: client_secret,
        },
        function () {
          SOC.showSuccess('Settings saved.');
          SOC.reloadPanel('google-reviews');
        },
        function (msg) {
          SOC.showError(msg);
          $btn.prop('disabled', false);
        }
      );
    });

    // Disconnect
    $(document).on('click' + ns, '#soc-gr-disconnect', function () {
      if (!confirm('Disconnect from Google Business Profile?')) return;
      const $btn = $(this);
      $btn.prop('disabled', true);
      SOC.showLoading();

      SOC.ajax(
        'soc_google_reviews_disconnect',
        {},
        function () {
          SOC.showSuccess('Disconnected.');
          SOC.reloadPanel('google-reviews');
        },
        function (msg) {
          SOC.showError(msg);
          $btn.prop('disabled', false);
        }
      );
    });

    // Discovery: accounts
    $(document).on('click' + ns, '#soc-gr-load-accounts', function () {
      const $btn = $(this);
      const $select = $('#soc-gr-account-select');

      $btn.prop('disabled', true);
      SOC.showLoading();

      SOC.ajax(
        'soc_google_reviews_list_accounts',
        {},
        function (data) {
          $select.empty();
          (data.accounts || []).forEach(function (account) {
            $select.append($('<option>', { value: account.id, text: account.name }));
          });
          SOC.showSuccess((data.accounts || []).length + ' account(s) loaded.');
          $btn.prop('disabled', false);
        },
        function (msg) {
          SOC.showError(msg);
          $btn.prop('disabled', false);
        }
      );
    });

    // Discovery: locations
    $(document).on('click' + ns, '#soc-gr-load-locations', function () {
      const $btn = $(this);
      const $select = $('#soc-gr-location-select');
      const accountId = $('#soc-gr-account-select').val();

      if (!accountId) {
        SOC.showError('Load and select an account first.');
        return;
      }

      $btn.prop('disabled', true);
      SOC.showLoading();

      SOC.ajax(
        'soc_google_reviews_list_locations',
        { account_id: accountId },
        function (data) {
          $select.empty();
          (data.locations || []).forEach(function (location) {
            $select.append($('<option>', { value: location.id, text: location.name }));
          });
          SOC.showSuccess((data.locations || []).length + ' location(s) loaded.');
          $btn.prop('disabled', false);
        },
        function (msg) {
          SOC.showError(msg);
          $btn.prop('disabled', false);
        }
      );
    });

    // Save chosen account/location
    $(document).on('click' + ns, '#soc-gr-save-account', function () {
      const $btn = $(this);
      const accountId = $('#soc-gr-account-select').val() || '';
      const locationId = $('#soc-gr-location-select').val() || '';

      $btn.prop('disabled', true);
      SOC.showLoading();

      SOC.ajax(
        'soc_google_reviews_save_account',
        {
          account_id: accountId,
          location_id: locationId,
        },
        function () {
          SOC.showSuccess('Account/location saved.');
          SOC.reloadPanel('google-reviews');
        },
        function (msg) {
          SOC.showError(msg);
          $btn.prop('disabled', false);
        }
      );
    });

    // Places API key (fallback)
    $(document).on('click' + ns, '#soc-gr-places-key-edit', function () {
      $(this).hide();
      $(this).closest('td').find('.soc-masked-key').hide();
      $('#soc-gr-places-api-key').show().trigger('focus');
    });

    $(document).on('click' + ns, '#soc-gr-save-places-key', function () {
      const $btn = $(this);
      const api_key = $('#soc-gr-places-api-key').val().trim();

      $btn.prop('disabled', true);
      SOC.showLoading();

      SOC.ajax(
        'soc_google_reviews_save_places_key',
        {
          api_key: api_key,
        },
        function () {
          SOC.showSuccess('Settings saved.');
          SOC.reloadPanel('google-reviews');
        },
        function (msg) {
          SOC.showError(msg);
          $btn.prop('disabled', false);
        }
      );
    });

    // Test
    $(document).on('click' + ns, '#soc-gr-test', function () {
      const $btn = $(this);
      const $result = $('#soc-gr-test-result');
      const place_id = $('#soc-gr-test-place-id').val().trim();

      $btn.prop('disabled', true);
      $result.text('Testing…').css('color', '');
      SOC.showLoading();

      SOC.ajax(
        'soc_google_reviews_test',
        { place_id: place_id },
        function (data) {
          $result.text('✓ OK (' + data.source + ') — ' + (data.total_review_count || 0) + ' review(s)').css('color', '#00a32a');
          SOC.showSuccess('Connection test complete.');
          $btn.prop('disabled', false);
        },
        function (msg) {
          $result.text('✗ ' + msg).css('color', '#d63638');
          SOC.showError(msg);
          $btn.prop('disabled', false);
        }
      );
    });

    // Full review history sync (keyword filtering) — chunked, one Business
    // Profile page per AJAX round-trip, driven from here instead of WP
    // Cron. WP Cron's background event depends on a loopback request that
    // some hosts block or never trigger (WAF, staging Basic Auth,
    // DISABLE_WP_CRON with no real system cron) — this loop only needs
    // normal admin-ajax requests to keep succeeding, same as any other
    // SOC action.
    $(document).on('click' + ns, '#soc-gr-sync-now', function () {
      const $btn = $(this);
      const $stopBtn = $('#soc-gr-sync-stop');
      // The top-of-panel notice (#soc-action-notice) scrolls out of
      // view once the page is long enough that the user has to scroll
      // down to reach these buttons — updating it alone made the sync
      // look stuck with no visible progress. Mirror it here, right next
      // to the buttons the user is actually looking at.
      const $progress = $('#soc-gr-sync-progress');

      // Business Profile pages are a fixed 20 reviews each (see
      // get_reviews_business_profile()) — the previous sync's cached
      // count is the only signal we have for how many pages this run
      // will likely need, so use it as a rough ETA estimate. Nothing to
      // go on for a first-ever sync, so no ETA is shown until page 2+
      // (elapsed time / pages so far gives a live average instead).
      const REVIEWS_PER_PAGE = 20;
      const prevCount = parseInt($btn.data('prev-count'), 10) || 0;
      const estimatedPages = prevCount > 0 ? Math.max(1, Math.ceil(prevCount / REVIEWS_PER_PAGE)) : 0;
      const startedAt = Date.now();

      SOC._grSyncActive = true;
      $btn.hide();
      $stopBtn.prop('disabled', false).show();
      $progress.text('Starting…');
      SOC.showLoading();

      const formatSeconds = function (seconds) {
        seconds = Math.max(0, Math.round(seconds));
        if (seconds < 60) return seconds + 's';
        return Math.floor(seconds / 60) + 'm ' + (seconds % 60) + 's';
      };

      const progressMessage = function (result) {
        const elapsedMs = Date.now() - startedAt;
        const avgMsPerPage = elapsedMs / result.pages_done;
        let suffix = '';

        if (estimatedPages > result.pages_done) {
          const remainingSec = (avgMsPerPage * (estimatedPages - result.pages_done)) / 1000;
          suffix = ' — est. ' + formatSeconds(remainingSec) + ' remaining';
        } else if (!estimatedPages) {
          suffix = ' — ' + formatSeconds(elapsedMs / 1000) + ' elapsed';
        }

        return 'Syncing… page ' + result.pages_done + ' (' + result.reviews_so_far + ' reviews so far)' + suffix;
      };

      const onStep = function (result) {
        if (!SOC._grSyncActive) {
          return; // stopped by the user — ignore any in-flight response
        }

        if (result && result.done) {
          SOC._grSyncActive = false;
          $progress.text('');
          SOC.showSuccess('Sync complete — ' + result.total + ' review(s) cached.');
          SOC.reloadPanel('google-reviews');
          return;
        }

        const message = progressMessage(result);
        $progress.text(message);
        SOC.showNotice('loading', message);
        SOC.ajax('soc_google_reviews_sync_step', { page_token: result.next_page_token }, onStep, function (msg) {
          SOC._grSyncActive = false;
          $progress.text('');
          SOC.showError(msg);
          SOC.reloadPanel('google-reviews');
        });
      };

      SOC.ajax('soc_google_reviews_sync_now', {}, onStep, function (msg) {
        SOC._grSyncActive = false;
        $progress.text('');
        SOC.showError(msg);
        $btn.show();
        $stopBtn.hide();
      });
    });

    // Stop button — cancels the client-side loop immediately and tells the
    // server to drop the lock/in-progress pages so "Sync Now" is clickable
    // again right away, whether the sync is running in this tab or another.
    $(document).on('click' + ns, '#soc-gr-sync-stop', function () {
      const $btn = $(this);
      $btn.prop('disabled', true);
      SOC._grSyncActive = false;
      $('#soc-gr-sync-progress').text('Stopping…');

      SOC.ajax(
        'soc_google_reviews_sync_stop',
        {},
        function () {
          SOC.showSuccess('Sync stopped.');
          SOC.reloadPanel('google-reviews');
        },
        function (msg) {
          SOC.showError(msg);
          $btn.prop('disabled', false);
        }
      );
    });

    // Minimum star rating
    $(document).on('click' + ns, '#soc-gr-save-min-rating', function () {
      const $btn = $(this);
      const minRating = $('#soc-gr-min-rating').val();

      $btn.prop('disabled', true);
      SOC.showLoading();

      SOC.ajax(
        'soc_google_reviews_save_min_rating',
        { min_rating: minRating },
        function () {
          SOC.showSuccess('Settings saved.');
          SOC.reloadPanel('google-reviews');
        },
        function (msg) {
          SOC.showError(msg);
          $btn.prop('disabled', false);
        }
      );
    });
  };

  // ----------------------------------------------------------------
  // Lead Delivery module
  // ----------------------------------------------------------------
  SOC.bindLeadDelivery = function () {
    if (!$('#soc-lead-save-settings').length) {
      return;
    }

    // Toggle API rows when delivery method changes.
    $('input[name="adf_delivery_method"]').on('change', function () {
      var method = $(this).val();
      var showApi = method === 'api' || method === 'both';
      $('.soc-lead-api-row').toggle(showApi);
      $('.soc-lead-api-only-row').toggle(method === 'api');
    });

    // Show/hide notify email row.
    $('#soc-lead-notify-admin').on('change', function () {
      $('#soc-lead-notify-email-row').toggle(this.checked);
    });

    // Show/hide secret key input.
    $('#soc-lead-key-edit').on('click', function () {
      $('#soc-lead-key-masked').hide();
      $(this).hide();
      $('#soc-lead-key-input').show().trigger('focus');
    });

    // Save settings.
    $('#soc-lead-save-settings').on('click', function () {
      var $btn = $(this);
      $btn.prop('disabled', true);

      SOC.ajax(
        'soc_lead_settings_save',
        {
          delivery_method: $('input[name="adf_delivery_method"]:checked').val() || 'email',
          api_endpoint: $('#soc-lead-endpoint').val().trim(),
          dealer_id: $('#soc-lead-dealer-id').val().trim(),
          secret_key: $('#soc-lead-key-input').val().trim(),
          timeout: parseInt($('#soc-lead-timeout').val(), 10) || 10,
          fallback_email: $('#soc-lead-fallback').is(':checked') ? 1 : 0,
          site_name: $('#soc-lead-site-name').val().trim(),
          notify_admin: $('#soc-lead-notify-admin').is(':checked') ? 1 : 0,
          notify_email: $('#soc-lead-notify-email').val().trim(),
          max_retries: parseInt($('#soc-lead-max-retries').val(), 10) || 0,
          dedup_minutes: parseInt($('#soc-lead-dedup').val(), 10) || 0,
          wpforms_form_ids: $('#soc-lead-wpforms-ids').val().trim(),
        },
        function (data) {
          SOC.showSuccess('Settings saved.');
          $btn.prop('disabled', false);
          if (data.api_configured) {
            $('.soc-badge.soc-badge--warn').first().removeClass('soc-badge--warn').addClass('soc-badge--ok').text('Configured');
          }
          $('#soc-lead-key-input').val('').hide();
          $('#soc-lead-key-masked').show();
          $('#soc-lead-key-edit').show();
        },
        function (msg) {
          SOC.showError(msg);
          $btn.prop('disabled', false);
        }
      );
    });

    // Test API connection.
    $('#soc-lead-test-connection').on('click', function () {
      var $btn = $(this);
      var $result = $('#soc-lead-test-result');

      $btn.prop('disabled', true);
      $result.text('Testing…').removeClass('is-ok is-fail');

      SOC.ajax(
        'soc_lead_test_connection',
        {},
        function (data) {
          $result.text('✓ OK (HTTP ' + (data.response_code || '2xx') + ')').addClass('is-ok');
          $btn.prop('disabled', false);
        },
        function (msg) {
          $result.text('✗ ' + msg).addClass('is-fail');
          $btn.prop('disabled', false);
        }
      );
    });

    // Filter log.
    $('#soc-lead-filter-btn').on('click', function () {
      SOC.loadLeadLog($('#soc-lead-filter-status').val(), 1, $('#soc-lead-filter-search').val());
    });
    $('#soc-lead-filter-search').on('keydown', function (e) {
      if (e.key === 'Enter') {
        e.preventDefault();
        SOC.loadLeadLog($('#soc-lead-filter-status').val(), 1, $(this).val());
      }
    });

    // Paginate — delegated because the table is replaced by AJAX.
    $(document).on('click', '.soc-lead-page-btn', function () {
      var page = parseInt($(this).data('page'), 10) || 1;
      var status = $('#soc-lead-filter-status').val() || 'all';
      var search = $('#soc-lead-filter-search').val();
      SOC.loadLeadLog(status, page, search);
    });

    // Retry button — delegated.
    $(document).on('click', '.soc-lead-retry-btn', function () {
      var $btn = $(this);
      var log_id = parseInt($btn.data('log-id'), 10);

      if (!log_id) {
        return;
      }

      $btn.prop('disabled', true).text('…');

      SOC.ajax(
        'soc_lead_retry',
        { log_id: log_id },
        function () {
          SOC.showSuccess('Lead re-sent.');
          SOC.loadLeadLog($('#soc-lead-filter-status').val() || 'all', 1, $('#soc-lead-filter-search').val());
        },
        function (msg) {
          SOC.showError(msg);
          $btn.prop('disabled', false).text('Retry');
        }
      );
    });

    // Details button — open modal with error + raw response.
    $(document).on('click', '.soc-lead-detail-btn', function () {
      var $btn = $(this);
      // Use .attr(), not .data() — jQuery's .data() auto-parses
      // JSON-looking attribute values into objects, which breaks
      // the JSON.parse() below and prints "[object Object]".
      var response = $btn.attr('data-response') || '';
      var payload = $btn.attr('data-payload') || '';
      var pretty = response;
      try {
        var parsed = JSON.parse(response);
        pretty = JSON.stringify(parsed, null, 2);
      } catch (e) {
        /* not JSON, use raw */
      }

      $('#soc-modal-name').text($btn.data('name') || '—');
      $('#soc-modal-time').text($btn.data('time') || '—');
      $('#soc-modal-code').text($btn.data('code') || '—');
      $('#soc-modal-error').text($btn.data('error') || '—');
      $('#soc-modal-payload').text(payload || '(no payload stored)');
      $('#soc-modal-response').text(pretty || '(empty)');

      SOC.openLeadModal();
    });

    // Modal close.
    $(document).on('click', '.soc-lead-modal__close, .soc-lead-modal__backdrop', function () {
      SOC.closeLeadModal();
    });
    $(document).on('keydown', function (e) {
      if (e.key === 'Escape') {
        SOC.closeLeadModal();
      }
    });
  };

  SOC.openLeadModal = function () {
    $('#soc-lead-modal').fadeIn(150);
    $('body').addClass('soc-modal-open');
  };

  SOC.closeLeadModal = function () {
    $('#soc-lead-modal').fadeOut(150);
    $('body').removeClass('soc-modal-open');
  };

  SOC.loadLeadLog = function (status, page, search) {
    var $wrap = $('#soc-lead-log-wrap');
    $wrap.css('opacity', 0.5);

    SOC.ajax(
      'soc_lead_log_filter',
      { status: status || 'all', page: page || 1, search: search || '' },
      function (data) {
        $wrap.html(data.html).css('opacity', 1);
      },
      function (msg) {
        $wrap.css('opacity', 1);
        SOC.showError(msg);
      }
    );
  };

  // ----------------------------------------------------------------
  // Boot
  // ----------------------------------------------------------------
  $(document).ready(function () {
    SOC.init();
  });
})(jQuery);
