<?php

namespace App\Services\View;

use DOMDocument;
use DOMXPath;
use DOMNode;
use DOMElement;
use Illuminate\Support\Facades\View;
use Illuminate\Support\Facades\Blade;

class ViewExtender
{
    protected ViewExtensionRegistry $registry;

    /**
     * Valid positions for modifications.
     */
    public const POSITION_BEFORE = 'before';
    public const POSITION_AFTER = 'after';
    public const POSITION_INSIDE_START = 'inside_start';  // prepend
    public const POSITION_INSIDE_END = 'inside_end';      // append
    public const POSITION_REPLACE = 'replace';
    public const POSITION_ATTRIBUTES = 'attributes';
    public const POSITION_REMOVE = 'remove';

    public function __construct(?ViewExtensionRegistry $registry = null)
    {
        $this->registry = $registry ?? ViewExtensionRegistry::getInstance();
    }

    /**
     * Process a rendered view and apply all registered extensions.
     *
     * @param string $viewName The view name
     * @param string $html The rendered HTML
     * @return string Modified HTML
     */
    public function process(string $viewName, string $html): string
    {
        // Check for complete replacement first
        $replacement = $this->registry->getReplacement($viewName);
        if ($replacement) {
            // Render the replacement view instead
            // Note: The replacement view should have access to original data via composers
            return $html; // Let the view system handle it
        }

        // Get extensions for this view
        $extensions = $this->registry->getExtensions($viewName);
        
        if (empty($extensions)) {
            return $html;
        }

        // Process XPath modifications
        return $this->applyExtensions($html, $extensions);
    }

    /**
     * Apply XPath-based extensions to HTML.
     */
    protected function applyExtensions(string $html, array $extensions): string
    {
        // Wrap in root element if needed for valid XML
        $wrappedHtml = $this->wrapHtml($html);
        
        // Create DOM document
        $doc = new DOMDocument('1.0', 'UTF-8');
        $doc->preserveWhiteSpace = true;
        $doc->formatOutput = false;
        
        // Suppress errors for HTML5 tags
        libxml_use_internal_errors(true);
        
        // Load HTML
        $doc->loadHTML(
            mb_convert_encoding($wrappedHtml, 'HTML-ENTITIES', 'UTF-8'),
            LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD
        );
        
        libxml_clear_errors();

        // Create XPath instance
        $xpath = new DOMXPath($doc);

        // Apply each extension
        foreach ($extensions as $ext) {
            $modification = $ext['modification'];
            $this->applyModification($doc, $xpath, $modification);
        }

        // Get the modified HTML
        $result = $doc->saveHTML();
        
        // Unwrap if we wrapped it
        $result = $this->unwrapHtml($result);

        return $result;
    }

    /**
     * Apply a single modification.
     */
    protected function applyModification(DOMDocument $doc, DOMXPath $xpath, array $modification): void
    {
        $selector = $modification['selector'] ?? $modification['xpath'] ?? null;
        $position = $modification['position'] ?? self::POSITION_AFTER;
        $content = $modification['content'] ?? $modification['html'] ?? '';
        $view = $modification['view'] ?? null;
        $attributes = $modification['attributes'] ?? [];

        if (!$selector) {
            return;
        }

        // If a view is specified, render it
        if ($view && empty($content)) {
            $viewData = $modification['view_data'] ?? [];
            $content = View::make($view, $viewData)->render();
        }

        // Find target nodes
        $nodes = $xpath->query($selector);
        
        if ($nodes === false || $nodes->length === 0) {
            // Selector didn't match anything - that's okay, view might not have that element
            return;
        }

        // Apply modification to each matched node
        foreach ($nodes as $node) {
            $this->applyToNode($doc, $node, $position, $content, $attributes);
        }
    }

