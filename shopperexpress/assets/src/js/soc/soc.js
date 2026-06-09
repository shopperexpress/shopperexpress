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

    // ----------------------------------------------------------------
    // Init
    // ----------------------------------------------------------------
    SOC.init = function () {
        SOC.bindRefresh();
        SOC.bindCronActions();
        SOC.bindCacheActions();
        SOC.bindCachePtActions();
        SOC.bindLogPagination();
        SOC.bindApiTest();
        SOC.bindDbCleanup();
        SOC.bindApiSettings();
    };

    // ----------------------------------------------------------------
    // AJAX helper
    // ----------------------------------------------------------------
    SOC.ajax = function (action, data, successCb, errorCb) {
        $.post(
            socData.ajaxUrl,
            Object.assign({ action: action, nonce: socData.nonce }, data)
        )
        .done(function (res) {
            if (res && res.success) {
                if (typeof successCb === 'function') successCb(res.data);
            } else {
                const msg = (res && res.data && res.data.message)
                    ? res.data.message
                    : socData.strings.error;
                if (typeof errorCb === 'function') errorCb(msg);
                else SOC.showError(msg);
            }
        })
        .fail(function () {
            const msg = socData.strings.error;
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

    SOC.showError   = function (msg) { SOC.showNotice('error',   msg || socData.strings.error); };
    SOC.showSuccess = function (msg) { SOC.showNotice('success', msg || socData.strings.success); };
    SOC.showLoading = function ()    { SOC.showNotice('loading', socData.strings.loading); };

    // ----------------------------------------------------------------
    // Dynamic panel reload
    // Fetches rendered module HTML from server and swaps #soc-panel-body.
    // Re-runs SOC.init() so all event bindings work in the new markup.
    // ----------------------------------------------------------------
    SOC.reloadPanel = function (module, cb) {
        SOC.showLoading();
        SOC.ajax('soc_load_panel', { module: module }, function (data) {
            if (data && data.html) {
                $('#soc-panel-body').html(data.html);
                SOC.init();          // re-bind all handlers to new DOM
                SOC.showSuccess('Refreshed.');
                if (typeof cb === 'function') cb();
            }
        });
    };

    SOC.bindRefresh = function () {
        $(document).on('click', '.soc-refresh-btn', function () {
            const module = $(this).closest('[data-module]').data('module')
                        || $('#soc-panel').data('module');
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
            let args   = $btn.data('args') || [];
            if (typeof args === 'string') {
                try { args = JSON.parse(args); } catch (e) { args = []; }
            }
            $btn.prop('disabled', true);
            SOC.showLoading();
            SOC.ajax('soc_run_cron', { hook: hook, args: args }, function (data) {
                SOC.showSuccess('Cron "' + hook + '" executed at ' + (data.ran_at || ''));
                $btn.prop('disabled', false);
            }, function (msg) {
                SOC.showError(msg);
                $btn.prop('disabled', false);
            });
        });

        $(document).on('click', '.soc-delete-cron', function () {
            if (!confirm(socData.strings.confirm_delete)) return;
            const $btn      = $(this);
            const hook      = $btn.data('hook');
            const timestamp = $btn.data('timestamp');
            $btn.prop('disabled', true);
            SOC.showLoading();
            SOC.ajax('soc_delete_cron', { hook: hook, timestamp: timestamp }, function () {
                SOC.showSuccess('Cron "' + hook + '" deleted.');
                $btn.closest('tr').fadeOut(300, function () { $(this).remove(); });
            }, function (msg) {
                SOC.showError(msg);
                $btn.prop('disabled', false);
            });
        });

        $(document).on('click', '.soc-reschedule-cron', function () {
            const $btn       = $(this);
            const $row       = $btn.closest('tr');
            const hook       = $btn.data('hook');
            const timestamp  = $btn.data('timestamp');
            const $select    = $row.find('.soc-schedule-select');
            const recurrence = $select.val();
            const args       = $row.find('.soc-run-cron').data('args') || '[]';
            $btn.prop('disabled', true);
            SOC.showLoading();
            SOC.ajax('soc_reschedule_cron', { hook: hook, timestamp: timestamp, recurrence: recurrence, args: args }, function (data) {
                SOC.showSuccess('Cron "' + hook + '" rescheduled as ' + recurrence + '.');
                $row.find('td:nth-child(3)').text(recurrence);
                $btn.data('timestamp', data.new_timestamp || timestamp);
                $btn.prop('disabled', false);
            }, function (msg) {
                SOC.showError(msg);
                $btn.prop('disabled', false);
            });
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
            SOC.ajax('soc_clear_cache', {}, function () {
                SOC.showSuccess('All cache cleared.');
                $btn.prop('disabled', false);
            }, function (msg) {
                SOC.showError(msg);
                $btn.prop('disabled', false);
            });
        });

        $(document).on('click', '#soc-clear-object-cache', function () {
            const $btn = $(this);
            $btn.prop('disabled', true);
            SOC.showLoading();
            SOC.ajax('soc_clear_object_cache', {}, function () {
                SOC.showSuccess('Object cache flushed.');
                $btn.prop('disabled', false);
            }, function (msg) {
                SOC.showError(msg);
                $btn.prop('disabled', false);
            });
        });

        $(document).on('click', '#soc-clear-transients', function () {
            const $btn = $(this);
            $btn.prop('disabled', true);
            SOC.showLoading();
            SOC.ajax('soc_clear_transients', {}, function (data) {
                const deleted = (data && data.deleted_rows !== undefined) ? data.deleted_rows : '?';
                SOC.showSuccess('Deleted ' + deleted + ' transients.');
                $btn.prop('disabled', false);
            }, function (msg) {
                SOC.showError(msg);
                $btn.prop('disabled', false);
            });
        });
    };

    // ----------------------------------------------------------------
    // Cache — post-type actions (cache-manager view)
    // ----------------------------------------------------------------
    const statusBadge = {
        valid:   { cls: 'soc-badge--ok',      label: 'Valid' },
        stale:   { cls: 'soc-badge--warn',     label: 'Stale' },
        expired: { cls: 'soc-badge--fail',     label: 'Expired' },
        missing: { cls: 'soc-badge--neutral',  label: 'Missing' },
    };

    // Timezone-safe relative time — age computed server-side, JS adds elapsed.
    const pageLoadedAt = Date.now();

    SOC.formatAge = function (secs) {
        if (secs < 60)    return 'just now';
        if (secs < 3600)  return Math.floor(secs / 60) + ' min ago';
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
        const limit  = maxAttempts || 20;

        const timer = setInterval(function () {
            attempts++;

            $.post(socData.ajaxUrl, {
                action:    'soc_pt_cache_status',
                post_type: pt,
                nonce:     socData.nonce,
            }).done(function (res) {
                const d      = res.data;
                const status = d.status;
                const def    = statusBadge[status] || statusBadge.missing;

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
            const $btn            = $(this);
            const pt              = $btn.data('pt');
            const row             = $btn.closest('tr');
            const badge           = row.find('.soc-badge');
            const generatedCell   = row.find('.soc-pt-generated');
            const generatedByCell = row.find('.soc-pt-generated-by');

            $btn.prop('disabled', true).text('Starting…');
            SOC.showLoading();

            SOC.ajax('soc_regen_pt_cache', { post_type: pt }, function () {
                SOC.showNotice('loading', 'Regenerating "' + pt + '"…');
                badge.attr('class', 'soc-badge soc-badge--neutral').text('Regenerating…');
                SOC.pollUntilValid(pt, badge, generatedCell, generatedByCell, 20);
                $btn.prop('disabled', false).text('Regenerate');
            }, function (msg) {
                SOC.showError(msg);
                $btn.prop('disabled', false).text('Regenerate');
            });
        });
    };

    // ----------------------------------------------------------------
    // API test tool
    // ----------------------------------------------------------------
    SOC.bindApiTest = function () {
        $(document).on('click', '#soc-test-api-btn', function () {
            const $btn = $(this);
            const url  = $('#soc-test-api-url').val().trim();
            if (!url) return;
            $btn.prop('disabled', true);
            SOC.showLoading();
            SOC.ajax('soc_test_api', { url: url }, function (data) {
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
            }, function (msg) {
                SOC.showError(msg);
                $btn.prop('disabled', false);
            });
        });
    };

    // ----------------------------------------------------------------
    // DB cleanup
    // ----------------------------------------------------------------
    SOC.bindDbCleanup = function () {
        $(document).on('click', '.soc-db-cleanup', function () {
            if (!confirm(socData.strings.confirm_delete)) return;
            const $btn  = $(this);
            const type  = $btn.data('cleanup-type');
            const $row  = $btn.closest('.soc-cleanup-row');
            $btn.prop('disabled', true);
            SOC.showLoading();
            SOC.ajax('soc_db_cleanup', { type: type }, function (data) {
                const deleted = (data && data.deleted !== undefined) ? parseInt(data.deleted, 10) : 0;
                if (deleted > 0) {
                    SOC.showSuccess('Cleaned up ' + deleted + ' ' + type + ' rows.');
                    $row.find('.soc-cleanup-count').text('0');
                    $btn.prop('disabled', true).text('Done');
                } else {
                    SOC.showSuccess('Nothing to clean up for ' + type + '.');
                    $btn.prop('disabled', false);
                }
            }, function (msg) {
                SOC.showError(msg);
                $btn.prop('disabled', false);
            });
        });
    };

    // ----------------------------------------------------------------
    // Cache Activity Log — client-side pagination
    // ----------------------------------------------------------------
    SOC.bindLogPagination = function () {
        const $pager = $('#soc-log-pagination');
        if ( ! $pager.length ) return;

        const $dataEl  = $('#soc-cache-log-data');
        if ( ! $dataEl.length ) return;

        const allRows  = JSON.parse($dataEl.text() || '[]');
        const perPage  = parseInt($pager.data('per-page'), 10) || 10;
        const pages    = parseInt($pager.data('pages'), 10) || 1;
        let   current  = 1;

        function renderPage(page) {
            current = page;
            const start = (page - 1) * perPage;
            const slice = allRows.slice(start, start + perPage);

            const rows = slice.map(function (e) {
                const time     = e.time     || '';
                const action   = e.action   ? e.action.charAt(0).toUpperCase() + e.action.slice(1) : '';
                const postType = e.post_type || '—';
                const user     = e.user     || '';
                return '<tr>' +
                    '<td>' + $('<span>').text(time).html()     + '</td>' +
                    '<td>' + $('<span>').text(action).html()   + '</td>' +
                    '<td><code>' + $('<span>').text(postType).html() + '</code></td>' +
                    '<td>' + $('<span>').text(user).html()     + '</td>' +
                    '</tr>';
            }).join('');

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

        // Mode toggle
        $(document).on('change', '#soc-api-mode-toggle', function () {
            const enabled = $(this).is(':checked') ? 1 : 0;
            const $card   = $(this).closest('.soc-api-mode-card');
            const $label  = $('#soc-api-mode-label');
            const $badge  = $('#soc-api-mode-badge');
            const $dot    = $card.find('.soc-api-mode-indicator');

            SOC.showLoading();

            SOC.ajax('soc_api_mode_toggle', { enabled: enabled }, function (data) {
                const on = data && data.enabled;

                $card.toggleClass('soc-api-mode-card--api', !!on)
                     .toggleClass('soc-api-mode-card--wp', !on);
                $dot.toggleClass('soc-api-mode-indicator--on', !!on);
                $label.text(on ? 'API Mode (Intice)' : 'WordPress Mode');
                $badge.attr('class', 'soc-badge ' + (on ? 'soc-badge--ok' : 'soc-badge--neutral'))
                      .text(on ? 'ACTIVE' : 'INACTIVE');

                SOC.showSuccess('Mode ' + (on ? 'enabled' : 'disabled') + '.');
            });
        });

        // Show key input on "Change" click
        $(document).on('click', '#soc-api-key-edit', function () {
            $(this).hide();
            $(this).closest('td').find('.soc-masked-key').hide();
            $('#soc-intice-api-key').show().trigger('focus');
        });

        // Save credentials
        $(document).on('click', '#soc-save-api-credentials', function () {
            const $btn = $(this);
            const url  = $('#soc-intice-api-url').val().trim();
            const key  = $('#soc-intice-api-key').val().trim();

            if (!url) {
                SOC.showError('API URL is required.');
                return;
            }

            $btn.prop('disabled', true);
            SOC.showLoading();

            SOC.ajax('soc_api_save_credentials', { api_url: url, api_key: key }, function () {
                SOC.showSuccess('Credentials saved.');
                $btn.prop('disabled', false);
                // Hide input, reload panel to refresh masked key display
                if (key) {
                    SOC.reloadPanel('api-settings');
                }
            }, function (msg) {
                SOC.showError(msg);
                $btn.prop('disabled', false);
            });
        });

        // Cache enable / disable toggle
        $(document).on('change', '#soc-intice-cache-toggle', function () {
            const enabled  = $(this).is(':checked') ? 1 : 0;
            const $card    = $(this).closest('.soc-api-mode-card');
            const $label   = $('#soc-cache-label');
            const $badge   = $('#soc-cache-badge');
            const $dot     = $('#soc-cache-indicator');

            SOC.showLoading();

            SOC.ajax('soc_intice_cache_toggle', { enabled: enabled }, function (data) {
                const on = data && data.enabled;

                $card.toggleClass('soc-api-mode-card--api', !!on)
                     .toggleClass('soc-api-mode-card--wp', !on);
                $dot.toggleClass('soc-api-mode-indicator--on', !!on);
                $label.text(on ? 'Cache Enabled' : 'Cache Disabled');
                $badge.attr('class', 'soc-badge ' + (on ? 'soc-badge--ok' : 'soc-badge--neutral'))
                      .text(on ? 'ON' : 'OFF');

                SOC.showSuccess('Intice cache ' + (on ? 'enabled' : 'disabled') + '.');
            }, function (msg) {
                SOC.showError(msg);
                // Revert toggle on error
                $(this).prop('checked', !enabled);
            });
        });

        // Flush all Intice cache
        $(document).on('click', '#soc-flush-all-api-cache', function () {
            if (!confirm('Flush all Intice Nexus cache? Next page loads will re-fetch from the API.')) return;
            const $btn = $(this);
            $btn.prop('disabled', true);
            SOC.showLoading();
            SOC.ajax('soc_flush_api_cache', {}, function () {
                SOC.showSuccess('All Intice cache flushed.');
                SOC.reloadPanel('api-settings');
            }, function (msg) {
                SOC.showError(msg);
                $btn.prop('disabled', false);
            });
        });

        // Flush single cache group
        $(document).on('click', '.soc-flush-api-cache-group', function () {
            const $btn  = $(this);
            const group = $btn.data('group');
            $btn.prop('disabled', true);
            SOC.showLoading();
            SOC.ajax('soc_flush_api_cache_group', { group: group }, function (data) {
                SOC.showSuccess('Cache group "' + group + '" flushed (' + (data.deleted || 0) + ' rows).');
                SOC.reloadPanel('api-settings');
            }, function (msg) {
                SOC.showError(msg);
                $btn.prop('disabled', false);
            });
        });

        // Test connection
        $(document).on('click', '#soc-test-intice-api', function () {
            const $btn    = $(this);
            const $result = $('#soc-connection-result');

            $btn.prop('disabled', true);
            $result.text('Testing…').css('color', '');
            SOC.showLoading();

            SOC.ajax('soc_api_test_connection', {}, function (data) {
                const ok = data && data.ok;
                $result.text(
                    ok
                        ? '✓ ' + (data.status || 'OK') + ' — ' + (data.ms || 0) + ' ms'
                        : '✗ ' + (data.error || data.status || 'Error')
                ).css('color', ok ? '#00a32a' : '#d63638');
                SOC.showSuccess('Connection test complete.');
                $btn.prop('disabled', false);
            }, function (msg) {
                $result.text('✗ ' + msg).css('color', '#d63638');
                SOC.showError(msg);
                $btn.prop('disabled', false);
            });
        });
    };

    // ----------------------------------------------------------------
    // Boot
    // ----------------------------------------------------------------
    $(document).ready(function () {
        SOC.init();
    });

})(jQuery);
