<?php

namespace Modules\Coding\Grading;

/**
 * Membungkus kode siswa agar fungsi dipanggil dengan argumen tes dan nilai baliknya
 * dicetak sebagai JSON. Versi JavaScript-nya ada di resources/js/lib/harness.js;
 * keduanya harus menghasilkan format yang sama.
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

    /** Ambil nilai balik dari output harness. */
    public static function extract(?string $output): ?string
    {
        if ($output === null || ! str_contains($output, '__RESULT__')) {
            return null;
        }

        return trim(substr($output, strrpos($output, '__RESULT__') + 10));
    }
}
