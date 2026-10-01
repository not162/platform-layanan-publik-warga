<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>OpenAPI & Swagger UI - Platform Layanan Publik Warga</title>
    <link rel="icon" type="image/svg+xml" href="{{ asset('images/favicon.svg') }}">
    <link rel="stylesheet" href="https://unpkg.com/swagger-ui-dist@5/swagger-ui.css">
    <style>
        body {
            margin: 0;
            background: #FAFAFA;
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif;
        }
        .swagger-ui .topbar {
            background-color: #1B365D;
            padding: 10px 0;
            border-bottom: 2px solid #3A9696;
        }
        .swagger-ui .topbar .topbar-wrapper {
            max-width: 1200px;
            margin: 0 auto;
            padding: 0 20px;
            display: flex;
            align-items: center;
            justify-content: space-between;
        }
        .swagger-ui .topbar-wrapper a span {
            color: #FFFFFF;
            font-size: 1.25rem;
            font-weight: 700;
        }
        .topbar-nav {
            display: flex;
            gap: 15px;
        }
        .topbar-nav a {
            color: #E2E8F0;
            text-decoration: none;
            font-size: 0.9rem;
            font-weight: 500;
            transition: color 0.2s;
        }
        .topbar-nav a:hover {
            color: #3A9696;
        }
        .swagger-ui .info {
            margin: 30px 0;
        }
        .swagger-ui .scheme-container {
            background: #FFFFFF;
            box-shadow: 0 1px 3px rgba(0,0,0,0.05);
            margin-bottom: 30px;
        }
    </style>
</head>
<body>
    <div id="swagger-ui"></div>

    <script src="https://unpkg.com/swagger-ui-dist@5/swagger-ui-bundle.js"></script>
    <script src="https://unpkg.com/swagger-ui-dist@5/swagger-ui-standalone-preset.js"></script>
    <script>
        window.onload = function() {
            window.ui = SwaggerUIBundle({
                url: "/docs/openapi.yaml",
                dom_id: '#swagger-ui',
                deepLinking: true,
                presets: [
                    SwaggerUIBundle.presets.apis,
                    SwaggerUIStandalonePreset
                ],
                plugins: [
                    SwaggerUIBundle.plugins.DownloadUrl
                ],
                layout: "StandaloneLayout",
                filter: true,
                tryItOutEnabled: true,
                persistAuthorization: true
            });
        };
    </script>
</body>
</html>
