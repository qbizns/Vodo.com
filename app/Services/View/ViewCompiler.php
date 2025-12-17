<?php

namespace App\Services\View;

use App\Models\ViewDefinition;
use App\Models\ViewExtension;
use App\Models\CompiledView;
use DOMDocument;
use DOMXPath;
use DOMNode;
use DOMElement;
use DOMDocumentFragment;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;

class ViewCompiler
{
    protected array $log = [];
    protected int $startTime;

    /**
     * Compile a view with all its extensions
     */
    public function compile(ViewDefinition $view, array $context = []): string
    {
        $this->log = [];
        $this->startTime = hrtime(true);

        $this->log('info', "Starting compilation of view: {$view->name}");

        // Get base content (resolving inheritance)
        $content = $view->getBaseContent();
        $this->log('info', 'Base content loaded', ['length' => strlen($content)]);

        // Get applicable extensions
        $extensions = ViewExtension::getForView($view->name, $context);
        $this->log('info', 'Found extensions', ['count' => $extensions->count()]);

        if ($extensions->isEmpty()) {
            $this->log('info', 'No extensions to apply');
            return $content;
        }

        // Apply extensions
        $compiled = $this->applyExtensions($content, $extensions);

        $compilationTime = (hrtime(true) - $this->startTime) / 1_000_000; // Convert to ms
        $this->log('info', 'Compilation complete', ['time_ms' => round($compilationTime, 2)]);

        // Cache if enabled
        if ($view->is_cacheable) {
            CompiledView::store(
                $view->name,
                $compiled,
                $view->computeContentHash(),
                $extensions->pluck('id')->toArray(),
                $this->log,
                (int)$compilationTime
            );
        }

        return $compiled;
    }

    /**
     * Apply all extensions to content
     */
    public function applyExtensions(string $content, Collection $extensions): string
    {
        // Wrap content in a root element for proper parsing
        $wrappedContent = $this->wrapContent($content);

        // Parse to DOM
        $dom = $this->parseHtml($wrappedContent);
        if (!$dom) {
            $this->log('error', 'Failed to parse content as HTML');
            return $content;
        }

        $xpath = new DOMXPath($dom);

        // Apply each extension
        foreach ($extensions as $extension) {
            $this->applyExtension($dom, $xpath, $extension);
        }

        // Extract and unwrap content
        return $this->unwrapContent($dom);
    }

    /**
     * Apply a single extension
     */
    protected function applyExtension(DOMDocument $dom, DOMXPath $xpath, ViewExtension $extension): bool
    {
        $this->log('info', "Applying extension: {$extension->name}", [
            'xpath' => $extension->xpath,
            'operation' => $extension->operation,
        ]);

        try {
            // Find target nodes
            $nodes = $xpath->query($extension->xpath);

            if ($nodes === false) {
                $this->log('error', "Invalid XPath expression", ['xpath' => $extension->xpath]);
                return false;
            }

            if ($nodes->length === 0) {
                $this->log('warning', "XPath matched no nodes", ['xpath' => $extension->xpath]);
                return false;
            }

            $this->log('info', "Found target nodes", ['count' => $nodes->length]);

            // Apply operation to each matched node
            $applied = 0;
            foreach ($nodes as $node) {
                if ($this->applyOperationToNode($dom, $node, $extension)) {
                    $applied++;
                }
            }

            $this->log('info', "Applied to nodes", ['count' => $applied]);
            return $applied > 0;

        } catch (\Exception $e) {
            $this->log('error', "Exception applying extension", [
                'extension' => $extension->name,
                'error' => $e->getMessage(),
            ]);
            return false;
        }
    }

    /**
     * Apply operation to a single node
     */
    protected function applyOperationToNode(DOMDocument $dom, DOMNode $node, ViewExtension $extension): bool
    {
        return match($extension->operation) {
            ViewExtension::OP_BEFORE => $this->insertBefore($dom, $node, $extension->content),
            ViewExtension::OP_AFTER => $this->insertAfter($dom, $node, $extension->content),
            ViewExtension::OP_REPLACE => $this->replaceNode($dom, $node, $extension->content),
            ViewExtension::OP_REMOVE => $this->removeNode($node),
            ViewExtension::OP_INSIDE_FIRST => $this->prependInside($dom, $node, $extension->content),
            ViewExtension::OP_INSIDE_LAST => $this->appendInside($dom, $node, $extension->content),
            ViewExtension::OP_WRAP => $this->wrapNode($dom, $node, $extension->content),
            ViewExtension::OP_ATTRIBUTES => $this->modifyAttributes($node, $extension->attribute_changes ?? []),
            default => false,
        };
    }