    /**
     * Apply modification to a specific node.
     */
    protected function applyToNode(
        DOMDocument $doc,
        DOMNode $node,
        string $position,
        string $content,
        array $attributes
    ): void {
        switch ($position) {
            case self::POSITION_BEFORE:
                $this->insertBefore($doc, $node, $content);
                break;

            case self::POSITION_AFTER:
                $this->insertAfter($doc, $node, $content);
                break;

            case self::POSITION_INSIDE_START:
            case 'prepend':
                $this->prepend($doc, $node, $content);
                break;

            case self::POSITION_INSIDE_END:
            case 'append':
                $this->append($doc, $node, $content);
                break;

            case self::POSITION_REPLACE:
                $this->replace($doc, $node, $content);
                break;

            case self::POSITION_ATTRIBUTES:
                $this->modifyAttributes($node, $attributes);
                break;

            case self::POSITION_REMOVE:
                $this->remove($node);
                break;
        }
    }

    /**
     * Insert content before a node.
     */
    protected function insertBefore(DOMDocument $doc, DOMNode $node, string $content): void
    {
        $fragment = $this->createFragment($doc, $content);
        if ($fragment && $node->parentNode) {
            $node->parentNode->insertBefore($fragment, $node);
        }
    }

    /**
     * Insert content after a node.
     */
    protected function insertAfter(DOMDocument $doc, DOMNode $node, string $content): void
    {
        $fragment = $this->createFragment($doc, $content);
        if ($fragment && $node->parentNode) {
            if ($node->nextSibling) {
                $node->parentNode->insertBefore($fragment, $node->nextSibling);
            } else {
                $node->parentNode->appendChild($fragment);
            }
        }
    }

    /**
     * Prepend content inside a node.
     */
    protected function prepend(DOMDocument $doc, DOMNode $node, string $content): void
    {
        $fragment = $this->createFragment($doc, $content);
        if ($fragment) {
            if ($node->firstChild) {
                $node->insertBefore($fragment, $node->firstChild);
            } else {
                $node->appendChild($fragment);
            }
        }
    }

    /**
     * Append content inside a node.
     */
    protected function append(DOMDocument $doc, DOMNode $node, string $content): void
    {
        $fragment = $this->createFragment($doc, $content);
        if ($fragment) {
            $node->appendChild($fragment);
        }
    }

    /**
     * Replace a node with new content.
     */
    protected function replace(DOMDocument $doc, DOMNode $node, string $content): void
    {
        $fragment = $this->createFragment($doc, $content);
        if ($fragment && $node->parentNode) {
            $node->parentNode->replaceChild($fragment, $node);
        }
    }

    /**
     * Modify attributes of a node.
     */
    protected function modifyAttributes(DOMNode $node, array $attributes): void
    {
        if (!($node instanceof DOMElement)) {
            return;
        }

        foreach ($attributes as $action => $attrs) {
            switch ($action) {
                case 'set':
                case 'add':
                    foreach ($attrs as $name => $value) {
                        $node->setAttribute($name, $value);
                    }
                    break;

                case 'remove':
                    foreach ((array) $attrs as $name) {
                        $node->removeAttribute($name);
                    }
                    break;

                case 'add_class':
                    $currentClass = $node->getAttribute('class');
                    $newClasses = is_array($attrs) ? implode(' ', $attrs) : $attrs;
                    $node->setAttribute('class', trim($currentClass . ' ' . $newClasses));
                    break;

                case 'remove_class':
                    $currentClass = $node->getAttribute('class');
                    $classesToRemove = is_array($attrs) ? $attrs : [$attrs];
                    $classes = array_filter(
                        explode(' ', $currentClass),
                        fn($c) => !in_array(trim($c), $classesToRemove)
                    );
                    $node->setAttribute('class', implode(' ', $classes));
                    break;
            }
        }
    }

    /**
     * Remove a node.
     */
    protected function remove(DOMNode $node): void
    {
        if ($node->parentNode) {
            $node->parentNode->removeChild($node);
        }
    }

