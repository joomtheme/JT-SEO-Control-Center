(function () {
    'use strict';

    var options = {};

    if (window.Joomla && typeof Joomla.getOptions === 'function') {
        try {
            options = Joomla.getOptions('com_jtseo.dashboard') || {};
        } catch (error) {
            options = {};
        }
    }

    function ready(callback) {
        if (document.readyState === 'loading') {
            document.addEventListener('DOMContentLoaded', callback);
        } else {
            callback();
        }
    }

    function closest(element, selector) {
        if (!element) {
            return null;
        }

        if (element.closest) {
            return element.closest(selector);
        }

        while (element && element.nodeType === 1) {
            if (element.matches && element.matches(selector)) {
                return element;
            }
            element = element.parentElement;
        }

        return null;
    }

    function showDashboardTab(button, updateUrl) {
        var tabs = Array.prototype.slice.call(document.querySelectorAll('#jtseo-dashboard-tabs [data-jtseo-tab-hash][data-bs-target]'));
        var panes = Array.prototype.slice.call(document.querySelectorAll('#jtseo-dashboard-tab-content .tab-pane'));
        var target = button ? button.getAttribute('data-bs-target') : '';
        var pane = target ? document.querySelector(target) : null;
        var bootstrapHandled = false;

        if (!button || !pane || !tabs.length || !panes.length) {
            return;
        }

        if (window.bootstrap && window.bootstrap.Tab && typeof window.bootstrap.Tab.getOrCreateInstance === 'function') {
            try {
                window.bootstrap.Tab.getOrCreateInstance(button).show();
                bootstrapHandled = true;
            } catch (error) {
                bootstrapHandled = false;
            }
        }

        if (!bootstrapHandled) {
            tabs.forEach(function (tab) {
                var active = tab === button;
                tab.classList.toggle('active', active);
                tab.setAttribute('aria-selected', active ? 'true' : 'false');
            });

            panes.forEach(function (item) {
                var active = item === pane;
                item.classList.toggle('active', active);
                item.classList.toggle('show', active);
            });
        }

        if (updateUrl && window.history && window.history.replaceState) {
            var hash = button.getAttribute('data-jtseo-tab-hash') || 'overview';
            var url = button.getAttribute('data-jtseo-tab-url') || button.getAttribute('href') || window.location.href;

            window.history.replaceState(null, '', url + '#jtseo-' + hash);
        }
    }

    ready(function () {
        var tabs = Array.prototype.slice.call(document.querySelectorAll('#jtseo-dashboard-tabs [data-jtseo-tab-hash][data-bs-target]'));

        if (!tabs.length) {
            return;
        }

        document.addEventListener('click', function (event) {
            var button = closest(event.target, '#jtseo-dashboard-tabs [data-jtseo-tab-hash][data-bs-target]');

            if (!button) {
                return;
            }

            event.preventDefault();
            showDashboardTab(button, true);
        }, true);

        var hash = (window.location.hash || '').replace('#jtseo-', '');
        var hashAliases = {
            'bulk-meta': 'bulk',
            'bulk-import-export': 'bulk',
            'audit-card': 'audit',
            'structured-data': 'structured',
            'health-check': 'overview',
            'global-settings': 'overview',
            'feature-cards': 'overview',
            'next-steps': 'overview'
        };
        var resolvedHash = hashAliases[hash] || hash;
        var selected = tabs.filter(function (button) {
            return button.getAttribute('data-jtseo-tab-hash') === resolvedHash;
        })[0];

        if (selected) {
            showDashboardTab(selected, false);
        }
    });

    ready(function () {
        var search = document.getElementById('jtseo-audit-search');
        var severity = document.getElementById('jtseo-audit-severity');
        var type = document.getElementById('jtseo-audit-type');
        var reset = document.getElementById('jtseo-audit-reset');
        var counter = document.getElementById('jtseo-audit-filter-count');
        var rows = Array.prototype.slice.call(document.querySelectorAll('.jtseo-audit-row'));

        if (!rows.length || !search || !severity || !type || !counter) {
            return;
        }

        var template = options.auditFilterCount || '%s / %s';

        function normalize(value) {
            return String(value || '').toLowerCase().trim();
        }

        function updateRows() {
            var query = normalize(search.value);
            var selectedSeverity = severity.value;
            var selectedType = type.value;
            var visible = 0;

            rows.forEach(function (row) {
                var matchesSearch = query === '' || (row.getAttribute('data-text') || '').toLowerCase().indexOf(query) !== -1;
                var matchesSeverity = selectedSeverity === '' || row.getAttribute('data-severity') === selectedSeverity;
                var matchesType = selectedType === '' || row.getAttribute('data-type') === selectedType;
                var show = matchesSearch && matchesSeverity && matchesType;

                row.hidden = !show;

                if (show) {
                    visible++;
                }
            });

            counter.textContent = template.replace('%s', visible).replace('%s', rows.length);
        }

        [search, severity, type].forEach(function (field) {
            field.addEventListener('input', updateRows);
            field.addEventListener('keyup', updateRows);
            field.addEventListener('change', updateRows);
        });

        if (reset) {
            reset.addEventListener('click', function (event) {
                event.preventDefault();
                search.value = '';
                severity.value = '';
                type.value = '';
                updateRows();
                search.focus();
            });
        }

        updateRows();
    });

    ready(function () {
        var form = document.getElementById('jtseo-bulk-meta-form');

        if (!form) {
            return;
        }

        Array.prototype.slice.call(form.querySelectorAll('input, textarea, select')).forEach(function (field) {
            if (!field.name || field.type === 'hidden') {
                return;
            }

            field.setAttribute('data-jtseo-initial-value', field.value || '');

            field.addEventListener('input', function () {
                field.classList.toggle('is-changed', (field.value || '') !== field.getAttribute('data-jtseo-initial-value'));
            });

            field.addEventListener('change', function () {
                field.classList.toggle('is-changed', (field.value || '') !== field.getAttribute('data-jtseo-initial-value'));
            });
        });
    });

    ready(function () {
        ['jtseo-audit-filter-form', 'jtseo-bulk-filter-form'].forEach(function (formId) {
            var form = document.getElementById(formId);

            if (!form) {
                return;
            }

            Array.prototype.slice.call(form.querySelectorAll('select')).forEach(function (field) {
                field.addEventListener('change', function () {
                    if (typeof form.requestSubmit === 'function') {
                        form.requestSubmit();
                    } else {
                        form.submit();
                    }
                });
            });
        });
    });

}());
