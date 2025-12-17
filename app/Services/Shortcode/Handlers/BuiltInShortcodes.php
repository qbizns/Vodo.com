<?php

namespace App\Services\Shortcode\Handlers;

use App\Models\Shortcode;

/**
 * Built-in Shortcode Handlers
 * 
 * Default shortcodes provided by the system.
 */

// =============================================================================
// Button Shortcode
// =============================================================================

class ButtonShortcode
{
    public function render(array $attrs, ?string $content, array $context, Shortcode $shortcode): string
    {
        $url = $attrs['url'] ?? '#';
        $target = $attrs['target'] ?? '_self';
        $class = $attrs['class'] ?? 'btn btn-primary';
        $size = $attrs['size'] ?? 'md';
        $style = $attrs['style'] ?? 'primary';
        $icon = $attrs['icon'] ?? null;
        $id = $attrs['id'] ?? null;

        // Size classes
        $sizeClass = match ($size) {
            'sm', 'small' => 'btn-sm',
            'lg', 'large' => 'btn-lg',
            default => '',
        };

        // Style classes
        $styleClass = match ($style) {
            'secondary' => 'btn-secondary',
            'success' => 'btn-success',
            'danger' => 'btn-danger',
            'warning' => 'btn-warning',
            'info' => 'btn-info',
            'light' => 'btn-light',
            'dark' => 'btn-dark',
            'link' => 'btn-link',
            'outline-primary' => 'btn-outline-primary',
            'outline-secondary' => 'btn-outline-secondary',
            default => 'btn-primary',
        };

        $classes = trim("btn {$styleClass} {$sizeClass} {$class}");
        $idAttr = $id ? " id=\"{$id}\"" : '';
        $iconHtml = $icon ? "<i class=\"{$icon}\"></i> " : '';
        $text = $content ?? $attrs['text'] ?? 'Click Here';

        return "<a href=\"{$url}\" class=\"{$classes}\" target=\"{$target}\"{$idAttr}>{$iconHtml}{$text}</a>";
    }

    public static function definition(): array
    {
        return [
            'tag' => 'button',
            'name' => 'Button',
            'description' => 'Creates a styled button link',
            'handler_class' => self::class,
            'handler_method' => 'render',
            'category' => 'general',
            'has_content' => true,
            'attributes' => [
                'url' => ['type' => 'string', 'default' => '#', 'description' => 'Link URL'],
                'target' => ['type' => 'enum', 'options' => ['_self', '_blank'], 'default' => '_self'],
                'style' => ['type' => 'enum', 'options' => ['primary', 'secondary', 'success', 'danger', 'warning', 'info', 'light', 'dark', 'link'], 'default' => 'primary'],
                'size' => ['type' => 'enum', 'options' => ['sm', 'md', 'lg'], 'default' => 'md'],
                'class' => ['type' => 'string', 'default' => '', 'description' => 'Additional CSS classes'],
                'icon' => ['type' => 'string', 'description' => 'Icon class (e.g., fa fa-arrow-right)'],
                'id' => ['type' => 'string', 'description' => 'HTML ID attribute'],
            ],
            'examples' => [
                '[button url="/signup" style="success"]Sign Up Now[/button]',
                '[button url="/learn-more" size="lg" icon="fa fa-arrow-right"]Learn More[/button]',
            ],
        ];
    }
}

// =============================================================================
// Alert Shortcode
// =============================================================================

class AlertShortcode
{
    public function render(array $attrs, ?string $content, array $context, Shortcode $shortcode): string
    {
        $type = $attrs['type'] ?? 'info';
        $dismissible = $attrs['dismissible'] ?? false;
        $icon = $attrs['icon'] ?? null;
        $title = $attrs['title'] ?? null;

        $typeClass = match ($type) {
            'success' => 'alert-success',
            'warning' => 'alert-warning',
            'danger', 'error' => 'alert-danger',
            default => 'alert-info',
        };

        $defaultIcon = match ($type) {
            'success' => 'fa fa-check-circle',
            'warning' => 'fa fa-exclamation-triangle',
            'danger', 'error' => 'fa fa-times-circle',
            default => 'fa fa-info-circle',
        };

        $iconClass = $icon ?? $defaultIcon;
        $dismissClass = $dismissible ? ' alert-dismissible fade show' : '';
        $dismissBtn = $dismissible 
            ? '<button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>' 
            : '';

        $titleHtml = $title ? "<strong>{$title}</strong> " : '';
        $iconHtml = "<i class=\"{$iconClass} me-2\"></i>";

        return "<div class=\"alert {$typeClass}{$dismissClass}\" role=\"alert\">{$iconHtml}{$titleHtml}{$content}{$dismissBtn}</div>";
    }