    /**
     * Insert content before a node
     */
    protected function insertBefore(DOMDocument $dom, DOMNode $node, ?string $content): bool
    {
        if (empty($content) || !$node->parentNode) {
            return false;
        }

        $fragment = $this->createFragment($dom, $content);
        if (!$fragment) {
            return false;
        }

        $node->parentNode->insertBefore($fragment, $node);
        return true;
    }

    /**
     * Insert content after a node
     */
    protected function insertAfter(DOMDocument $dom, DOMNode $node, ?string $content): bool
    {
        if (empty($content) || !$node->parentNode) {
            return false;
        }

        $fragment = $this->createFragment($dom, $content);
        if (!$fragment) {
            return false;
        }

        if ($node->nextSibling) {
            $node->parentNode->insertBefore($fragment, $node->nextSibling);
        } else {
            $node->parentNode->appendChild($fragment);
        }

        return true;
    }

    /**
     * Replace a node with new content
     */
    protected function replaceNode(DOMDocument $dom, DOMNode $node, ?string $content): bool
    {
        if (!$node->parentNode) {
            return false;
        }

        if (empty($content)) {
            // Empty content = remove
            return $this->removeNode($node);
        }

        $fragment = $this->createFragment($dom, $content);
        if (!$fragment) {
            return false;
        }

        $node->parentNode->replaceChild($fragment, $node);
        return true;
    }

    /**
     * Remove a node
     */
    protected function removeNode(DOMNode $node): bool
    {
        if (!$node->parentNode) {
            return false;
        }

        $node->parentNode->removeChild($node);
        return true;
    }

    /**
     * Prepend content inside a node (as first child)
     */
    protected function prependInside(DOMDocument $dom, DOMNode $node, ?string $content): bool
    {
        if (empty($content)) {
            return false;
        }

        $fragment = $this->createFragment($dom, $content);
        if (!$fragment) {
            return false;
        }

        if ($node->firstChild) {
            $node->insertBefore($fragment, $node->firstChild);
        } else {
            $node->appendChild($fragment);
        }

        return true;
    }

    /**
     * Append content inside a node (as last child)
     */
    protected function appendInside(DOMDocument $dom, DOMNode $node, ?string $content): bool
    {
        if (empty($content)) {
            return false;
        }

        $fragment = $this->createFragment($dom, $content);
        if (!$fragment) {
            return false;
        }

        $node->appendChild($fragment);
        return true;
    }

    /**
     * Wrap a node with new element
     */
    protected function wrapNode(DOMDocument $dom, DOMNode $node, ?string $content): bool
    {
        if (empty($content) || !$node->parentNode) {
            return false;
        }

        // Parse wrapper content
        $fragment = $this->createFragment($dom, $content);
        if (!$fragment || !$fragment->firstChild) {
            return false;
        }

        // Get the wrapper element (first child of fragment)
        $wrapper = $fragment->firstChild;

        // Clone the node we're wrapping
        $nodeClone = $node->cloneNode(true);

        // Find the deepest element in wrapper to put the content
        $deepest = $this->findDeepestElement($wrapper);
        $deepest->appendChild($nodeClone);

        // Replace original node with wrapper
        $node->parentNode->replaceChild($wrapper, $node);

        return true;
    }

    /**
     * Modify attributes of a node
     */
    protected function modifyAttributes(DOMNode $node, array $changes): bool
    {
        if (!($node instanceof DOMElement)) {
            return false;
        }

        foreach ($changes as $attr => $change) {
            if ($attr === 'class') {
                $this->modifyClasses($node, $change);
            } elseif (is_array($change)) {
                // Complex change with add/remove
                if (isset($change['remove']) && $change['remove'] === true) {
                    $node->removeAttribute($attr);
                } elseif (isset($change['value'])) {
                    $node->setAttribute($attr, $change['value']);
                }
            } elseif ($change === null || $change === false) {
                // Remove attribute
                $node->removeAttribute($attr);
            } else {
                // Set attribute value
                $node->setAttribute($attr, (string)$change);
            }
        }

        return true;
    }

    /**
     * Modify classes on an element
     */
    protected function modifyClasses(DOMElement $node, $change): void
    {
        $currentClasses = array_filter(explode(' ', $node->getAttribute('class')));

        if (is_string($change)) {
            // Simple replace
            $node->setAttribute('class', $change);
            return;
        }

        if (is_array($change)) {
            // Add classes
            if (!empty($change['add'])) {
                $toAdd = is_array($change['add']) ? $change['add'] : explode(' ', $change['add']);
                $currentClasses = array_unique(array_merge($currentClasses, array_filter($toAdd)));
            }

            // Remove classes
            if (!empty($change['remove'])) {
                $toRemove = is_array($change['remove']) ? $change['remove'] : explode(' ', $change['remove']);
                $currentClasses = array_diff($currentClasses, $toRemove);
            }

            // Toggle classes
            if (!empty($change['toggle'])) {
                $toToggle = is_array($change['toggle']) ? $change['toggle'] : explode(' ', $change['toggle']);
                foreach ($toToggle as $class) {
                    if (in_array($class, $currentClasses)) {
                        $currentClasses = array_diff($currentClasses, [$class]);
                    } else {
                        $currentClasses[] = $class;
                    }
                }
            }
        }

        $node->setAttribute('class', implode(' ', array_unique($currentClasses)));
    }

