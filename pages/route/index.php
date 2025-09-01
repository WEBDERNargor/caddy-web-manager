<?php
header('Content-Type: text/html; charset=utf-8');
$layout->setLayout('auth');
$config = getConfig();
$setHead(<<<HTML
<title> All Routes - {$config['web']['name']}</title>
HTML);

// Direct Caddy Admin API URL
$caddyUrl = rtrim($config['web']['caddy_url'] ?? 'http://127.0.0.1:2019', '/');
$url = $caddyUrl . '/config/';

$curl = curl_init();
curl_setopt_array($curl, array(
    CURLOPT_URL => $url,
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_ENCODING => '',
    CURLOPT_MAXREDIRS => 10,
    CURLOPT_CONNECTTIMEOUT => 2,
    CURLOPT_TIMEOUT => 6,
    CURLOPT_FOLLOWLOCATION => true,
    CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
    CURLOPT_CUSTOMREQUEST => 'GET',
));
$response = curl_exec($curl);
curl_close($curl);

$data = json_decode($response ?? '', true);

// Flatten routes across servers
$rows = [];
if (is_array($data)) {
    $apps = $data['apps'] ?? null;
    if (is_array($apps)) {
        $http = $apps['http'] ?? null;
        if (is_array($http)) {
            $srvMap = $http['servers'] ?? null;
            if (is_array($srvMap)) {
                foreach ($srvMap as $srvName => $srv) {
                    $listens = [];
                    if (!empty($srv['listen']) && is_array($srv['listen'])) {
                        foreach ($srv['listen'] as $ln) { if (is_string($ln)) $listens[] = $ln; }
                    }
                    $routes = $srv['routes'] ?? [];
                    if (is_array($routes)) {
                        foreach ($routes as $r) {
                            // Extract hosts
                            $hosts = [];
                            if (!empty($r['match']) && is_array($r['match'])) {
                                foreach ($r['match'] as $m) {
                                    if (isset($m['host'])) {
                                        foreach ((array)$m['host'] as $h) { $hosts[] = $h; }
                                    }
                                }
                            }
                            $hosts = array_values(array_unique($hosts));
                            // Extract dials
                            $dials = [];
                            if (!empty($r['handle']) && is_array($r['handle'])) {
                                foreach ($r['handle'] as $h) {
                                    if (is_array($h) && ($h['handler'] ?? '') === 'reverse_proxy') {
                                        if (!empty($h['upstreams']) && is_array($h['upstreams'])) {
                                            foreach ($h['upstreams'] as $u) { if (isset($u['dial'])) $dials[] = (string)$u['dial']; }
                                        }
                                    }
                                }
                            }
                            $dials = array_values(array_unique($dials));
                            $rows[] = [
                                'server' => (string)$srvName,
                                'listen' => implode(', ', $listens),
                                'domain' => implode(', ', $hosts),
                                'dial'   => implode(', ', $dials),
                            ];
                        }
                    }
                }
            }
        }
    }
}
?>

<div class="max-w-7xl mx-auto px-4 py-8">
    <div class="flex items-center justify-between mb-6">
        <h1 class="text-2xl font-bold">All Routes</h1>
        <div class="flex items-center gap-3">
            <a href="<?= defined('BASE_PATH') ? BASE_PATH : '' ?>/" class="text-sm text-theme hover:underline">← Back</a>
        </div>
    </div>

    <div class="bg-theme shadow rounded-lg p-4 overflow-x-auto">
        <table id="allRoutesTable" class="min-w-full display nowrap stripe hover bg-theme" style="width:100%">
            <thead>
                <tr>
                    <th class="text-left text-theme">#</th>
                    <th class="text-left text-theme">Server</th>
                    <th class="text-left text-theme">Listen</th>
                    <th class="text-left text-theme">Domain</th>
                    <th class="text-left text-theme">Dial</th>
                    <th class="text-left text-theme">Action</th>
                </tr>
            </thead>
            <tbody>
                <?php if (!empty($rows)): ?>
                    <?php foreach ($rows as $i => $row): ?>
                        <tr>
                            <td><?= $i + 1 ?></td>
                            <td class="text-theme"><?= htmlspecialchars($row['server']) ?></td>
                            <td class="text-theme"><?= htmlspecialchars($row['listen']) ?></td>
                            <td class="text-theme"><?= htmlspecialchars($row['domain']) ?></td>
                            <td class="text-theme"><?= htmlspecialchars($row['dial']) ?></td>
                            <td>
                                <a href="<?= defined('BASE_PATH') ? BASE_PATH : '' ?>/route/<?= urlencode($row['server']) ?>"
                                   class="px-3 py-1 inline-block rounded bg-blue-600 text-white text-xs hover:bg-blue-700">Manage</a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<script>
$(function(){
    if ($.fn.DataTable) {
        const table = $('#allRoutesTable').DataTable({
            responsive: true,
            pageLength: 10,
            lengthMenu: [10, 25, 50, 100],
            order: [[0, 'asc']],
            columnDefs: [ { targets: '_all', className: 'text-theme' } ],
            language: {
                search: 'Search:',
                lengthMenu: 'Show _MENU_',
                info: 'Showing _START_ to _END_ of _TOTAL_ entries',
                emptyTable: 'No routes found.'
            }
        });
        // Apply theme colors similar to other tables
        function applyDTTheme() {
            const $wrap = $('#allRoutesTable').closest('.dataTables_wrapper, .dt-container');
            $wrap.addClass('text-theme');
            $wrap.find('.dataTables_length, .dataTables_filter, .dt-length, .dt-search').addClass('text-theme');
            $wrap.find('.dataTables_length *, .dataTables_filter *, .dt-length *, .dt-search *').addClass('text-theme');
        }
        applyDTTheme();
        $('#allRoutesTable').on('draw.dt init.dt', applyDTTheme);
        document.addEventListener('theme:changed', applyDTTheme);
        setTimeout(() => { table.columns.adjust().draw(false); applyDTTheme(); }, 50);
    }
});
</script>
