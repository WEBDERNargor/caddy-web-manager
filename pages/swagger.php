<?php
$layout->setLayout('default');
$setHead(<<<HTML
<title>Swagger UI - Caddy Admin Proxy API</title>
<link rel="stylesheet" href="https://unpkg.com/swagger-ui-dist@5.17.14/swagger-ui.css" />
<style> body{margin:0} .topbar{display:none} </style>
HTML);
?>
<div class="max-w-7xl mx-auto px-0 py-0">
  <div id="swagger-ui"></div>
</div>
<script src="https://unpkg.com/swagger-ui-dist@5.17.14/swagger-ui-bundle.js"></script>
<script>
  window.addEventListener('DOMContentLoaded', () => {
    const params = new URLSearchParams(location.search);
    const apiKey = params.get('api_key') || params.get('x_api_key');
    const bearer = params.get('bearer');

    const ui = SwaggerUIBundle({
      url: '/api/readfile/other.php?file=openapi.json',
      dom_id: '#swagger-ui',
      deepLinking: true,
      presets: [SwaggerUIBundle.presets.apis],
      layout: 'BaseLayout',
      persistAuthorization: true,
      onComplete: function() {
        try {
          if (apiKey) {
            ui.preauthorizeApiKey('ApiKeyHeader', apiKey);
            ui.preauthorizeApiKey('ApiKeyQuery', apiKey);
          }
          if (bearer) {
            ui.preauthorizeApiKey('BearerAuth', bearer);
          }
        } catch (e) { /* ignore */ }
      }
    });
  });
</script>
