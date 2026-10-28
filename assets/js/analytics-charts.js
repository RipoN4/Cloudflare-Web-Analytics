/**
 * Cloudflare Edge Analytics - Interactive Scripts
 * Pure vanilla JavaScript - Zero external library dependencies
 */

(function () {
    'use strict';

    function initAll() {
        initDurationDropdown();
        init24hChartTracking();
        init30dBarChartHover();
        initRefreshButton();
        initPasswordToggle();
        initToggleTokenEdit();
        initResetButton();
        initSitePickerModal();
    }

    // Ensure initialization runs whether DOMContentLoaded has already fired or not
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', initAll);
    } else {
        initAll();
    }
    window.addEventListener('load', initAll);

    /**
     * 1. Last 24 Hours Area Line Graph - Dynamic Hover Engine
     */
    function init24hChartTracking() {
        var wrappers = document.querySelectorAll('.cf-chart-interactive-wrapper');
        if (!wrappers.length) {
            return;
        }

        wrappers.forEach(function (wrapper) {
            // Prevent duplicate bindings
            if (wrapper.dataset.chartBound === 'true') {
                return;
            }
            wrapper.dataset.chartBound = 'true';

            var svg = wrapper.querySelector('.cf-spline-svg');
            var dataScript = wrapper.querySelector('script[type="application/json"]');
            var tooltip = wrapper.querySelector('.cf-spline-tooltip');
            var tooltipTime = wrapper.querySelector('.cf-spline-tooltip-time') || wrapper.querySelector('#cf-spline-tooltip-time');
            var tooltipNum = wrapper.querySelector('.cf-spline-tooltip-num') || wrapper.querySelector('#cf-spline-tooltip-num');
            var cursorGroup = wrapper.querySelector('.cf-cursor-group');
            var cursorLine = wrapper.querySelector('#cf-cursor-vline') || wrapper.querySelector('.cf-cursor-vline');
            var cursorDot = wrapper.querySelector('#cf-cursor-dot') || wrapper.querySelector('.cf-cursor-dot');
            var cursorDotGlow = wrapper.querySelector('#cf-cursor-dot-glow') || wrapper.querySelector('.cf-cursor-dot-glow');

            if (!svg || !dataScript || !tooltip || !cursorGroup) {
                return;
            }

            var points = [];
            try {
                points = JSON.parse(dataScript.textContent.trim());
            } catch (e) {
                return;
            }

            if (!Array.isArray(points) || points.length === 0) {
                return;
            }

            var viewBoxAttr = svg.getAttribute('viewBox') || '0 0 900 240';
            var vbParts = viewBoxAttr.split(' ');
            var vbWidth = parseFloat(vbParts[2]) || 900;

            function onMouseMove(e) {
                var rect = wrapper.getBoundingClientRect();
                var mouseX = e.clientX - rect.left;

                if (mouseX < 0 || mouseX > rect.width) {
                    onMouseLeave();
                    return;
                }

                var scaleX = vbWidth / rect.width;
                var svgX = mouseX * scaleX;

                var closest = points[0];
                var minDiff = Math.abs(points[0].x - svgX);

                for (var i = 1; i < points.length; i++) {
                    var diff = Math.abs(points[i].x - svgX);
                    if (diff < minDiff) {
                        minDiff = diff;
                        closest = points[i];
                    }
                }

                cursorGroup.style.display = 'block';
                tooltip.style.display = 'inline-flex';
                tooltip.style.opacity = '1';

                if (cursorLine) {
                    cursorLine.setAttribute('x1', closest.x);
                    cursorLine.setAttribute('x2', closest.x);
                }

                if (cursorDot) {
                    cursorDot.setAttribute('cx', closest.x);
                    cursorDot.setAttribute('cy', closest.y);
                }

                if (cursorDotGlow) {
                    cursorDotGlow.setAttribute('cx', closest.x);
                    cursorDotGlow.setAttribute('cy', closest.y);
                }

                if (tooltipTime) {
                    tooltipTime.textContent = closest.full_label || closest.time_label;
                }
                if (tooltipNum) {
                    tooltipNum.textContent = Number(closest.val).toLocaleString();
                }

                var targetLeftPercent = (closest.x / vbWidth) * 100;
                targetLeftPercent = Math.max(16, Math.min(84, targetLeftPercent));
                tooltip.style.left = targetLeftPercent + '%';
            }

            function onMouseLeave() {
                cursorGroup.style.display = 'none';
                tooltip.style.display = 'none';
            }

            wrapper.addEventListener('mousemove', onMouseMove);
            wrapper.addEventListener('mouseenter', onMouseMove);
            wrapper.addEventListener('mouseleave', onMouseLeave);

            wrapper.addEventListener('touchmove', function (e) {
                if (e.touches.length > 0) {
                    onMouseMove(e.touches[0]);
                }
            }, { passive: true });

            wrapper.addEventListener('touchend', onMouseLeave);
        });
    }

    /**
     * 2. Last 30 Days Candle Bar Chart - Hover Card
     */
    function init30dBarChartHover() {
        var tracks = document.querySelectorAll('.cf-30d-bars-track');
        if (!tracks.length) {
            return;
        }

        tracks.forEach(function (track) {
            if (track.dataset.chartBound === 'true') {
                return;
            }
            track.dataset.chartBound = 'true';

            var tooltip = track.querySelector('.cf-bar-tooltip') || track.querySelector('#cf-bar-tooltip');
            var dateEl = track.querySelector('.cf-bar-tooltip-date') || track.querySelector('#cf-bar-tooltip-date');
            var visitorsEl = track.querySelector('.cf-bar-tooltip-visitors') || track.querySelector('#cf-bar-tooltip-visitors');
            var viewsEl = track.querySelector('.cf-bar-tooltip-views') || track.querySelector('#cf-bar-tooltip-views');

            if (!tooltip) {
                return;
            }

            var cols = track.querySelectorAll('.cf-candle-col');

            cols.forEach(function (col) {
                function showBarTooltip() {
                    var date = col.getAttribute('data-date') || '';
                    var views = col.getAttribute('data-views') || '0';
                    var uniques = col.getAttribute('data-uniques') || '0';

                    if (dateEl) dateEl.textContent = date;
                    if (visitorsEl) visitorsEl.textContent = uniques;
                    if (viewsEl) viewsEl.textContent = views;

                    tooltip.style.display = 'block';
                    tooltip.style.opacity = '1';

                    var trackRect = track.getBoundingClientRect();
                    var colRect = col.getBoundingClientRect();

                    var colCenter = (colRect.left + colRect.width / 2) - trackRect.left;
                    var trackWidth = trackRect.width;

                    var minClamp = 85;
                    var maxClamp = trackWidth - 85;
                    var clampedCenter = Math.max(minClamp, Math.min(maxClamp, colCenter));

                    tooltip.style.left = clampedCenter + 'px';
                }

                col.addEventListener('mouseenter', showBarTooltip);
                col.addEventListener('mousemove', showBarTooltip);
            });

            track.addEventListener('mouseleave', function () {
                tooltip.style.display = 'none';
            });
        });
    }

    /**
     * Duration Preset Dropdown Menu Handler
     */
    function initDurationDropdown() {
        var dropdown = document.getElementById('cf-duration-dropdown');
        var btn = document.getElementById('cf-duration-btn');
        if (!dropdown || !btn || dropdown.dataset.bound === 'true') {
            return;
        }
        dropdown.dataset.bound = 'true';

        btn.addEventListener('click', function (e) {
            e.preventDefault();
            e.stopPropagation();
            var isOpen = dropdown.classList.toggle('is-open');
            btn.setAttribute('aria-expanded', isOpen ? 'true' : 'false');
        });

        document.addEventListener('click', function (e) {
            if (!dropdown.contains(e.target)) {
                dropdown.classList.remove('is-open');
                btn.setAttribute('aria-expanded', 'false');
            }
        });

        document.addEventListener('keydown', function (e) {
            if (e.key === 'Escape' || e.key === 'Esc') {
                dropdown.classList.remove('is-open');
                btn.setAttribute('aria-expanded', 'false');
            }
        });
    }

    /**
     * 3. Refresh Data Button via AJAX
     */
    function initRefreshButton() {
        var btn = document.getElementById('cf-btn-refresh');
        var icon = document.getElementById('cf-refresh-icon');
        var text = document.getElementById('cf-refresh-text');
        var cacheLabel = document.getElementById('cf-cache-label');

        if (!btn || typeof cfAnalyticsData === 'undefined' || btn.dataset.bound === 'true') {
            return;
        }
        btn.dataset.bound = 'true';

        btn.addEventListener('click', function (e) {
            e.preventDefault();

            if (btn.classList.contains('is-busy')) {
                return;
            }

            btn.classList.add('is-busy');
            if (icon) {
                icon.classList.add('cf-refresh-spin');
            }
            if (text) {
                text.textContent = cfAnalyticsData.i18n.refreshing;
            }

            var period = btn.getAttribute('data-period') || '30d';
            var formData = new FormData();
            formData.append('action', 'cf_analytics_refresh');
            formData.append('nonce', cfAnalyticsData.nonce);
            formData.append('period', period);

            fetch(cfAnalyticsData.ajaxUrl, {
                method: 'POST',
                body: formData,
                credentials: 'same-origin',
            })
                .then(function (response) {
                    return response.json();
                })
                .then(function (json) {
                    if (json.success) {
                        if (text) {
                            text.textContent = cfAnalyticsData.i18n.refreshed;
                        }
                        if (cacheLabel && json.data && json.data.cache_time) {
                            cacheLabel.textContent = 'Cached ' + json.data.cache_time;
                        }
                        setTimeout(function () {
                            window.location.reload();
                        }, 500);
                    } else {
                        var msg = (json.data && json.data.message) ? json.data.message : cfAnalyticsData.i18n.error;
                        alert(msg);
                        btn.classList.remove('is-busy');
                        if (icon) {
                            icon.classList.remove('cf-refresh-spin');
                        }
                        if (text) {
                            text.textContent = 'Refresh Data';
                        }
                    }
                })
                .catch(function () {
                    var fallbackUrl = cfAnalyticsData.refreshUrl + '&period=' + encodeURIComponent(period);
                    window.location.href = fallbackUrl;
                });
        });
    }

    /**
     * 4. Settings Password Visibility Toggle
     */
    function initPasswordToggle() {
        var toggle = document.getElementById('cf-toggle-token');
        var input = document.getElementById('cf_analytics_api_token');

        if (!toggle || !input || toggle.dataset.bound === 'true') {
            return;
        }
        toggle.dataset.bound = 'true';

        toggle.addEventListener('click', function () {
            var icon = toggle.querySelector('.dashicons');
            if (input.type === 'password') {
                input.type = 'text';
                if (icon) {
                    icon.classList.remove('dashicons-visibility');
                    icon.classList.add('dashicons-hidden');
                }
            } else {
                input.type = 'password';
                if (icon) {
                    icon.classList.remove('dashicons-hidden');
                    icon.classList.add('dashicons-visibility');
                }
            }
        });
    }

    /**
     * 5. Settings Auto-Detect Credentials Button
     */
    function initAutoDetectButton() {
        var btn = document.getElementById('cf-btn-autodetect');
        var inputToken = document.getElementById('cf_analytics_api_token');
        var status = document.getElementById('cf-autodetect-status');
        var inputAccount = document.getElementById('cf_analytics_account_id');
        var inputSiteTag = document.getElementById('cf_analytics_site_tag');
        var inputZone = document.getElementById('cf_analytics_zone_id');

        if (!btn || !inputToken || btn.dataset.bound === 'true') {
            return;
        }
        btn.dataset.bound = 'true';

        btn.addEventListener('click', function (e) {
            e.preventDefault();
            var tokenVal = inputToken.value ? inputToken.value.trim() : '';
            if (!tokenVal) {
                if (status) {
                    status.style.color = '#dc2626';
                    status.textContent = 'Please enter an API Token first.';
                }
                inputToken.focus();
                return;
            }

            btn.disabled = true;
            if (status) {
                status.style.color = '#0284c7';
                status.textContent = 'Contacting Cloudflare & detecting properties...';
            }

            var formData = new FormData();
            formData.append('action', 'cf_analytics_autodetect');
            formData.append('nonce', typeof cfAnalyticsData !== 'undefined' ? cfAnalyticsData.nonce : '');
            formData.append('token', tokenVal);

            fetch(typeof cfAnalyticsData !== 'undefined' ? cfAnalyticsData.ajaxUrl : '/wp-admin/admin-ajax.php', {
                method: 'POST',
                body: formData,
                credentials: 'same-origin'
            })
            .then(function (res) { return res.json(); })
            .then(function (res) {
                btn.disabled = false;
                if (res.success && res.data) {
                    if (inputAccount && res.data.account_id) {
                        inputAccount.value = res.data.account_id;
                    }
                    if (inputSiteTag && res.data.site_tag) {
                        inputSiteTag.value = res.data.site_tag;
                    }
                    if (inputZone && res.data.zone_tag) {
                        inputZone.value = res.data.zone_tag;
                    }

                    // Update live connection card labels if present
                    var metaSite = document.getElementById('cf-meta-site-tag');
                    var metaAcc  = document.getElementById('cf-meta-account-id');
                    var metaZone = document.getElementById('cf-meta-zone-id');
                    var metaDomain = document.getElementById('cf-meta-domain');
                    if (metaSite && res.data.site_tag) metaSite.textContent = res.data.site_tag;
                    if (metaAcc && res.data.account_id) metaAcc.textContent = res.data.account_id;
                    if (metaZone && res.data.zone_tag) metaZone.textContent = res.data.zone_tag;
                    if (metaDomain && res.data.host) metaDomain.textContent = res.data.host;

                    if (status) {
                        status.style.color = '#16a34a';
                        var details = [];
                        if (res.data.site_tag) details.push('Site Tag');
                        if (res.data.zone_tag) details.push('Zone ID');
                        if (res.data.account_id) details.push('Account ID');
                        status.textContent = 'Detected & populated: ' + details.join(', ') + '!';
                    }
                } else {
                    if (status) {
                        status.style.color = '#dc2626';
                        status.textContent = (res.data && res.data.message) ? res.data.message : 'Auto-detection failed.';
                    }
                }
            })
            .catch(function (err) {
                btn.disabled = false;
                if (status) {
                    status.style.color = '#dc2626';
                    status.textContent = 'Connection error during auto-detection.';
                }
            });
        });
    }

    /**
     * 5. Toggle Token Input Box in Settings (Change Token button)
     */
    function initToggleTokenEdit() {
        var btn = document.getElementById('cf-btn-toggle-token-edit');
        var box = document.getElementById('cf-token-setup-box');
        var input = document.getElementById('cf_analytics_api_token');
        var label = document.getElementById('cf-edit-token-label');

        if (!btn || !box || btn.dataset.bound === 'true') {
            return;
        }
        btn.dataset.bound = 'true';

        btn.addEventListener('click', function (e) {
            e.preventDefault();
            var isHidden = box.classList.contains('cf-token-hidden');
            if (isHidden) {
                box.classList.remove('cf-token-hidden');
                if (label) {
                    label.textContent = 'Cancel Edit';
                }
                if (input) {
                    input.focus();
                }
            } else {
                box.classList.add('cf-token-hidden');
                if (label) {
                    label.textContent = 'Change Token';
                }
            }
        });
    }

    /**
     * 6. Reset Plugin Button Handler
     */
    function initResetButton() {
        var btn = document.getElementById('cf-btn-reset-plugin');
        var form = document.getElementById('cf-reset-plugin-form');
        if (!btn || !form || btn.dataset.bound === 'true') {
            return;
        }
        btn.dataset.bound = 'true';

        btn.addEventListener('click', function (e) {
            e.preventDefault();
            var confirmed = window.confirm(
                'Are you sure you want to reset all Cloudflare Web Analytics data and settings?\n\n' +
                '• All API Tokens, Account IDs, and Site Tags will be permanently deleted.\n' +
                '• All cached analytics transients will be completely purged.\n' +
                '• The plugin will return to a clean, fresh installation state.\n\n' +
                'Do you want to proceed with the reset?'
            );
            if (confirmed) {
                btn.disabled = true;
                btn.innerHTML = '<span class="cf-spinner" style="width:14px;height:14px;border-width:2px;margin-right:6px;"></span> Resetting...';
                form.submit();
            }
        });
    }

    /**
     * 7. Interactive Property Picker Modal (Connect, Switch Domain & Manual Selection)
     */
    function initSitePickerModal() {
        var modal = document.getElementById('cf-site-picker-modal');
        if (!modal || modal.dataset.bound === 'true') {
            return;
        }
        modal.dataset.bound = 'true';

        var btnConnect = document.getElementById('cf-btn-autodetect');
        var btnSwitch  = document.getElementById('cf-btn-switch-domain');
        var closeBtn   = document.getElementById('cf-modal-close-btn');
        var cancelBtn  = document.getElementById('cf-modal-cancel-btn');
        var confirmBtn = document.getElementById('cf-modal-confirm-btn');
        var backdrop   = document.getElementById('cf-modal-backdrop');
        var searchInput= document.getElementById('cf-modal-search-input');
        var sitesList  = document.getElementById('cf-modal-sites-list');
        var countEl    = document.getElementById('cf-modal-sites-count');
        var statusEl   = document.getElementById('cf-modal-status');
        var tokenInput = document.getElementById('cf_analytics_api_token');

        var allSites = [];
        var selectedSite = null;

        function openModal() {
            var tokenVal = tokenInput ? tokenInput.value.trim() : '';
            if (!tokenVal) {
                alert('Please enter your Cloudflare API Token first.');
                if (tokenInput) {
                    var box = document.getElementById('cf-token-setup-box');
                    if (box && box.classList.contains('cf-token-hidden')) {
                        box.classList.remove('cf-token-hidden');
                    }
                    tokenInput.focus();
                }
                return;
            }

            modal.style.display = 'flex';
            document.body.style.overflow = 'hidden';
            if (searchInput) searchInput.value = '';
            if (statusEl) {
                statusEl.style.display = 'none';
                statusEl.textContent = '';
            }
            if (confirmBtn) confirmBtn.disabled = true;

            // Show loading
            sitesList.innerHTML = '<div class="cf-modal-loading"><span class="cf-spinner"></span><p>Fetching Web Analytics properties from your Cloudflare account...</p></div>';
            if (countEl) countEl.textContent = 'Loading properties...';

            fetchSites(tokenVal);
        }

        function closeModal() {
            modal.style.display = 'none';
            document.body.style.overflow = '';
        }

        function fetchSites(token) {
            var formData = new FormData();
            formData.append('action', 'cf_analytics_get_sites');
            formData.append('nonce', typeof cfAnalyticsData !== 'undefined' ? cfAnalyticsData.nonce : '');
            formData.append('token', token);

            fetch(typeof cfAnalyticsData !== 'undefined' ? cfAnalyticsData.ajaxUrl : '/wp-admin/admin-ajax.php', {
                method: 'POST',
                body: formData,
                credentials: 'same-origin'
            })
            .then(function (res) { return res.json(); })
            .then(function (res) {
                if (res.success && res.data && res.data.sites) {
                    allSites = res.data.sites;
                    renderSites(allSites);
                } else {
                    var msg = (res.data && res.data.message) ? res.data.message : 'Failed to fetch sites from Cloudflare.';
                    sitesList.innerHTML = '<div class="cf-modal-empty"><span class="dashicons dashicons-warning" style="font-size:32px;width:32px;height:32px;color:#dc2626;"></span><p>' + escapeHtml(msg) + '</p></div>';
                    if (countEl) countEl.textContent = '0 properties available';
                }
            })
            .catch(function (err) {
                sitesList.innerHTML = '<div class="cf-modal-empty"><span class="dashicons dashicons-warning" style="font-size:32px;width:32px;height:32px;color:#dc2626;"></span><p>Connection error while querying Cloudflare API.</p></div>';
                if (countEl) countEl.textContent = 'Error';
            });
        }

        function renderSites(sites) {
            if (!sites || !sites.length) {
                sitesList.innerHTML = '<div class="cf-modal-empty"><span class="dashicons dashicons-search" style="font-size:32px;width:32px;height:32px;color:#94a3b8;"></span><p>No properties found matching your search.</p></div>';
                if (countEl) countEl.textContent = '0 properties found';
                if (confirmBtn) confirmBtn.disabled = true;
                return;
            }

            if (countEl) countEl.textContent = sites.length + ' ' + (sites.length === 1 ? 'property' : 'properties') + ' available';

            // Check if there is an active/recommended site to pre-select
            var preSelectedSite = null;
            if (!selectedSite) {
                for (var i = 0; i < sites.length; i++) {
                    if (sites[i].is_active) {
                        preSelectedSite = sites[i];
                        break;
                    }
                }
                if (!preSelectedSite) {
                    for (var j = 0; j < sites.length; j++) {
                        if (sites[j].is_match) {
                            preSelectedSite = sites[j];
                            break;
                        }
                    }
                }
                if (preSelectedSite) {
                    selectedSite = preSelectedSite;
                }
            }

            var html = '';
            sites.forEach(function (site, idx) {
                var isChecked = selectedSite && selectedSite.site_tag === site.site_tag;
                html += '<div class="cf-site-card ' + (isChecked ? 'is-selected' : '') + '" data-index="' + idx + '" data-tag="' + escapeHtml(site.site_tag) + '">';
                html += '  <input type="radio" name="cf_modal_selected_site" class="cf-site-radio" value="' + escapeHtml(site.site_tag) + '" ' + (isChecked ? 'checked' : '') + ' />';
                html += '  <div class="cf-site-info">';
                html += '    <div class="cf-site-top-row">';
                html += '      <span class="cf-site-domain">' + escapeHtml(site.domain) + '</span>';
                if (site.is_match) {
                    html += '      <span class="cf-badge-recommended">Recommended</span>';
                }
                if (site.is_active) {
                    html += '      <span class="cf-badge-current">Connected</span>';
                }
                html += '    </div>';
                html += '    <div class="cf-site-meta-row">';
                html += '      <span class="cf-site-meta-item"><strong>Site Tag:</strong> ' + escapeHtml(site.site_tag.substring(0, 16)) + '...</span>';
                if (site.zone_tag) {
                    html += '      <span class="cf-site-meta-item"><strong>Zone:</strong> ' + escapeHtml(site.zone_tag.substring(0, 8)) + '...</span>';
                }
                html += '    </div>';
                html += '  </div>';
                html += '</div>';
            });

            sitesList.innerHTML = html;

            if (selectedSite && confirmBtn) {
                confirmBtn.disabled = false;
            }

            // Bind click events on cards
            var cards = sitesList.querySelectorAll('.cf-site-card');
            cards.forEach(function (card) {
                card.addEventListener('click', function () {
                    cards.forEach(function (c) {
                        c.classList.remove('is-selected');
                        var radio = c.querySelector('input[type="radio"]');
                        if (radio) radio.checked = false;
                    });
                    card.classList.add('is-selected');
                    var radio = card.querySelector('input[type="radio"]');
                    if (radio) radio.checked = true;

                    var tag = card.getAttribute('data-tag');
                    for (var k = 0; k < allSites.length; k++) {
                        if (allSites[k].site_tag === tag) {
                            selectedSite = allSites[k];
                            break;
                        }
                    }

                    if (confirmBtn) confirmBtn.disabled = false;
                });
            });
        }

        function filterSites() {
            var q = searchInput ? searchInput.value.toLowerCase().trim() : '';
            if (!q) {
                renderSites(allSites);
                return;
            }
            var filtered = allSites.filter(function (s) {
                return (s.domain && s.domain.toLowerCase().indexOf(q) !== -1) ||
                       (s.site_tag && s.site_tag.toLowerCase().indexOf(q) !== -1) ||
                       (s.zone_tag && s.zone_tag.toLowerCase().indexOf(q) !== -1);
            });
            renderSites(filtered);
        }

        function saveSelectedSite() {
            if (!selectedSite) return;

            var tokenVal = tokenInput ? tokenInput.value.trim() : '';
            confirmBtn.disabled = true;
            var originalBtnHtml = confirmBtn.innerHTML;
            confirmBtn.innerHTML = '<span class="cf-spinner" style="width:14px;height:14px;border-width:2px;margin-right:6px;"></span> Connecting...';

            var formData = new FormData();
            formData.append('action', 'cf_analytics_save_selected_site');
            formData.append('nonce', typeof cfAnalyticsData !== 'undefined' ? cfAnalyticsData.nonce : '');
            formData.append('site_tag', selectedSite.site_tag);
            formData.append('account_id', selectedSite.account_id || '');
            formData.append('zone_id', selectedSite.zone_tag || '');
            formData.append('domain', selectedSite.domain || '');
            formData.append('token', tokenVal);

            fetch(typeof cfAnalyticsData !== 'undefined' ? cfAnalyticsData.ajaxUrl : '/wp-admin/admin-ajax.php', {
                method: 'POST',
                body: formData,
                credentials: 'same-origin'
            })
            .then(function (res) { return res.json(); })
            .then(function (res) {
                confirmBtn.innerHTML = originalBtnHtml;
                if (res.success && res.data) {
                    // Update form fields
                    var inputSiteTag = document.getElementById('cf_analytics_site_tag');
                    var inputAccount = document.getElementById('cf_analytics_account_id');
                    var inputZone    = document.getElementById('cf_analytics_zone_id');
                    if (inputSiteTag) inputSiteTag.value = res.data.site_tag || '';
                    if (inputAccount) inputAccount.value = res.data.account_id || '';
                    if (inputZone) inputZone.value = res.data.zone_id || '';

                    // Reload page so full connected dashboard & connection status view reflect immediately
                    closeModal();
                    window.location.reload();
                } else {
                    confirmBtn.disabled = false;
                    alert((res.data && res.data.message) ? res.data.message : 'Failed to save property selection.');
                }
            })
            .catch(function (err) {
                confirmBtn.disabled = false;
                confirmBtn.innerHTML = originalBtnHtml;
                alert('Connection error while saving property selection.');
            });
        }

        function escapeHtml(str) {
            if (!str) return '';
            return String(str)
                .replace(/&/g, '&amp;')
                .replace(/</g, '&lt;')
                .replace(/>/g, '&gt;')
                .replace(/"/g, '&quot;')
                .replace(/'/g, '&#039;');
        }

        // Open modal triggers
        if (btnConnect) {
            btnConnect.addEventListener('click', function (e) {
                e.preventDefault();
                openModal();
            });
        }
        if (btnSwitch) {
            btnSwitch.addEventListener('click', function (e) {
                e.preventDefault();
                openModal();
            });
        }

        // Close modal triggers
        if (closeBtn) closeBtn.addEventListener('click', closeModal);
        if (cancelBtn) cancelBtn.addEventListener('click', closeModal);
        if (backdrop) backdrop.addEventListener('click', closeModal);
        document.addEventListener('keydown', function (e) {
            if (e.key === 'Escape' && modal.style.display === 'flex') {
                closeModal();
            }
        });

        // Search filter
        if (searchInput) {
            searchInput.addEventListener('input', filterSites);
        }

        // Confirm button
        if (confirmBtn) {
            confirmBtn.addEventListener('click', saveSelectedSite);
        }
    }
})();
