<!-- Top Navbar -->
<?php
$config=getConfig();
?>
<nav class="w-full navbar-theme  border-b border-gray-300 relative z-40 overflow-visible">
  <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
    <div class="h-14 grid grid-cols-3 items-center">
      <!-- Left: Brand -->
      <div class="flex items-center justify-start gap-2">
        <!-- Hamburger (mobile only) -->
        <button type="button"
                class="md:hidden inline-flex items-center justify-center p-2 rounded-lg border border-gray-300 text-gray-700 hover:bg-gray-100 focus:outline-none focus:ring-2 focus:ring-indigo-500"
                aria-controls="navItems" aria-expanded="false" aria-label="Toggle navigation"
                data-mobile-trigger>
          <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" class="w-6 h-6">
            <path fill-rule="evenodd" d="M3.75 5.25a.75.75 0 0 1 .75-.75h15a.75.75 0 0 1 0 1.5H4.5a.75.75 0 0 1-.75-.75Zm0 6a.75.75 0 0 1 .75-.75h15a.75.75 0 0 1 0 1.5H4.5a.75.75 0 0 1-.75-.75Zm.75 5.25a.75.75 0 0 0 0 1.5h15a.75.75 0 0 0 0-1.5H4.5Z" clip-rule="evenodd" />
          </svg>
        </button>

        <div class="text-gray-900 font-medium tracking-tight text-theme"><a href="/"><?= $config['web']['name'] ?></a></div>
      </div>

      <!-- Center: Nav Pills (Servers[, Users for admin]) -->
      <div id="navItems" class="hidden md:flex items-center justify-center gap-6">
        <!-- Servers pill -->
        <a href="<?= defined('BASE_PATH') ? BASE_PATH : '' ?>/"
           class="nav-pill bg-white text-gray-900 px-4 py-2 rounded-xl shadow-sm hover:shadow transition flex items-center gap-2">
          <span>Servers</span>
        </a>
        <?php
          $__isAdmin = isset($_SESSION['user_data']) && isset($_SESSION['user_data']['role']) && $_SESSION['user_data']['role'] === 'admin';
          if ($__isAdmin):
        ?>
        <!-- Users (admin only): single action → use direct pill link -->
        <a href="/users/"
           class="nav-pill bg-white text-gray-900 px-4 py-2 rounded-xl shadow-sm hover:shadow transition flex items-center gap-2">
          <span>Users</span>
        </a>
        <!-- API Keys (admin only) -->
        <a href="<?= defined('BASE_PATH') ? BASE_PATH : '' ?>/api-keys/"
           class="nav-pill bg-white text-gray-900 px-4 py-2 rounded-xl shadow-sm hover:shadow transition flex items-center gap-2">
          <span>API Keys</span>
        </a>
        <?php endif; ?>
      </div>

      <!-- Right: User dropdown -->
      <div class="flex items-center justify-end justify-self-end col-start-3">
        <!-- Tomson (User) -->
        <div class="relative" data-dropdown>
          <button type="button" aria-haspopup="true" aria-expanded="false"
                  class="bg-white text-gray-900 px-4 py-2 rounded-xl shadow-sm hover:shadow transition flex items-center gap-2"
                  data-trigger>
            <span><?php echo isset($_SESSION['user_data']['username']) ? htmlspecialchars($_SESSION['user_data']['username']) : 'User'; ?></span>
            <span class="select-none transition-transform duration-150" data-caret>▼</span>
          </button>
          <div class="absolute right-0 top-full mt-2 w-48 bg-white rounded-xl shadow-lg ring-1 ring-black/5 py-2 hidden z-50"
               role="menu" aria-label="User menu" data-menu>
            <a class="block px-4 py-2 hover:bg-gray-50 text-sm text-gray-700 btn-theme" role="menuitem" tabindex="-1" href="#">Theme</a>
            <a class="block px-4 py-2 hover:bg-gray-50 text-sm text-gray-700" role="menuitem" tabindex="-1" href="<?= defined('BASE_PATH') ? BASE_PATH : '' ?>/profile">Profile</a>
            <a class="block px-4 py-2 hover:bg-gray-50 text-sm text-gray-700 btn-logout" role="menuitem" tabindex="-1" href="#">Logout</a>
          </div>
        </div>
      </div>
    </div>
  </div>
</nav>