    public static function definition(): array
    {
        return [
            'tag' => 'alert',
            'name' => 'Alert',
            'description' => 'Creates an alert/notification box',
            'handler_class' => self::class,
            'handler_method' => 'render',
            'category' => 'general',
            'has_content' => true,
            'attributes' => [
                'type' => ['type' => 'enum', 'options' => ['info', 'success', 'warning', 'danger', 'error'], 'default' => 'info'],
                'dismissible' => ['type' => 'boolean', 'default' => false],
                'title' => ['type' => 'string', 'description' => 'Alert title/heading'],
                'icon' => ['type' => 'string', 'description' => 'Custom icon class'],
            ],
            'examples' => [
                '[alert type="success"]Operation completed successfully![/alert]',
                '[alert type="warning" dismissible="true" title="Warning"]Please review your input.[/alert]',
            ],
        ];
    }
}

// =============================================================================
// YouTube Embed Shortcode
// =============================================================================

class YoutubeShortcode
{
    public function render(array $attrs, ?string $content, array $context, Shortcode $shortcode): string
    {
        $id = $attrs['id'] ?? $this->extractVideoId($attrs['url'] ?? '');
        
        if (!$id) {
            return '<!-- YouTube shortcode: Missing video ID -->';
        }

        $width = $attrs['width'] ?? '100%';
        $height = $attrs['height'] ?? '400';
        $autoplay = $attrs['autoplay'] ?? false;
        $mute = $attrs['mute'] ?? false;
        $loop = $attrs['loop'] ?? false;
        $controls = $attrs['controls'] ?? true;
        $start = $attrs['start'] ?? null;

        $params = [];
        if ($autoplay) $params[] = 'autoplay=1';
        if ($mute) $params[] = 'mute=1';
        if ($loop) $params[] = 'loop=1&playlist=' . $id;
        if (!$controls) $params[] = 'controls=0';
        if ($start) $params[] = 'start=' . (int)$start;

        $queryString = !empty($params) ? '?' . implode('&', $params) : '';
        $src = "https://www.youtube.com/embed/{$id}{$queryString}";

        return "<div class=\"ratio ratio-16x9\" style=\"max-width:{$width};\"><iframe src=\"{$src}\" width=\"{$width}\" height=\"{$height}\" frameborder=\"0\" allow=\"accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture\" allowfullscreen></iframe></div>";
    }

    protected function extractVideoId(string $url): ?string
    {
        // Match various YouTube URL formats
        $patterns = [
            '/youtube\.com\/watch\?v=([^&]+)/',
            '/youtube\.com\/embed\/([^?]+)/',
            '/youtu\.be\/([^?]+)/',
        ];

        foreach ($patterns as $pattern) {
            if (preg_match($pattern, $url, $matches)) {
                return $matches[1];
            }
        }

        return null;
    }

    public static function definition(): array
    {
        return [
            'tag' => 'youtube',
            'name' => 'YouTube Video',
            'description' => 'Embeds a YouTube video',
            'handler_class' => self::class,
            'handler_method' => 'render',
            'category' => 'embed',
            'has_content' => false,
            'attributes' => [
                'id' => ['type' => 'string', 'description' => 'YouTube video ID'],
                'url' => ['type' => 'string', 'description' => 'YouTube video URL'],
                'width' => ['type' => 'string', 'default' => '100%'],
                'height' => ['type' => 'string', 'default' => '400'],
                'autoplay' => ['type' => 'boolean', 'default' => false],
                'mute' => ['type' => 'boolean', 'default' => false],
                'loop' => ['type' => 'boolean', 'default' => false],
                'controls' => ['type' => 'boolean', 'default' => true],
                'start' => ['type' => 'integer', 'description' => 'Start time in seconds'],
            ],
            'required' => [],
            'examples' => [
                '[youtube id="dQw4w9WgXcQ" /]',
                '[youtube url="https://www.youtube.com/watch?v=dQw4w9WgXcQ" autoplay="true" mute="true" /]',
            ],
        ];
    }
}

// =============================================================================
// Accordion Shortcode
// =============================================================================

