<?php

declare(strict_types=1);

namespace Neo4j\Alexa\Helper;

class LevenshteinLabel
{
    public static function getNearest($input, array $labels) : string
    {
        $input = strtolower(trim($input));
        $closest = '';
        $shortest = -1;

        foreach ($labels as $label) {
            $sanitized = strtolower(trim($label));
            $lev = levenshtein($input, $sanitized);

            if (0 === $lev) {
                return $label;
            }

            if ($lev <= $shortest || $shortest < 0) {
                $closest = $label;
                $shortest = $lev;
            }
        }

        return $closest;
    }
}