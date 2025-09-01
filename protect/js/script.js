

$(document).ready(function () {
    // Ensure default theme on first load
    try {
        if (!readCookie('theme')) {
            updateCookie('theme', 'dark', 9999999);
        }
    } catch (e) {}
    loadtheme();

    // Helper to prevent duplicate toggles from multiple handlers (global lock)
    let themeToggleLock = false;
    function toggleThemeOnce(e) {
        if (e && e.preventDefault) e.preventDefault();
        if (themeToggleLock) return;
        themeToggleLock = true;
        setTimeout(() => { themeToggleLock = false; }, 0);
        const theme = readCookie('theme');
        updateCookie('theme', theme === 'dark' ? 'light' : 'dark', 9999999);
        loadtheme();
        try { document.dispatchEvent(new CustomEvent('theme:changed', { detail: { theme: readCookie('theme') } })); } catch (_) {}
    }

    // Direct binding so it still fires inside dropdown menus that stopPropagation
    $('.btn-theme').on('click', function (e) {
        if (e && typeof e.stopImmediatePropagation === 'function') e.stopImmediatePropagation();
        if (e && e.stopPropagation) e.stopPropagation();
        toggleThemeOnce(e);
        return false;
    });

    // Fallback delegated (outside dropdowns)
    $(document).on('click', '.btn-theme', function (e) { toggleThemeOnce(e); return false; });

    // Capture-phase handler so dropdown's stopPropagation doesn't block theme toggle
    window.addEventListener('click', function (e) {
        const btn = e.target && (e.target.closest ? e.target.closest('.btn-theme') : null);
        if (!btn) return;
        toggleThemeOnce(e);
    }, true);




});