class AccordionShortcode
{
    public function render(array $attrs, ?string $content, array $context, Shortcode $shortcode): string
    {
        $id = $attrs['id'] ?? 'accordion-' . uniqid();
        $flush = $attrs['flush'] ?? false;
        $alwaysOpen = $attrs['always_open'] ?? false;

        $flushClass = $flush ? ' accordion-flush' : '';
        $dataParent = $alwaysOpen ? '' : " data-bs-parent=\"#{$id}\"";

        // Parse accordion items from content
        // Expected format: [accordion_item title="..."]content[/accordion_item]
        $items = $this->parseItems($content ?? '', $id, $dataParent);

        return "<div class=\"accordion{$flushClass}\" id=\"{$id}\">{$items}</div>";
    }

    protected function parseItems(string $content, string $accordionId, string $dataParent): string
    {
        $pattern = '/\[accordion_item\s+title="([^"]+)"(?:\s+open="?(true|false)"?)?\](.*?)\[\/accordion_item\]/s';
        $itemIndex = 0;

        return preg_replace_callback($pattern, function ($matches) use ($accordionId, $dataParent, &$itemIndex) {
            $title = $matches[1];
            $isOpen = ($matches[2] ?? '') === 'true';
            $itemContent = $matches[3];
            $itemIndex++;

            $itemId = "{$accordionId}-item-{$itemIndex}";
            $collapseClass = $isOpen ? 'show' : '';
            $expandedAttr = $isOpen ? 'true' : 'false';
            $collapsedClass = $isOpen ? '' : ' collapsed';

            return "
                <div class=\"accordion-item\">
                    <h2 class=\"accordion-header\">
                        <button class=\"accordion-button{$collapsedClass}\" type=\"button\" data-bs-toggle=\"collapse\" data-bs-target=\"#{$itemId}\" aria-expanded=\"{$expandedAttr}\" aria-controls=\"{$itemId}\">
                            {$title}
                        </button>
                    </h2>
                    <div id=\"{$itemId}\" class=\"accordion-collapse collapse {$collapseClass}\"{$dataParent}>
                        <div class=\"accordion-body\">
                            {$itemContent}
                        </div>
                    </div>
                </div>";
        }, $content);
    }

    public static function definition(): array
    {
        return [
            'tag' => 'accordion',
            'name' => 'Accordion',
            'description' => 'Creates a collapsible accordion component',
            'handler_class' => self::class,
            'handler_method' => 'render',
            'category' => 'layout',
            'has_content' => true,
            'parse_nested' => false, // We handle nested parsing ourselves
            'attributes' => [
                'id' => ['type' => 'string', 'description' => 'Accordion ID'],
                'flush' => ['type' => 'boolean', 'default' => false, 'description' => 'Remove borders'],
                'always_open' => ['type' => 'boolean', 'default' => false, 'description' => 'Allow multiple open items'],
            ],
            'examples' => [
                '[accordion]
[accordion_item title="First Item" open="true"]Content for first item[/accordion_item]
[accordion_item title="Second Item"]Content for second item[/accordion_item]
[/accordion]',
            ],
        ];
    }
}

// =============================================================================
// Tabs Shortcode
// =============================================================================

class TabsShortcode
{
    public function render(array $attrs, ?string $content, array $context, Shortcode $shortcode): string
    {
        $id = $attrs['id'] ?? 'tabs-' . uniqid();
        $style = $attrs['style'] ?? 'tabs'; // tabs, pills
        $vertical = $attrs['vertical'] ?? false;
        $justified = $attrs['justified'] ?? false;

        // Parse tab items
        $items = $this->parseItems($content ?? '', $id);

        $navClass = $style === 'pills' ? 'nav-pills' : 'nav-tabs';
        $verticalClass = $vertical ? ' flex-column' : '';
        $justifiedClass = $justified ? ' nav-justified' : '';
        
        $wrapperClass = $vertical ? 'd-flex align-items-start' : '';
        $contentClass = $vertical ? 'flex-grow-1' : '';

        return "<div class=\"{$wrapperClass}\">
            <ul class=\"nav {$navClass}{$verticalClass}{$justifiedClass}\" id=\"{$id}\" role=\"tablist\">
                {$items['nav']}
            </ul>
            <div class=\"tab-content {$contentClass}\" id=\"{$id}-content\">
                {$items['content']}
            </div>
        </div>";
    }