    /**
     * Create a document fragment from HTML string.
     */
    protected function createFragment(DOMDocument $doc, string $html): ?\DOMDocumentFragment
    {
        if (empty(trim($html))) {
            return null;
        }

        $fragment = $doc->createDocumentFragment();
        
        // Suppress errors for HTML5 tags
        libxml_use_internal_errors(true);
        
        // The appendXML method requires valid XML, so we need to handle HTML
        // Create a temporary document to parse the HTML
        $tempDoc = new DOMDocument('1.0', 'UTF-8');
        $tempDoc->loadHTML(
            '<div id="__temp__">' . mb_convert_encoding($html, 'HTML-ENTITIES', 'UTF-8') . '</div>',
            LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD
        );
        
        libxml_clear_errors();

        // Find our temp wrapper
        $tempWrapper = $tempDoc->getElementById('__temp__');
        if ($tempWrapper) {
            // Import and append each child node
            foreach ($tempWrapper->childNodes as $child) {
                $imported = $doc->importNode($child, true);
                $fragment->appendChild($imported);
            }
        }

        return $fragment;
    }

    /**
     * Wrap HTML in a root element for valid parsing.
     */
    protected function wrapHtml(string $html): string
    {
        // Check if HTML already has proper structure
        if (stripos($html, '<html') !== false || stripos($html, '<!DOCTYPE') !== false) {
            return $html;
        }

        return '<div id="__view_extender_root__">' . $html . '</div>';
    }

    /**
     * Remove the wrapper we added.
     */
    protected function unwrapHtml(string $html): string
    {
        // Remove the wrapper div we added
        $pattern = '/<div id="__view_extender_root__">(.*)<\/div>/s';
        if (preg_match($pattern, $html, $matches)) {
            return $matches[1];
        }

        // Also handle if doctype was added
        $html = preg_replace('/<!DOCTYPE[^>]*>/i', '', $html);
        $html = preg_replace('/<html[^>]*>/i', '', $html);
        $html = preg_replace('/<\/html>/i', '', $html);
        $html = preg_replace('/<body[^>]*>/i', '', $html);
        $html = preg_replace('/<\/body>/i', '', $html);
        $html = preg_replace('/<head[^>]*>.*<\/head>/is', '', $html);

        return trim($html);
    }

    /**
     * Helper to build common XPath selectors.
     */
    public static function selector(): ViewSelectorBuilder
    {
        return new ViewSelectorBuilder();
    }
}

/**
 * Fluent builder for XPath selectors.
 */
class ViewSelectorBuilder
{
    protected string $selector = '';

    /**
     * Select by ID.
     */
    public function id(string $id): self
    {
        $this->selector = "//*[@id='{$id}']";
        return $this;
    }

    /**
     * Select by class.
     */
    public function class(string $class): self
    {
        $this->selector = "//*[contains(@class, '{$class}')]";
        return $this;
    }

    /**
     * Select by tag name.
     */
    public function tag(string $tag): self
    {
        $this->selector = "//{$tag}";
        return $this;
    }

    /**
     * Select by data attribute.
     */
    public function data(string $attribute, ?string $value = null): self
    {
        if ($value !== null) {
            $this->selector = "//*[@data-{$attribute}='{$value}']";
        } else {
            $this->selector = "//*[@data-{$attribute}]";
        }
        return $this;
    }

    /**
     * Select by name attribute.
     */
    public function name(string $name): self
    {
        $this->selector = "//*[@name='{$name}']";
        return $this;
    }

    /**
     * Select by any attribute.
     */
    public function attr(string $attribute, ?string $value = null): self
    {
        if ($value !== null) {
            $this->selector = "//*[@{$attribute}='{$value}']";
        } else {
            $this->selector = "//*[@{$attribute}]";
        }
        return $this;
    }

    /**
     * Select form fields.
     */
    public function field(string $name): self
    {
        $this->selector = "//input[@name='{$name}']|//select[@name='{$name}']|//textarea[@name='{$name}']";
        return $this;
    }

    /**
     * Select within context.
     */
    public function within(string $parentSelector): self
    {
        $this->selector = $parentSelector . $this->selector;
        return $this;
    }

    /**
     * Get the built selector.
     */
    public function get(): string
    {
        return $this->selector;
    }

    /**
     * Get as string.
     */
    public function __toString(): string
    {
        return $this->selector;
    }
}
