<?php

namespace App\Http\Services;

use DOMDocument;
use DOMElement;
use DOMXPath;

class HtmlContentSanitizer
{
    private const ALLOWED_TAGS = [
        'p', 'br', 'strong', 'b', 'em', 'i', 'u', 's', 'a', 'ul', 'ol', 'li',
        'blockquote', 'h1', 'h2', 'h3', 'h4', 'h5', 'h6', 'img', 'figure',
        'figcaption', 'pre', 'code', 'span',
    ];

    private const ALLOWED_ATTRIBUTES = [
        'a' => ['href', 'title', 'target', 'rel'],
        'img' => ['src', 'alt', 'title', 'width', 'height'],
        'span' => ['dir'],
        'p' => ['dir'],
    ];

    public function clean(?string $html): ?string
    {
        if ($html === null || trim($html) === '') {
            return $html;
        }

        $document = new DOMDocument('1.0', 'UTF-8');
        libxml_use_internal_errors(true);
        $document->loadHTML('<?xml encoding="UTF-8"><div>'.$html.'</div>', LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD);
        libxml_clear_errors();

        $xpath = new DOMXPath($document);
        foreach ($xpath->query('//*') as $node) {
            if (! $node instanceof DOMElement) {
                continue;
            }

            if (! in_array($node->tagName, self::ALLOWED_TAGS, true) && $node->tagName !== 'div') {
                $node->parentNode?->removeChild($node);
                continue;
            }

            $allowed = self::ALLOWED_ATTRIBUTES[$node->tagName] ?? [];
            foreach (iterator_to_array($node->attributes ?? []) as $attribute) {
                $name = strtolower($attribute->name);
                $value = trim($attribute->value);

                if (str_starts_with($name, 'on') || ! in_array($name, $allowed, true) || preg_match('/^\s*javascript:/i', $value)) {
                    $node->removeAttribute($attribute->name);
                }
            }

            if ($node->tagName === 'a') {
                $node->setAttribute('rel', 'noopener noreferrer');
            }
        }

        $root = $document->getElementsByTagName('div')->item(0);
        $clean = '';
        foreach ($root?->childNodes ?? [] as $child) {
            $clean .= $document->saveHTML($child);
        }

        return $clean;
    }
}