<!-- Force-visible rule for opened dropdowns (bypass any global styles) -->
<style>
  /* Keep pills single-line to avoid height overflow */
  .nav-pill { white-space: nowrap; }
  [data-menu][data-open="true"] { display: block !important; }
  /* When mobile menu is open, position the shared nav items as a full-width panel */
  #navItems[data-open="true"] {
    display:block !important;
    position: fixed;
    left: 0; right: 0; top: 56px;
    z-index: 50;
    background-color: var(--navbar-bg, #ffffff);
    border-bottom: 1px solid var(--navbar-border, #e5e7eb);
    padding: 10px 16px;
  }
  #navItems[data-open="true"] > * { pointer-events: auto; }
  /* stack vertically on mobile when opened */
  @media (max-width: 767px) {
    #navItems { width: 100%; }
    #navItems[data-open="true"] { display:block; }
    #navItems[data-open="true"] { gap: 10px; }
    #navItems[data-open="true"] { flex-direction: column; align-items: stretch; }
    #navItems[data-open="true"] [data-dropdown] { width: 100%; }
    #navItems[data-open="true"] [data-trigger] { width: 100%; justify-content: space-between; }
  }

  /* Desktop: don't deliver clicks to the empty grey area between pills */
  @media (min-width: 768px) {
    #navItems { pointer-events: none; }
    #navItems [data-dropdown], #navItems [data-trigger], #navItems [data-menu], #navItems .nav-pill { pointer-events: auto; }
  }
</style>

  <!-- Minimal dropdown logic (vanilla JS) -->
  <script>
  $(function () {
    const $dropdowns = $('[data-dropdown]');
    let $currentOpen = null;
    const $navItems = $('#navItems');
    const $mobileTrigger = $('[data-mobile-trigger]');

    const closeAll = () => {
      $dropdowns.each((_, el) => {
        const $d = $(el);
        const $menu = $d.find('[data-menu]');
        const $btn = $d.find('[data-trigger]');
        const $caret = $d.find('[data-caret]');
        $menu.addClass('hidden').removeAttr('data-open')
             .css({ position: '', left: '', top: '', minWidth: '', width: '', maxWidth: '', zIndex: '' });
        $btn.attr('aria-expanded', 'false');
        $caret.removeClass('rotate-180');
      });
      $currentOpen = null;
    };

    // Mobile toggle (shared items container)
    const closeMobile = () => {
      if (!$navItems.length) return;
      $navItems.addClass('hidden').removeAttr('data-open');
      $mobileTrigger.attr('aria-expanded', 'false');
    };
    const openMobile = () => {
      if (!$navItems.length) return;
      $navItems.removeClass('hidden').attr('data-open', 'true');
      $mobileTrigger.attr('aria-expanded', 'true');
    };
    $mobileTrigger.on('click', (e) => {
      e.stopPropagation();
      if (!$navItems.length) return;
      if ($navItems.is('[data-open]')) closeMobile(); else openMobile();
    });

    // Keep mobile panel open when interacting inside it (bubble only)
    const keepPanelOpen = (e) => {
      if (!$navItems.length) return;
      const isMobile = window.innerWidth < 768;
      const $t = $(e.target);
      const onTrigger = $t.closest('[data-trigger]').length > 0;
      const insideMenu = $t.closest('[data-menu]').length > 0;
      const onNavPill = $t.closest('.nav-pill').length > 0;

      // Only force-keep panel open on mobile
      if (isMobile) {
        $navItems.removeClass('hidden').attr('data-open', 'true');
      }

      // If click is on empty grey area (not on trigger or menu), never open anything
      if (!onTrigger && !insideMenu && !onNavPill) {
        closeAll();
        if (e.cancelable) e.preventDefault();
        if (typeof e.stopImmediatePropagation === 'function') e.stopImmediatePropagation();
        e.stopPropagation();
        return false;
      }
      // otherwise allow event to continue so the trigger/menu handlers run normally
    };
    $navItems.on('click', keepPanelOpen);
    $navItems.on('touchstart', keepPanelOpen);

    // Capture-phase guard: if clicking empty grey area inside #navItems, block early
    try {
      const navEl = $navItems.get(0);
      if (navEl) {
        navEl.addEventListener('click', function(e){
          const onTrigger = e.target && (e.target.closest ? e.target.closest('[data-trigger]') : null);
          const insideMenu = e.target && (e.target.closest ? e.target.closest('[data-menu]') : null);
          const onNavPill = e.target && (e.target.closest ? e.target.closest('.nav-pill') : null);
          if (!onTrigger && !insideMenu && !onNavPill) {
            closeAll();
            if (e.cancelable) e.preventDefault();
            e.stopPropagation();
          }
        }, true);
      }
    } catch(_) {}

    // Global capture-phase fallback: if the click starts in #navItems but not on a trigger/menu, block it
    document.addEventListener('click', function(e){
      const inNav = e.target && (e.target.closest ? e.target.closest('#navItems') : null);
      if (!inNav) return;
      const onTrigger = e.target.closest && e.target.closest('[data-trigger]');
      const insideMenu = e.target.closest && e.target.closest('[data-menu]');
      const onNavPill = e.target.closest && e.target.closest('.nav-pill');
      if (!onTrigger && !insideMenu && !onNavPill) {
        closeAll();
        if (e.cancelable) e.preventDefault();
        e.stopPropagation();
      }
    }, true);

    // Dropdowns
    $dropdowns.each((_, el) => {
      const $d = $(el);
      const $btn = $d.find('[data-trigger]');
      const $menu = $d.find('[data-menu]');
      const $caret = $d.find('[data-caret]');

      const toggleDropdown = (evt) => {
        if (evt.cancelable) evt.preventDefault();
        if (typeof evt.stopImmediatePropagation === 'function') evt.stopImmediatePropagation();
        evt.stopPropagation();
        const isOpening = $menu.hasClass('hidden');
        closeAll();
        if (isOpening) {
          const rect = $btn.get(0).getBoundingClientRect();
          const vw = window.innerWidth || document.documentElement.clientWidth;
          const pad = 8;
          const isMobile = (window.innerWidth || vw) < 768;
          let mw = Math.max($menu.outerWidth() || 0, 192);
          if (isMobile) {
            mw = Math.round(rect.width);
            $menu.css({ width: mw + 'px', minWidth: mw + 'px', maxWidth: mw + 'px' });
          } else {
            $menu.css({ width: '', maxWidth: '', minWidth: mw + 'px' });
          }
          const desiredLeft = rect.left;
          const clampedLeft = Math.max(pad, Math.min(desiredLeft, vw - mw - pad));

          $menu.css({ position: 'fixed', top: Math.round(rect.bottom + 8) + 'px', left: Math.round(clampedLeft) + 'px', zIndex: 60 })
               .removeClass('hidden').attr('data-open', 'true');
          $btn.attr('aria-expanded', 'true');
          $caret.addClass('rotate-180');
          $currentOpen = $d;
          // On mobile, only force-open the center menu list if this dropdown is inside it
          if (window.innerWidth < 768 && $navItems.length) {
            const insideNav = $d.closest('#navItems').length > 0;
            if (insideNav) {
              $navItems.removeClass('hidden').attr('data-open', 'true');
            }
          }
        }
      };

      $btn.on('click', toggleDropdown);
      $btn.on('touchstart', (evt) => { if (evt.cancelable) evt.preventDefault(); toggleDropdown(evt); });

      // Prevent bubbling from menu; close only dropdowns on item click
      $menu.on('click touchstart', (e) => e.stopPropagation());
      $menu.find('a, button').each((_, item) => {
        $(item).on('click', () => closeAll());
      });
    });

    // Outside click/tap: close dropdowns and mobile panel
    const onOutsidePointer = (e) => {
      const $t = $(e.target);
      const insidePanel = $t.closest('#navItems').length > 0;
      const onHamburger = $t.closest('[data-mobile-trigger]').length > 0;
      const insideAnyDropdown = $t.closest('[data-dropdown]').length > 0;
      if (insidePanel || onHamburger || insideAnyDropdown) return;
      closeAll();
      if ($navItems.is('[data-open]')) closeMobile();
    };
    $(document).on('click', onOutsidePointer);
    $(document).on('touchstart', onOutsidePointer);

    // Normalize panel visibility (desktop should show without hamburger)
    const normalizeVisibility = () => {
      const isDesktop = window.innerWidth >= 768;
      if (isDesktop) {
        $navItems.removeClass('hidden').removeAttr('data-open');
      } else {
        if ($navItems.is('[data-open]')) $navItems.removeClass('hidden'); else $navItems.addClass('hidden');
      }
    };

    // Initial normalize on load
    normalizeVisibility();
    // Resize: close dropdowns; normalize panel visibility
    $(window).on('resize', () => {
      closeAll();
      normalizeVisibility();
    });

    // Scroll: close open dropdown to avoid desync
    $(window).on('scroll', () => { if ($currentOpen) closeAll(); });

    // Escape key
    $(document).on('keydown', (e) => { if (e.key === 'Escape') { closeAll(); closeMobile(); } });

    // Logout with confirmation (SweetAlert2) - bind directly to element (menu stops propagation)
    $('.btn-logout').on('click', async function(e){
      e.preventDefault();
      try {
        const result = await Swal.fire({
          icon: 'question',
          title: 'ออกจากระบบ?',
          text: 'ยืนยันการออกจากระบบ',
          showCancelButton: true,
          confirmButtonText: 'ออกจากระบบ',
          cancelButtonText: 'ยกเลิก',
          reverseButtons: true
        });
        if (result.isConfirmed) {
          const base = '<?= defined('BASE_PATH') ? addslashes(BASE_PATH) : '' ?>';
          window.location.href = (base || '') + '/?action=logout';
        }
      } catch (_) {
        const base = '<?= defined('BASE_PATH') ? addslashes(BASE_PATH) : '' ?>';
        window.location.href = (base || '') + '/?action=logout';
      }
    });
  });
  </script>