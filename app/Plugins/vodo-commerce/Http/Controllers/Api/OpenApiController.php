<?php

declare(strict_types=1);

namespace VodoCommerce\Http\Controllers\Api;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Routing\Controller;
use VodoCommerce\Api\CommerceOpenApiGenerator;

/**
 * OpenAPI Documentation Controller
 *
 * Serves the OpenAPI specification for the commerce API.
 */
class OpenApiController extends Controller
{
    public function __construct(
        protected CommerceOpenApiGenerator $generator
    ) {
    }

    /**
     * Get the OpenAPI specification as JSON.
     */
    public function json(): JsonResponse
    {
        $spec = $this->generator->generate();

        return response()->json($spec)
            ->header('Cache-Control', 'public, max-age=3600');
    }

    /**
     * Get the OpenAPI specification as YAML.
     */
    public function yaml(): Response
    {
        $yaml = $this->generator->toYaml();

        return response($yaml, 200)
            ->header('Content-Type', 'text/yaml')
            ->header('Cache-Control', 'public, max-age=3600');
    }

    /**
     * Render the interactive API documentation UI.
     */
    public function ui(Request $request): Response
    {
        $specUrl = route('api.v1.commerce.openapi.json');
        $theme = $request->get('theme', 'light');

        $html = $this->renderSwaggerUI($specUrl, $theme);

        return response($html, 200)
            ->header('Content-Type', 'text/html');
    }

    /**
     * Render Swagger UI HTML.
     *
     * @param string $specUrl URL to the OpenAPI spec
     * @param string $theme Theme (light/dark)
     * @return string
     */
    protected function renderSwaggerUI(string $specUrl, string $theme): string
    {
        $title = 'Vodo Commerce API Documentation';
        $darkStyles = $theme === 'dark' ? $this->getDarkModeStyles() : '';

        return <<<HTML
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{$title}</title>
    <link rel="stylesheet" type="text/css" href="https://unpkg.com/swagger-ui-dist@5.11.0/swagger-ui.css" />
    <style>
        html { box-sizing: border-box; overflow: -moz-scrollbars-vertical; overflow-y: scroll; }
        *, *:before, *:after { box-sizing: inherit; }
        body { margin: 0; background: #fafafa; }
        .swagger-ui .topbar { display: none; }
        .swagger-ui .info { margin: 30px 0; }
        .swagger-ui .info .title { font-size: 2rem; }
        .swagger-ui .info .description { font-size: 1rem; line-height: 1.6; }
        .swagger-ui .opblock .opblock-summary-operation-id { font-family: monospace; }
        .swagger-ui .opblock-tag { font-size: 1.2rem; }
        .swagger-ui .servers { padding: 16px 0; }
        .api-header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 20px;
            text-align: center;
        }
        .api-header h1 { margin: 0; font-size: 1.8rem; }
        .api-header p { margin: 8px 0 0; opacity: 0.9; }
        {$darkStyles}
    </style>
</head>
<body>
    <div class="api-header">
        <h1>Vodo Commerce API</h1>
        <p>Complete REST API for managing your online store</p>
    </div>
    <div id="swagger-ui"></div>
    <script src="https://unpkg.com/swagger-ui-dist@5.11.0/swagger-ui-bundle.js"></script>
    <script src="https://unpkg.com/swagger-ui-dist@5.11.0/swagger-ui-standalone-preset.js"></script>
    <script>
        window.onload = function() {
            window.ui = SwaggerUIBundle({
                url: "{$specUrl}",
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
                persistAuthorization: true,
                filter: true,
                withCredentials: true,
                tagsSorter: 'alpha',
                operationsSorter: 'alpha',
                docExpansion: 'list',
                defaultModelsExpandDepth: 2,
                defaultModelExpandDepth: 2,
                syntaxHighlight: {
                    activate: true,
                    theme: '{$theme}'
                }
            });
        };
    </script>
</body>
</html>
HTML;
    }

    /**
     * Get dark mode styles.
     *
     * @return string
     */
    protected function getDarkModeStyles(): string
    {
        return <<<'CSS'
body { background: #1a1a2e; }
.swagger-ui { background: #1a1a2e; }
.swagger-ui .info .title { color: #e0e0e0; }
.swagger-ui .info .description { color: #b0b0b0; }
.swagger-ui .scheme-container { background: #16213e; }
.swagger-ui .opblock { background: #16213e; border-color: #2a3a5e; }
.swagger-ui .opblock .opblock-summary { border-color: #2a3a5e; }
.swagger-ui .opblock-tag { color: #e0e0e0; }
.swagger-ui .btn { color: #e0e0e0; }
.swagger-ui input, .swagger-ui select { background: #0f0f23; color: #e0e0e0; border-color: #2a3a5e; }
.swagger-ui table thead tr td, .swagger-ui table thead tr th { color: #e0e0e0; }
.swagger-ui .response-col_status { color: #e0e0e0; }
.swagger-ui .model-box { background: #0f0f23; }
.swagger-ui section.models { border-color: #2a3a5e; }
.swagger-ui section.models .model-container { background: #16213e; }
.swagger-ui .model { color: #e0e0e0; }
.swagger-ui .prop-type { color: #82aaff; }
.swagger-ui .prop-format { color: #c792ea; }
.api-header { background: linear-gradient(135deg, #0f0f23 0%, #16213e 100%); }
CSS;
    }

    /**
     * Render Redoc UI (alternative documentation).
     */
    public function redoc(): Response
    {
        $specUrl = route('api.v1.commerce.openapi.json');

        $html = <<<HTML
<!DOCTYPE html>
<html>
<head>
    <title>Vodo Commerce API - Redoc</title>
    <meta charset="utf-8"/>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link href="https://fonts.googleapis.com/css?family=Montserrat:300,400,700|Roboto:300,400,700" rel="stylesheet">
    <style>
        body { margin: 0; padding: 0; }
    </style>
</head>
<body>
    <redoc spec-url='{$specUrl}'></redoc>
    <script src="https://cdn.redoc.ly/redoc/latest/bundles/redoc.standalone.js"></script>
</body>
</html>
HTML;

        return response($html, 200)
            ->header('Content-Type', 'text/html');
    }
}