    /**
     * Find the deepest element in a node tree
     */
    protected function findDeepestElement(DOMNode $node): DOMNode
    {
        $deepest = $node;

        foreach ($node->childNodes as $child) {
            if ($child instanceof DOMElement) {
                $childDeepest = $this->findDeepestElement($child);
                $deepest = $childDeepest;
                break; // Only follow first element path
            }
        }

        return $deepest;
    }

    /**
     * Create a document fragment from HTML string
     */
    protected function createFragment(DOMDocument $dom, string $html): ?DOMDocumentFragment
    {
        $fragment = $dom->createDocumentFragment();

        // Temporarily suppress errors for HTML parsing
        $previous = libxml_use_internal_errors(true);

        try {
            // Try to append HTML directly
            if (@$fragment->appendXML($html)) {
                libxml_clear_errors();
                libxml_use_internal_errors($previous);
                return $fragment;
            }

            // Fall back to parsing as HTML
            $tempDoc = new DOMDocument();
            $tempDoc->loadHTML(
                '<html><body>' . $html . '</body></html>',
                LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD
            );

            $body = $tempDoc->getElementsByTagName('body')->item(0);
            if ($body) {
                foreach ($body->childNodes as $child) {
                    $imported = $dom->importNode($child, true);
                    $fragment->appendChild($imported);
                }
            }

            libxml_clear_errors();
            libxml_use_internal_errors($previous);
            return $fragment;

        } catch (\Exception $e) {
            libxml_clear_errors();
            libxml_use_internal_errors($previous);
            $this->log('error', 'Failed to create fragment', ['error' => $e->getMessage()]);
            return null;
        }
    }

    /**
     * Parse HTML string to DOM
     */
    protected function parseHtml(string $html): ?DOMDocument
    {
        $dom = new DOMDocument();
        $dom->preserveWhiteSpace = true;
        $dom->formatOutput = false;

        $previous = libxml_use_internal_errors(true);

        $loaded = $dom->loadHTML(
            $html,
            LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD | LIBXML_NOERROR
        );

        libxml_clear_errors();
        libxml_use_internal_errors($previous);

        return $loaded ? $dom : null;
    }

    /**
     * Wrap content in a root element for parsing
     */
    protected function wrapContent(string $content): string
    {
        // Check if content already has html/body tags
        if (stripos($content, '<html') !== false || stripos($content, '<!DOCTYPE') !== false) {
            return $content;
        }

        // Wrap in a div for parsing
        return '<div id="__view_compiler_root__">' . $content . '</div>';
    }

    /**
     * Unwrap content from root element
     */
    protected function unwrapContent(DOMDocument $dom): string
    {
        // Find our wrapper
        $xpath = new DOMXPath($dom);
        $roots = $xpath->query('//*[@id="__view_compiler_root__"]');

        if ($roots && $roots->length > 0) {
            $root = $roots->item(0);
            $html = '';
            foreach ($root->childNodes as $child) {
                $html .= $dom->saveHTML($child);
            }
            return $html;
        }

        // No wrapper found, return full HTML
        $html = $dom->saveHTML();

        // Clean up doctype and html/body tags if they were added
        $html = preg_replace('/<!DOCTYPE[^>]*>/i', '', $html);
        $html = preg_replace('/<\/?html[^>]*>/i', '', $html);
        $html = preg_replace('/<\/?body[^>]*>/i', '', $html);

        return trim($html);
    }

    /**
     * Log a message
     */
    protected function log(string $level, string $message, array $context = []): void
    {
        $entry = [
            'type' => $level,
            'message' => $message,
            'context' => $context,
            'time' => (hrtime(true) - $this->startTime) / 1_000_000, // ms since start
        ];

        $this->log[] = $entry;

        // Also log to Laravel if error
        if ($level === 'error') {
            Log::error('[ViewCompiler] ' . $message, $context);
        }
    }

    /**
     * Get compilation log
     */
    public function getLog(): array
    {
        return $this->log;
    }

    /**
     * Clear compilation log
     */
    public function clearLog(): void
    {
        $this->log = [];
    }
}
