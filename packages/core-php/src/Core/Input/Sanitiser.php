<?php

declare(strict_types=1);

namespace Core\Input;

/**
 * Input sanitiser - makes data safe, not valid.
 *
 * One job: strip dangerous control characters.
 * One C call: filter_var_array.
 *
 * Laravel validates. We sanitise.
 */
class Sanitiser
{
    /**
     * Strip dangerous control characters from all values.
     *
     * Only strips ASCII 0-31 (null bytes, control characters).
     * Preserves Unicode (UTF-8 high bytes) for international input.
     */
    public function filter(array $input): array
    {
        if (empty($input)) {
            return [];
        }

        // Strip only control characters, preserve Unicode
        $filter = [
            'filter' => FILTER_UNSAFE_RAW,
            'flags' => FILTER_FLAG_STRIP_LOW,
        ];

        // One C call - process entire array
        $definition = array_fill_keys(array_keys($input), $filter);

        return filter_var_array($input, $definition) ?: [];
    }
}
