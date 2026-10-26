<?php

declare(strict_types=1);

/**
 * Derafu: Routing - Elegant PHP Router with Plugin Architecture.
 *
 * Copyright (c) 2025 Esteban De La Fuente Rubio / Derafu <https://www.derafu.dev>
 * Licensed under the MIT License.
 * See LICENSE file for more details.
 */

namespace Derafu\Routing;

/**
 * Splits route patterns without confusing regex quantifiers with placeholders.
 */
final class RoutePatternParser
{
    /**
     * @return list<array{literal: string}|array{name: string, requirement: string, optional: bool}>|null
     * Returns null when PCRE cannot process the pattern.
     */
    public static function parse(string $pattern): ?array
    {
        // Recursion balances braces inside requirements. Escapes and character
        // classes consume their own braces before the placeholder closes.
        $expression = <<<'REGEX'
~(\{(?:\\.|\[(?:\\.|[^\]\\])*\]|[^{}\\\[]++|(?1))*\})~
REGEX;
        $segments = preg_split($expression, $pattern, -1, PREG_SPLIT_DELIM_CAPTURE);
        if ($segments === false) {
            return null;
        }

        $parts = [];
        foreach ($segments as $index => $segment) {
            if ($segment === '') {
                continue;
            }

            if ($index % 2 === 0) {
                $parts[] = ['literal' => $segment];
                continue;
            }

            $parameter = explode(':', substr($segment, 1, -1), 2);
            $optional = str_ends_with($parameter[0], '?');
            $parts[] = [
                'name' => $optional ? substr($parameter[0], 0, -1) : $parameter[0],
                'requirement' => $parameter[1] ?? '[^/]+',
                'optional' => $optional,
            ];
        }

        return $parts;
    }

    /**
     * Escapes the # delimiter without changing existing regex escapes.
     */
    public static function escapeRegexDelimiter(string $expression): string
    {
        $escaped = '';
        $length = strlen($expression);
        for ($index = 0; $index < $length; ++$index) {
            $character = $expression[$index];
            if ($character === '\\' && $index + 1 < $length) {
                $escaped .= $character . $expression[++$index];
            } else {
                $escaped .= $character === '#' ? '\\#' : $character;
            }
        }

        return $escaped;
    }
}
