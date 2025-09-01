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

      <!-- Center: Dropdown Pills (Server, Route[, Users for admin]) -->
      <div id="navItems" class="hidden md:flex items-center justify-center gap-6">
        <!-- Server -->
        <div class="relative" data-dropdown>
          <button type="button" aria-haspopup="true" aria-expanded="false"
                  class="bg-white text-gray-900 px-4 py-2 rounded-xl shadow-sm hover:shadow transition flex items-center gap-2"
                  data-trigger>
            <span>Server</span>
            <span class="select-none transition-transform duration-150" data-caret>▼</span>
          </button>
          <div class="absolute right-0 top-full mt-2 w-56 bg-white rounded-xl shadow-lg ring-1 ring-black/5 py-2 hidden z-50"
               role="menu" aria-label="Server menu" data-menu>
            <a id="btnOpenNewServer" class="block px-4 py-2 hover:bg-gray-50 text-sm text-gray-700" role="menuitem" tabindex="-1" href="javascript:void(0)">Add Server</a>
            <a class="block px-4 py-2 hover:bg-gray-50 text-sm text-gray-700" role="menuitem" tabindex="-1" href="<?= defined('BASE_PATH') ? BASE_PATH : '' ?>/">Manage</a>
          </div>
        </div>

        <!-- Route -->
        <div class="relative" data-dropdown>
          <button type="button" aria-haspopup="true" aria-expanded="false"
                  class="bg-white text-gray-900 px-4 py-2 rounded-xl shadow-sm hover:shadow transition flex items-center gap-2"
                  data-trigger>
            <span>Route</span>
            <span class="select-none transition-transform duration-150" data-caret>▼</span>
          </button>
          <div class="absolute right-0 top-full mt-2 w-56 bg-white rounded-xl shadow-lg ring-1 ring-black/5 py-2 hidden z-50"
               role="menu" aria-label="Route menu" data-menu>
            <a id="btnOpenNewRoute" class="block px-4 py-2 hover:bg-gray-50 text-sm text-gray-700" role="menuitem" tabindex="-1" href="javascript:void(0)">New Route</a>
            <a class="block px-4 py-2 hover:bg-gray-50 text-sm text-gray-700" role="menuitem" tabindex="-1" href="<?= defined('BASE_PATH') ? BASE_PATH : '' ?>/route/">Manage</a>
          </div>
        </div>
        <?php
          $__isAdmin = isset($_SESSION['user_data']) && isset($_SESSION['user_data']['role']) && $_SESSION['user_data']['role'] === 'admin';
          if ($__isAdmin):
        ?>
        <!-- Users (admin only): single action → use direct pill link -->
        <a href="/users/"
           class="nav-pill bg-white text-gray-900 px-4 py-2 rounded-xl shadow-sm hover:shadow transition flex items-center gap-2">
          <span>Users</span>
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

<!-- New Route Modal -->
<div id="newRouteModal" class="fixed inset-0 z-[1000] hidden" aria-hidden="true">
  <div id="newRouteBackdrop" class="absolute inset-0 bg-black/70 backdrop-blur-sm transition-opacity"></div>
  <div class="absolute inset-0 flex items-center justify-center p-4">
    <div id="newRoutePanel" class="w-full max-w-md bg-theme shadow-2xl rounded-2xl overflow-hidden transform transition-all duration-200 ease-out opacity-0 scale-95">
      <div class="px-4 py-3 border-b border-slate-700 flex items-center justify-between">
        <h3 class="text-lg font-semibold text-theme">New Route</h3>
        <button id="btnCloseNewRoute" class="px-3 py-1.5 rounded text-theme hover:bg-slate-700/50">✕</button>
      </div>
      <form id="formNewRoute" class="p-4 space-y-4">
        <div>
          <label for="selectServer" class="block text-sm mb-1 text-theme">Select Server</label>
          <select id="selectServer" class="w-full input-theme rounded-[10px] h-[40px] px-3">
            <option value="" selected disabled>-- Choose server --</option>
          </select>
        </div>
        <div>
          <label for="nr_hosts" class="block text-sm mb-1 text-theme">Hosts (comma or newline separated)</label>
          <textarea id="nr_hosts" class="w-full input-theme rounded-[14px] text-sm h-[90px]" rows="2" placeholder="example.com, api.example.com"></textarea>
        </div>
        <div>
          <label for="nr_dial" class="block text-sm mb-1 text-theme">Dial (ip[:port])</label>
          <input id="nr_dial" type="text" class="w-full input-theme rounded-[55px] text-center text-[16px] h-[50px]" placeholder="52.63.187.129:3001" />
        </div>
        <div class="flex items-center gap-2">
          <input id="nr_insecure" type="checkbox" class="h-4 w-4" />
          <label for="nr_insecure" class="text-sm text-theme">Insecure TLS (skip verify)</label>
        </div>
        <div class="pt-2 flex justify-end gap-2">
          <button type="button" id="btnCloseNewRoute2" class="px-5 py-2 rounded-[50px] text-white text-sm bg-slate-600 hover:bg-slate-500">ยกเลิก</button>
          <button type="submit" class="px-5 py-2 rounded-[50px] bg-[#506BF4] text-white text-sm hover:opacity-90">บันทึก</button>
        </div>
      </form>
    </div>
  </div>
  