    protected function parseItems(string $content, string $tabsId): array
    {
        $pattern = '/\[tab\s+title="([^"]+)"(?:\s+icon="([^"]+)")?(?:\s+active="?(true|false)"?)?\](.*?)\[\/tab\]/s';
        $navItems = '';
        $contentItems = '';
        $itemIndex = 0;
        $hasActive = false;

        preg_match_all($pattern, $content, $matches, PREG_SET_ORDER);

        foreach ($matches as $match) {
            $title = $match[1];
            $icon = $match[2] ?? null;
            $isActive = ($match[3] ?? '') === 'true';
            $itemContent = $match[4];
            $itemIndex++;

            // First item is active by default if none specified
            if (!$hasActive && ($isActive || $itemIndex === 1)) {
                $isActive = true;
                $hasActive = true;
            } else {
                $isActive = false;
            }

            $itemId = "{$tabsId}-tab-{$itemIndex}";
            $paneId = "{$tabsId}-pane-{$itemIndex}";
            $activeClass = $isActive ? ' active' : '';
            $showClass = $isActive ? ' show active' : '';
            $selectedAttr = $isActive ? 'true' : 'false';
            $iconHtml = $icon ? "<i class=\"{$icon} me-1\"></i>" : '';

            $navItems .= "<li class=\"nav-item\" role=\"presentation\">
                <button class=\"nav-link{$activeClass}\" id=\"{$itemId}\" data-bs-toggle=\"tab\" data-bs-target=\"#{$paneId}\" type=\"button\" role=\"tab\" aria-controls=\"{$paneId}\" aria-selected=\"{$selectedAttr}\">
                    {$iconHtml}{$title}
                </button>
            </li>";

            $contentItems .= "<div class=\"tab-pane fade{$showClass}\" id=\"{$paneId}\" role=\"tabpanel\" aria-labelledby=\"{$itemId}\" tabindex=\"0\">
                {$itemContent}
            </div>";
        }

        return ['nav' => $navItems, 'content' => $contentItems];
    }

    public static function definition(): array
    {
        return [
            'tag' => 'tabs',
            'name' => 'Tabs',
            'description' => 'Creates a tabbed content component',
            'handler_class' => self::class,
            'handler_method' => 'render',
            'category' => 'layout',
            'has_content' => true,
            'parse_nested' => false,
            'attributes' => [
                'id' => ['type' => 'string', 'description' => 'Tabs ID'],
                'style' => ['type' => 'enum', 'options' => ['tabs', 'pills'], 'default' => 'tabs'],
                'vertical' => ['type' => 'boolean', 'default' => false],
                'justified' => ['type' => 'boolean', 'default' => false],
            ],
            'examples' => [
                '[tabs]
[tab title="Home" active="true"]Home content[/tab]
[tab title="Profile"]Profile content[/tab]
[tab title="Settings" icon="fa fa-cog"]Settings content[/tab]
[/tabs]',
            ],
        ];
    }
}

// =============================================================================
// Code Shortcode
// =============================================================================

class CodeShortcode
{
    public function render(array $attrs, ?string $content, array $context, Shortcode $shortcode): string
    {
        $language = $attrs['language'] ?? $attrs['lang'] ?? 'plaintext';
        $filename = $attrs['filename'] ?? null;
        $lineNumbers = $attrs['line_numbers'] ?? true;
        $highlight = $attrs['highlight'] ?? null;

        $content = htmlspecialchars($content ?? '');
        $languageClass = "language-{$language}";
        $lineNumberClass = $lineNumbers ? 'line-numbers' : '';
        $highlightAttr = $highlight ? " data-line=\"{$highlight}\"" : '';

        $header = $filename 
            ? "<div class=\"code-header\"><span class=\"filename\">{$filename}</span><span class=\"language\">{$language}</span></div>" 
            : '';

        return "<div class=\"code-block\">
            {$header}
            <pre class=\"{$lineNumberClass}\"{$highlightAttr}><code class=\"{$languageClass}\">{$content}</code></pre>
        </div>";
    }

