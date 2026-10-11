<?php

namespace Modules\Coding\Grading;

/**
 * Wraps student code so the function is called with the test arguments and its return
 * value is printed as JSON. The JavaScript version lives in resources/js/lib/harness.js;
 * both must produce the same format.
 */
class Harness
{
    public static function python(string $code, string $function, string $argsJson): string
    {
        $args = var_export($argsJson, true);

        return $code."\n\nimport json as __json\nprint('\\n__RESULT__' + __json.dumps({$function}(*__json.loads({$args}))))\n";
    }

    public static function javascript(string $code, string $function, string $argsJson): string
    {
        return $code."\n;console.log('\\n__RESULT__' + JSON.stringify({$function}(...".$argsJson.')));'."\n";
    }

    /** Extract the return value from the harness output. */
    public static function extract(?string $output): ?string
    {
        if ($output === null || ! str_contains($output, '__RESULT__')) {
            return null;
        }

        return trim(substr($output, strrpos($output, '__RESULT__') + 10));
    }
}
