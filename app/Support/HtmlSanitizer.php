<?php

namespace App\Support;

use DOMComment;
use DOMDocument;
use DOMElement;
use DOMNode;

/**
 * Allow-list HTML cleaner for admin-authored email bodies (rich-text editor
 * output). Strips anything not on the allow-list rather than trying to
 * blacklist dangerous constructs, so it fails closed.
 */
class HtmlSanitizer
{
    private const ALLOWED_TAGS = [
        'p', 'br', 'b', 'strong', 'i', 'em', 'u', 'a', 'img',
        'ul', 'ol', 'li', 'h1', 'h2', 'h3', 'h4', 'blockquote',
        'span', 'div', 'pre', 'code', 'hr',
    ];

    private const ALLOWED_ATTRIBUTES = [
        'a'   => ['href', 'title'],
        'img' => ['src', 'alt', 'width', 'height'],
    ];

    private const ALLOWED_URL_SCHEMES = ['http', 'https', 'mailto'];

    private const STRIPPED_ENTIRELY = ['script', 'style', 'iframe', 'object', 'embed', 'form', 'link', 'meta'];

    public static function clean(string $html): string
    {
        if (trim($html) === '') {
            return '';
        }

        $document = new DOMDocument();

        $previous = libxml_use_internal_errors(true);
        $document->loadHTML('<?xml encoding="UTF-8"><div>'.$html.'</div>', LIBXML_NOERROR | LIBXML_NOWARNING);
        libxml_clear_errors();
        libxml_use_internal_errors($previous);

        $root = $document->getElementsByTagName('div')->item(0);

        if (! $root) {
            return '';
        }

        self::sanitizeChildren($document, $root);

        $output = '';
        foreach (iterator_to_array($root->childNodes) as $child) {
            $output .= $document->saveHTML($child);
        }

        return $output;
    }

    private static function sanitizeChildren(DOMDocument $document, DOMNode $node): void
    {
        foreach (iterator_to_array($node->childNodes) as $child) {
            if ($child instanceof DOMComment) {
                $node->removeChild($child);

                continue;
            }

            if (! $child instanceof DOMElement) {
                continue; // text nodes are safe as-is
            }

            $tag = strtolower($child->tagName);

            if (in_array($tag, self::STRIPPED_ENTIRELY, true)) {
                $node->removeChild($child);

                continue;
            }

            if (! in_array($tag, self::ALLOWED_TAGS, true)) {
                self::sanitizeChildren($document, $child);

                while ($child->firstChild) {
                    $node->insertBefore($child->firstChild, $child);
                }
                $node->removeChild($child);

                continue;
            }

            self::sanitizeAttributes($child, $tag);
            self::sanitizeChildren($document, $child);
        }
    }

    private static function sanitizeAttributes(DOMElement $element, string $tag): void
    {
        $allowed = self::ALLOWED_ATTRIBUTES[$tag] ?? [];

        foreach (iterator_to_array($element->attributes ?? []) as $attribute) {
            $name = strtolower($attribute->name);

            if (! in_array($name, $allowed, true)) {
                $element->removeAttribute($attribute->name);

                continue;
            }

            if (in_array($name, ['href', 'src'], true) && ! self::hasAllowedScheme($attribute->value)) {
                $element->removeAttribute($attribute->name);
            }
        }

        if ($tag === 'a' && $element->hasAttribute('href')) {
            $element->setAttribute('target', '_blank');
            $element->setAttribute('rel', 'noopener noreferrer');
        }
    }

    private static function hasAllowedScheme(string $url): bool
    {
        $url = trim($url);

        if ($url === '' || str_starts_with($url, '/') || str_starts_with($url, '#')) {
            return true;
        }

        $scheme = parse_url($url, PHP_URL_SCHEME);

        return $scheme !== null && in_array(strtolower($scheme), self::ALLOWED_URL_SCHEMES, true);
    }
}