</div>

<script>
(function(){
  const base = '<?= defined('BASE_PATH') ? addslashes(BASE_PATH) : '' ?>';
  const $modal = $('#newRouteModal');
  const $panel = $('#newRoutePanel');
  const $select = $('#selectServer');
  const $hosts = $('#nr_hosts');
  const $dial = $('#nr_dial');
  const $insecure = $('#nr_insecure');

  async function loadServers(){
    try {
      const res = await fetch(base + '/api/caddy/config/', { headers: { 'Accept': 'application/json' } });
      let cfg = null; let t='';
      try { cfg = await res.json(); } catch { try { t = await res.text(); } catch { t=''; } }
      if (!res.ok || !cfg || typeof cfg !== 'object') throw new Error(t || 'Failed to load config');
      const servers = (cfg.apps && cfg.apps.http && cfg.apps.http.servers) ? cfg.apps.http.servers : {};
      $select.empty();
      $select.append('<option value="" disabled selected>-- Choose server --</option>');
      Object.keys(servers).forEach(name => {
        $select.append('<option value="' + name.replace(/"/g,'&quot;') + '">' + name + '</option>');
      });
    } catch (err) {
      Swal.fire({ icon: 'error', title: 'Load servers failed', text: (err && err.message ? err.message : String(err)) });
    }
  }

  function openModal(){
    $modal.removeClass('hidden');
    // animate in
    requestAnimationFrame(() => {
      $panel.removeClass('opacity-0 scale-95').addClass('opacity-100 scale-100');
    });
    $('body').addClass('overflow-hidden');
  }
  function closeModal(){
    // animate out
    $panel.addClass('opacity-0 scale-95').removeClass('opacity-100 scale-100');
    setTimeout(() => { $modal.addClass('hidden'); $('body').removeClass('overflow-hidden'); }, 150);
  }

  $('#btnOpenNewRoute').on('click', function(){
    openModal();
    loadServers();
  });
  $('#btnCloseNewRoute, #btnCloseNewRoute2, #newRouteBackdrop').on('click', function(){
    closeModal();
  });
  $(document).on('keydown', function(e){ if (e.key === 'Escape' && !$modal.hasClass('hidden')) closeModal(); });
  $('#formNewRoute').on('submit', async function(e){
    e.preventDefault();
    const server = String($select.val() || '').trim();
    const hostsRaw = String($hosts.val() || '');
    let hosts = hostsRaw.split(/[\,\n\s]+/).map(s => s.trim()).filter(Boolean);
    const dial = String($dial.val() || '').trim();
    const insecure = !!$insecure.is(':checked');
    if (!server) { await Swal.fire({ icon:'warning', title:'Missing server', text:'Please select a server' }); return; }
    if (!dial) { await Swal.fire({ icon:'warning', title:'Missing dial', text:'Please enter dial' }); return; }
    try {
      // Load current config
      const getRes = await fetch(base + '/api/caddy/config/', { headers: { 'Accept': 'application/json' } });
      let cfg = null; let t='';
      try { cfg = await getRes.json(); } catch { try { t = await getRes.text(); } catch { t=''; } }
      if (!getRes.ok || !cfg || typeof cfg !== 'object') throw new Error(t || 'Failed to load config');
      if (!cfg.apps || !cfg.apps.http || !cfg.apps.http.servers || !cfg.apps.http.servers[server]) throw new Error('Server not found: ' + server);
      const srv = cfg.apps.http.servers[server];
      if (!Array.isArray(srv.routes)) srv.routes = [];
      // Build route
      let route = { handle: [ { handler: 'reverse_proxy', upstreams: [ { dial } ] } ] };
      if (insecure) {
        route.handle[0].transport = { protocol: 'http', tls: { insecure_skip_verify: true } };
      }
      if (hosts && hosts.length) {
        route.match = [ { host: hosts } ];
      }
      // Append
      srv.routes.push(route);
      // Save
      const putRes = await fetch(base + '/api/caddy/load', {
        method: 'POST', headers: { 'Content-Type': 'application/json', 'Accept': 'application/json' }, body: JSON.stringify(cfg)
      });
      let pt=''; let pd=null; try { pd = await putRes.json(); } catch { try { pt = await putRes.text(); } catch { pt=''; } }
      if (!putRes.ok) throw new Error((pd && (pd.error || pd.body)) || pt || 'Save failed');
      // Go to server route page
      window.location.href = base + '/route/' + encodeURIComponent(server);
    } catch (err) {
      Swal.fire({ icon: 'error', title: 'Create route failed', text: (err && err.message ? err.message : String(err)) });
    }
  });
})();
</script>

<!-- New Server Modal -->
<div id="newServerModal" class="fixed inset-0 z-[1000] hidden" aria-hidden="true">
  <div id="newServerBackdrop" class="absolute inset-0 bg-black/70 backdrop-blur-sm transition-opacity"></div>
  <div class="absolute inset-0 flex items-center justify-center p-4">
    <div id="newServerPanel" class="w-full max-w-md bg-theme shadow-2xl rounded-2xl overflow-hidden transform transition-all duration-200 ease-out opacity-0 scale-95">
      <div class="px-4 py-3 border-b border-slate-700 flex items-center justify-between">
        <h3 class="text-lg font-semibold text-theme">Add Server</h3>
        <button id="btnCloseNewServer" class="px-3 py-1.5 rounded text-theme hover:bg-slate-700/50">✕</button>
      </div>
      <form id="formNewServer" class="p-4 space-y-4">
        <div>
          <label for="ns_name" class="block text-sm mb-1 text-theme">Server Name</label>
          <input id="ns_name" type="text" class="w-full input-theme rounded-[55px] text-center text-[16px] h-[50px]" placeholder="myserver" />
        </div>
        <div>
          <label for="ns_listen" class="block text-sm mb-1 text-theme">Listen (comma or newline separated)</label>
          <textarea id="ns_listen" class="w-full input-theme rounded-[14px] text-sm h-[90px]" rows="2" placeholder=":80, :443"></textarea>
        </div>
        <div class="pt-2 flex justify-end gap-2">
          <button type="button" id="btnCloseNewServer2" class="px-5 py-2 rounded-[50px] text-white text-sm bg-slate-600 hover:bg-slate-500">ยกเลิก</button>
          <button type="submit" class="px-5 py-2 rounded-[50px] bg-[#506BF4] text-white text-sm hover:opacity-90">บันทึก</button>
        </div>
      </form>
    </div>
  </div>
</div>

<script>
(function(){
  const base = '<?= defined('BASE_PATH') ? addslashes(BASE_PATH) : '' ?>';
  const $modal = $('#newServerModal');
  const $panel = $('#newServerPanel');
  const $name = $('#ns_name');
  const $listen = $('#ns_listen');

  function openModal(){
    $modal.removeClass('hidden');
    requestAnimationFrame(() => { $panel.removeClass('opacity-0 scale-95').addClass('opacity-100 scale-100'); });
    $('body').addClass('overflow-hidden');
    setTimeout(() => { $name.trigger('focus'); }, 0);
  }
  function closeModal(){
    $panel.addClass('opacity-0 scale-95').removeClass('opacity-100 scale-100');
    setTimeout(() => { $modal.addClass('hidden'); $('body').removeClass('overflow-hidden'); }, 150);
  }

  $('#btnOpenNewServer').on('click', function(){ openModal(); });
  $('#btnCloseNewServer, #btnCloseNewServer2, #newServerBackdrop').on('click', function(){ closeModal(); });
  $(document).on('keydown', function(e){ if (e.key === 'Escape' && !$modal.hasClass('hidden')) closeModal(); });

  $('#formNewServer').on('submit', async function(e){
    e.preventDefault();
    const name = String($name.val() || '').trim();
    const listens = String($listen.val() || '').split(/[\,\n\s]+/).map(s => s.trim()).filter(Boolean);
    if (!name) { await Swal.fire({ icon:'warning', title:'Missing name', text:'Please enter server name' }); return; }
    if (!listens.length) { await Swal.fire({ icon:'warning', title:'Missing addresses', text:'Please enter listen addresses' }); return; }
    try {
      // get config
      const getRes = await fetch(base + '/api/caddy/config/', { headers: { 'Accept': 'application/json' } });
      let cfg = null; let t='';
      try { cfg = await getRes.json(); } catch { try { t = await getRes.text(); } catch { t=''; } }
      if (!getRes.ok || !cfg || typeof cfg !== 'object') throw new Error(t || 'Failed to load config');

      // ensure structure
      if (!cfg.apps) cfg.apps = {};
      if (!cfg.apps.http) cfg.apps.http = {};
      if (!cfg.apps.http.servers) cfg.apps.http.servers = {};
      if (cfg.apps.http.servers[name]) { await Swal.fire({ icon:'error', title:'Duplicate', text:'Server already exists: ' + name }); return; }

      cfg.apps.http.servers[name] = {
        listen: listens,
        routes: []
      };

      const putRes = await fetch(base + '/api/caddy/load', {
        method: 'POST', headers: { 'Content-Type': 'application/json', 'Accept': 'application/json' }, body: JSON.stringify(cfg)
      });
      let pt=''; let pd=null; try { pd = await putRes.json(); } catch { try { pt = await putRes.text(); } catch { pt=''; } }
      if (!putRes.ok) throw new Error((pd && (pd.error || pd.body)) || pt || 'Save failed');
      window.location.href = base + '/route/' + encodeURIComponent(name) + '?new=1';
    } catch (err) {
      Swal.fire({ icon:'error', title:'Create server failed', text:(err && err.message ? err.message : String(err)) });
    }
  });
})();
</script>

<!-- Force-visible rule for opened dropdowns (bypass any global styles) -->
<style>
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