function loadtheme() {
    let theme = readCookie('theme');
    if (theme == 'dark') {
        $(document).find("body").css("background", "var(--dark-body)");
        $(document).find(".navbar-theme").css({
            "background": "var(--dark-navbar)",
            "border-color": "var(--dark-border)"
        });
        // expose navbar colors to CSS (used by navbar panel CSS)
        try {
            document.documentElement.style.setProperty('--navbar-bg', getComputedStyle(document.documentElement).getPropertyValue('--dark-navbar') || '');
            document.documentElement.style.setProperty('--navbar-border', getComputedStyle(document.documentElement).getPropertyValue('--dark-border') || '');
        } catch (e) {}
        $(document).find(".bg-theme").css("background", "var(--dark-bg)");
        $(document).find(".text-theme").css("color", "var(--dark-text)");
        $(document).find(".input-theme").css("background", "var(--dark-input)");
        $(document).find(".input-theme").css("border", "3px solid var(--dark-input-border)");
        $(document).find(".input-theme").css("caret-color", "var(--dark-text)");
        $(document).find(".input-theme").css("color", "var(--dark-text)");
        $(document).find(".label-bg").css("background", "var(--dark-label-bg)");
        $(document).find(".border-theme").css("border", "1px solid var(--dark-border)");
        $(document).find(".btn-theme").html('<i class="fas fa-sun"></i>');
        $(document).find(".btn-theme[role='menuitem']").html('Dark <i class="fas fa-sun"></i>');

        // DataTables (support v1 & v2)
        const $dt = $(document).find('.dataTables_wrapper, .dt-container');
        $dt.css('color', 'var(--dark-text)');
        $dt.find('.dataTables_length, .dataTables_filter, .dataTables_info, .dataTables_paginate, .dt-length, .dt-search, .dt-info, .dt-paging')
           .css('color', 'var(--dark-text)');
        $dt.find('.dataTables_length select, .dt-length select, .dataTables_filter input, .dt-search input')
           .css({
               'background': 'var(--dark-input)',
               'border': '1px solid var(--dark-input-border)',
               'color': 'var(--dark-text)',
               'caret-color': 'var(--dark-text)'
           });
        $dt.find('.paginate_button, .dt-paging button, .dt-paging a, .dt-paging span')
           .css({ 'color': 'var(--dark-text)', 'border-color': 'var(--dark-border)' });

        // Inject strong overrides with !important for disabled/current arrows
        applyDTStrongTheme('dark');
    } else {
        $(document).find("body").css("background", "var(--light-body)");
        $(document).find(".navbar-theme").css({
            "background": "var(--light-navbar)",
            "border-color": "var(--light-border)"
        });
        // expose navbar colors to CSS (used by navbar panel CSS)
        try {
            document.documentElement.style.setProperty('--navbar-bg', getComputedStyle(document.documentElement).getPropertyValue('--light-navbar') || '');
            document.documentElement.style.setProperty('--navbar-border', getComputedStyle(document.documentElement).getPropertyValue('--light-border') || '');
        } catch (e) {}
        $(document).find(".bg-theme").css("background", "var(--light-bg)");
        $(document).find(".text-theme").css("color", "var(--light-text)");
        $(document).find(".input-theme").css("background", "var(--light-input)");
        $(document).find(".input-theme").css("border", "1px solid var(--light-input-border)");
        $(document).find(".input-theme").css("color", "var(--light-text)");
        $(document).find(".label-bg").css("background", "var(--light-label-bg)");
        $(document).find(".border-theme").css("border", "0px solid var(--light-border)");
        $(document).find(".input-theme").css("caret-color", "var(--light-text)");
        $(document).find(".btn-theme").html('<i class="fas fa-moon"></i>');
        $(document).find(".btn-theme[role='menuitem']").html('Light <i class="fas fa-moon"></i>');

        // DataTables (support v1 & v2)
        const $dt = $(document).find('.dataTables_wrapper, .dt-container');
        $dt.css('color', 'var(--light-text)');
        $dt.find('.dataTables_length, .dataTables_filter, .dataTables_info, .dataTables_paginate, .dt-length, .dt-search, .dt-info, .dt-paging')
           .css('color', 'var(--light-text)');
        $dt.find('.dataTables_length select, .dt-length select, .dataTables_filter input, .dt-search input')
           .css({
               'background': 'var(--light-input)',
               'border': '1px solid var(--light-input-border)',
               'color': 'var(--light-text)',
               'caret-color': 'var(--light-text)'
           });
        $dt.find('.paginate_button, .dt-paging button, .dt-paging a, .dt-paging span')
           .css({ 'color': 'var(--light-text)', 'border-color': 'var(--light-border)' });

        // Inject strong overrides with !important for disabled/current arrows
        applyDTStrongTheme('light');
    }
}