    public static function definition(): array
    {
        return [
            'tag' => 'code',
            'name' => 'Code Block',
            'description' => 'Displays formatted code with syntax highlighting',
            'handler_class' => self::class,
            'handler_method' => 'render',
            'category' => 'utility',
            'has_content' => true,
            'content_type' => 'text',
            'attributes' => [
                'language' => ['type' => 'string', 'default' => 'plaintext', 'description' => 'Programming language'],
                'lang' => ['type' => 'string', 'description' => 'Alias for language'],
                'filename' => ['type' => 'string', 'description' => 'Optional filename to display'],
                'line_numbers' => ['type' => 'boolean', 'default' => true],
                'highlight' => ['type' => 'string', 'description' => 'Lines to highlight (e.g., "1,3-5")'],
            ],
            'examples' => [
                '[code language="php"]
<?php
echo "Hello World";
[/code]',
                '[code lang="javascript" filename="app.js" highlight="2"]
const greeting = "Hello";
console.log(greeting);
[/code]',
            ],
        ];
    }
}

// =============================================================================
// Image Shortcode
// =============================================================================

class ImageShortcode
{
    public function render(array $attrs, ?string $content, array $context, Shortcode $shortcode): string
    {
        $src = $attrs['src'] ?? $attrs['url'] ?? '';
        
        if (empty($src)) {
            return '<!-- Image shortcode: Missing src -->';
        }

        $alt = $attrs['alt'] ?? '';
        $title = $attrs['title'] ?? '';
        $width = $attrs['width'] ?? '';
        $height = $attrs['height'] ?? '';
        $class = $attrs['class'] ?? 'img-fluid';
        $align = $attrs['align'] ?? '';
        $link = $attrs['link'] ?? '';
        $caption = $attrs['caption'] ?? $content;
        $lazy = $attrs['lazy'] ?? true;

        // Build attributes
        $imgAttrs = ["src=\"{$src}\"", "alt=\"{$alt}\"", "class=\"{$class}\""];
        
        if ($title) $imgAttrs[] = "title=\"{$title}\"";
        if ($width) $imgAttrs[] = "width=\"{$width}\"";
        if ($height) $imgAttrs[] = "height=\"{$height}\"";
        if ($lazy) $imgAttrs[] = 'loading="lazy"';

        $img = '<img ' . implode(' ', $imgAttrs) . '>';

        // Wrap in link if specified
        if ($link) {
            $linkTarget = $link === 'lightbox' ? '#' : $link;
            $linkClass = $link === 'lightbox' ? ' data-lightbox="image"' : '';
            $img = "<a href=\"{$linkTarget}\"{$linkClass}>{$img}</a>";
        }

        // Add caption if specified
        if ($caption) {
            $alignClass = $align ? " text-{$align}" : '';
            return "<figure class=\"figure{$alignClass}\">
                {$img}
                <figcaption class=\"figure-caption\">{$caption}</figcaption>
            </figure>";
        }

        // Apply alignment
        if ($align) {
            $alignClass = match ($align) {
                'left' => 'float-start me-3',
                'right' => 'float-end ms-3',
                'center' => 'd-block mx-auto',
                default => '',
            };
            return "<div class=\"{$alignClass}\">{$img}</div>";
        }

        return $img;
    }

    public static function definition(): array
    {
        return [
            'tag' => 'image',
            'name' => 'Image',
            'description' => 'Displays an image with optional caption and styling',
            'handler_class' => self::class,
            'handler_method' => 'render',
            'category' => 'media',
            'has_content' => true,
            'attributes' => [
                'src' => ['type' => 'string', 'description' => 'Image URL'],
                'url' => ['type' => 'string', 'description' => 'Alias for src'],
                'alt' => ['type' => 'string', 'default' => '', 'description' => 'Alt text'],
                'title' => ['type' => 'string', 'description' => 'Title attribute'],
                'width' => ['type' => 'string', 'description' => 'Width in pixels or percentage'],
                'height' => ['type' => 'string', 'description' => 'Height in pixels'],
                'class' => ['type' => 'string', 'default' => 'img-fluid'],
                'align' => ['type' => 'enum', 'options' => ['left', 'right', 'center']],
                'link' => ['type' => 'string', 'description' => 'Link URL or "lightbox"'],
                'caption' => ['type' => 'string', 'description' => 'Image caption'],
                'lazy' => ['type' => 'boolean', 'default' => true],
            ],
            'required' => ['src'],
            'examples' => [
                '[image src="/images/photo.jpg" alt="A photo" /]',
                '[image src="/images/photo.jpg" align="center" caption="Beautiful sunset" /]',
            ],
        ];
    }
}

// =============================================================================
// Column Layout Shortcode
// =============================================================================

