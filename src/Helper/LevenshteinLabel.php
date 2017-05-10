<?php

declare(strict_types=1);

namespace Neo4j\Alexa\Helper;

class LevenshteinLabel
{
    public static function getNearest($input, array $labels, ?bool $adaptCost = false) : string
    {
        if (null === $input) {
            return '';
        }
        $input = strtolower(trim($input));
        $closest = '';
        $shortest = -1;

        foreach ($labels as $label) {
            $sanitized = strtolower(trim($label));
            $lev = $adaptCost ? levenshtein($input, $sanitized, 1, 1, 10) : levenshtein($input, $sanitized);

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