// Create/replace <style> tag with theme-strong rules for DataTables
function applyDTStrongTheme(mode) {
    const id = 'dt-theme-style';
    let el = document.getElementById(id);
    if (!el) {
        el = document.createElement('style');
        el.id = id;
        document.head.appendChild(el);
    }
    // Always move to the end of <head> to ensure our rules load last
    try { document.head.appendChild(el); } catch (_) {}
    const css = mode === 'dark' ? `
/* DataTables v1 */
.dataTables_wrapper .dataTables_paginate,
.dataTables_wrapper .dataTables_paginate * {
  color: var(--dark-text) !important;
  fill: var(--dark-text) !important;
  stroke: var(--dark-text) !important;
}
.dataTables_wrapper .dataTables_paginate .paginate_button,
.dataTables_wrapper .dataTables_paginate .paginate_button.current,
.dataTables_wrapper .dataTables_paginate .paginate_button.disabled,
.dataTables_wrapper .dataTables_paginate .paginate_button:hover,
.dataTables_wrapper .dataTables_paginate .paginate_button::before,
.dataTables_wrapper .dataTables_paginate .paginate_button::after,
.dataTables_wrapper .dataTables_paginate .paginate_button *,
.dataTables_wrapper .dataTables_paginate .paginate_button *::before,
.dataTables_wrapper .dataTables_paginate .paginate_button *::after,
.dataTables_wrapper .dataTables_paginate a,
.dataTables_wrapper .dataTables_paginate a *,
.dataTables_wrapper .dataTables_paginate a *::before,
.dataTables_wrapper .dataTables_paginate a *::after {
  color: var(--dark-text) !important;
  border-color: var(--dark-border) !important;
  border-width: 1px !important;
  border-style: solid !important;
  background: transparent !important;
  outline: none !important;
  fill: var(--dark-text) !important;
  stroke: var(--dark-text) !important;
}
.dataTables_wrapper .dataTables_info { color: var(--dark-text) !important; }
.dataTables_wrapper .dataTables_paginate .paginate_button.disabled,
.dataTables_wrapper .dataTables_paginate .ellipsis {
  opacity: 1 !important;
  color: var(--dark-text) !important;
  -webkit-text-fill-color: var(--dark-text) !important;
  filter: none !important;
}
.dataTables_wrapper .dataTables_paginate .paginate_button.disabled *,
.dataTables_wrapper .dataTables_paginate .paginate_button.disabled *::before,
.dataTables_wrapper .dataTables_paginate .paginate_button.disabled *::after {
  opacity: 1 !important;
  color: var(--dark-text) !important;
  fill: var(--dark-text) !important;
  stroke: var(--dark-text) !important;
  -webkit-text-fill-color: var(--dark-text) !important;
  filter: none !important;
}

/* DataTables v2 */
.dt-container .dt-paging,
.dt-container .dt-paging * {
  color: var(--dark-text) !important;
  fill: var(--dark-text) !important;
  stroke: var(--dark-text) !important;
}
.dt-container .dt-paging .dt-paging-button,
.dt-container .dt-paging .dt-paging-button.current,
.dt-container .dt-paging .dt-paging-button.disabled,
.dt-container .dt-paging .dt-paging-button:hover,
.dt-container .dt-paging .dt-paging-button::before,
.dt-container .dt-paging .dt-paging-button::after,
.dt-container .dt-paging .dt-paging-button .dt-paging-icon,
.dt-container .dt-paging .dt-paging-button .dt-label,
.dt-container .dt-paging .dt-paging-button span,
.dt-container .dt-paging .dt-paging-button i,
.dt-container .dt-paging .dt-paging-button svg,
.dt-container .dt-paging .dt-paging-button *,
.dt-container .dt-paging .dt-paging-button *::before,
.dt-container .dt-paging .dt-paging-button *::after,
.dt-container .dt-paging .dt-next,
.dt-container .dt-paging .dt-prev {
  color: var(--dark-text) !important;
  border-color: var(--dark-border) !important;
  border-width: 1px !important;
  border-style: solid !important;
  background: transparent !important;
  outline: none !important;
  fill: var(--dark-text) !important;
  stroke: var(--dark-text) !important;
}
.dt-container .dt-paging .dt-paging-button.disabled,
.dt-container .dt-paging .dt-paging-button[disabled],
.dt-container .dt-paging .dt-paging-button[aria-disabled="true"] {
  opacity: 1 !important;
  color: var(--dark-text) !important;
  border-color: var(--dark-border) !important;
  -webkit-text-fill-color: var(--dark-text) !important;
  filter: none !important;
}
.dt-container .dt-paging .dt-paging-button.disabled *,
.dt-container .dt-paging .dt-paging-button[disabled] *,
.dt-container .dt-paging .dt-paging-button[aria-disabled="true"] * {
  opacity: 1 !important;
  color: var(--dark-text) !important;
  fill: var(--dark-text) !important;
  stroke: var(--dark-text) !important;
  -webkit-text-fill-color: var(--dark-text) !important;
  filter: none !important;
}
.dt-container .dt-paging .ellipsis { color: var(--dark-text) !important; opacity: 1 !important; }
` : `
/* DataTables v1 */
.dataTables_wrapper .dataTables_paginate .paginate_button,
.dataTables_wrapper .dataTables_paginate .paginate_button.current,
.dataTables_wrapper .dataTables_paginate .paginate_button.disabled,
.dataTables_wrapper .dataTables_paginate .paginate_button:hover,
.dataTables_wrapper .dataTables_paginate .paginate_button *,
.dataTables_wrapper .dataTables_paginate .paginate_button *::before,
.dataTables_wrapper .dataTables_paginate .paginate_button *::after,
.dataTables_wrapper .dataTables_paginate a,
.dataTables_wrapper .dataTables_paginate a *,
.dataTables_wrapper .dataTables_paginate a *::before,
.dataTables_wrapper .dataTables_paginate a *::after {
  color: var(--light-text) !important;
  border-color: var(--light-border) !important;
  border-width: 1px !important;
  border-style: solid !important;
  background: transparent !important;
  outline: none !important;
  fill: var(--light-text) !important;
  stroke: var(--light-text) !important;
}
.dataTables_wrapper .dataTables_info { color: var(--light-text) !important; }
.dataTables_wrapper .dataTables_paginate .paginate_button.disabled,
.dataTables_wrapper .dataTables_paginate .ellipsis {
  opacity: 1 !important;
  color: var(--light-text) !important;
  -webkit-text-fill-color: var(--light-text) !important;
  filter: none !important;
}
.dataTables_wrapper .dataTables_paginate .paginate_button.disabled *,
.dataTables_wrapper .dataTables_paginate .paginate_button.disabled *::before,
.dataTables_wrapper .dataTables_paginate .paginate_button.disabled *::after {
  opacity: 1 !important;
  color: var(--light-text) !important;
  fill: var(--light-text) !important;
  stroke: var(--light-text) !important;
  -webkit-text-fill-color: var(--light-text) !important;
  filter: none !important;
}

/* DataTables v2 */
.dt-container .dt-paging .dt-paging-button,
.dt-container .dt-paging .dt-paging-button.current,
.dt-container .dt-paging .dt-paging-button.disabled,
.dt-container .dt-paging .dt-paging-button:hover,
.dt-container .dt-paging .dt-paging-button::before,
.dt-container .dt-paging .dt-paging-button::after,
.dt-container .dt-paging .dt-paging-button .dt-paging-icon,
.dt-container .dt-paging .dt-paging-button .dt-label,
.dt-container .dt-paging .dt-next,
.dt-container .dt-paging .dt-prev,
.dt-container .dt-paging .dt-paging-button *,
.dt-container .dt-paging .dt-paging-button *::before,
.dt-container .dt-paging .dt-paging-button *::after {
  color: var(--light-text) !important;
  border-color: var(--light-border) !important;
  border-width: 1px !important;
  border-style: solid !important;
  background: transparent !important;
  outline: none !important;
  fill: var(--light-text) !important;
  stroke: var(--light-text) !important;
}
.dt-container .dt-paging .dt-paging-button.disabled,
.dt-container .dt-paging .dt-paging-button[disabled],
.dt-container .dt-paging .dt-paging-button[aria-disabled="true"] { opacity: 1 !important; color: var(--light-text) !important; border-color: var(--light-border) !important; }
.dt-container .dt-paging .dt-paging-button.disabled,
.dt-container .dt-paging .dt-paging-button[disabled],
.dt-container .dt-paging .dt-paging-button[aria-disabled="true"] {
  -webkit-text-fill-color: var(--light-text) !important;
  filter: none !important;
}
.dt-container .dt-paging .ellipsis { color: var(--light-text) !important; opacity: 1 !important; }
.dt-container .dt-paging .dt-paging-button.disabled *,
.dt-container .dt-paging .dt-paging-button[disabled] *,
.dt-container .dt-paging .dt-paging-button[aria-disabled="true"] * {
  opacity: 1 !important;
  color: var(--light-text) !important;
  fill: var(--light-text) !important;
  stroke: var(--light-text) !important;
  -webkit-text-fill-color: var(--light-text) !important;
  filter: none !important;
}
`;
    el.textContent = css;
}

// (debug panel removed)