class RowShortcode
{
    public function render(array $attrs, ?string $content, array $context, Shortcode $shortcode): string
    {
        $class = $attrs['class'] ?? '';
        $gutter = $attrs['gutter'] ?? '';
        $align = $attrs['align'] ?? '';
        $justify = $attrs['justify'] ?? '';

        $classes = ['row'];
        
        if ($gutter) $classes[] = "g-{$gutter}";
        if ($align) $classes[] = "align-items-{$align}";
        if ($justify) $classes[] = "justify-content-{$justify}";
        if ($class) $classes[] = $class;

        $classString = implode(' ', $classes);

        return "<div class=\"{$classString}\">{$content}</div>";
    }

    public static function definition(): array
    {
        return [
            'tag' => 'row',
            'name' => 'Row',
            'description' => 'Creates a Bootstrap row for column layout',
            'handler_class' => self::class,
            'handler_method' => 'render',
            'category' => 'layout',
            'has_content' => true,
            'attributes' => [
                'class' => ['type' => 'string', 'default' => ''],
                'gutter' => ['type' => 'enum', 'options' => ['0', '1', '2', '3', '4', '5']],
                'align' => ['type' => 'enum', 'options' => ['start', 'center', 'end', 'stretch']],
                'justify' => ['type' => 'enum', 'options' => ['start', 'center', 'end', 'between', 'around', 'evenly']],
            ],
            'examples' => [
                '[row gutter="4"]
[col size="6"]Left column[/col]
[col size="6"]Right column[/col]
[/row]',
            ],
        ];
    }
}

class ColShortcode
{
    public function render(array $attrs, ?string $content, array $context, Shortcode $shortcode): string
    {
        $size = $attrs['size'] ?? '';
        $sm = $attrs['sm'] ?? '';
        $md = $attrs['md'] ?? '';
        $lg = $attrs['lg'] ?? '';
        $xl = $attrs['xl'] ?? '';
        $class = $attrs['class'] ?? '';
        $offset = $attrs['offset'] ?? '';
        $order = $attrs['order'] ?? '';

        $classes = [];
        
        if ($size) {
            $classes[] = "col-{$size}";
        } else {
            $classes[] = 'col';
        }
        
        if ($sm) $classes[] = "col-sm-{$sm}";
        if ($md) $classes[] = "col-md-{$md}";
        if ($lg) $classes[] = "col-lg-{$lg}";
        if ($xl) $classes[] = "col-xl-{$xl}";
        if ($offset) $classes[] = "offset-{$offset}";
        if ($order) $classes[] = "order-{$order}";
        if ($class) $classes[] = $class;

        $classString = implode(' ', $classes);

        return "<div class=\"{$classString}\">{$content}</div>";
    }

    public static function definition(): array
    {
        return [
            'tag' => 'col',
            'name' => 'Column',
            'description' => 'Creates a Bootstrap column',
            'handler_class' => self::class,
            'handler_method' => 'render',
            'category' => 'layout',
            'has_content' => true,
            'attributes' => [
                'size' => ['type' => 'string', 'description' => 'Column width (1-12 or auto)'],
                'sm' => ['type' => 'string', 'description' => 'Width on small screens'],
                'md' => ['type' => 'string', 'description' => 'Width on medium screens'],
                'lg' => ['type' => 'string', 'description' => 'Width on large screens'],
                'xl' => ['type' => 'string', 'description' => 'Width on extra large screens'],
                'offset' => ['type' => 'string', 'description' => 'Column offset'],
                'order' => ['type' => 'string', 'description' => 'Column order'],
                'class' => ['type' => 'string', 'default' => ''],
            ],
            'examples' => [
                '[col size="6"]Half width[/col]',
                '[col md="4" lg="3"]Responsive column[/col]',
            ],
        ];
    }
}

// =============================================================================
// Built-in Shortcode Collection
// =============================================================================

class BuiltInShortcodes
{
    /**
     * Get all built-in shortcode definitions
     */
    public static function all(): array
    {
        return [
            ButtonShortcode::definition(),
            AlertShortcode::definition(),
            YoutubeShortcode::definition(),
            AccordionShortcode::definition(),
            TabsShortcode::definition(),
            CodeShortcode::definition(),
            ImageShortcode::definition(),
            RowShortcode::definition(),
            ColShortcode::definition(),
        ];
    }

    /**
     * Register all built-in shortcodes
     */
    public static function register(\App\Services\Shortcode\ShortcodeRegistry $registry): void
    {
        foreach (self::all() as $definition) {
            $definition['system'] = true;
            $registry->register($definition, 'system');
        }
    }
}
