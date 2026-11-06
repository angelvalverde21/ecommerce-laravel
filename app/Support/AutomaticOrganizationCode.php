<?php

namespace App\Support;

use Illuminate\Http\Request;
use Illuminate\Support\Str;

class AutomaticOrganizationCode
{
    public static function fillMissing(Request $request, int $maxLength = 50): void
    {
        $name = $request->input('name');

        if ($request->filled('code') || !is_string($name)) {
            return;
        }

        $code = Str::slug(trim($name), '_');
        $code = rtrim(substr($code, 0, $maxLength), '_');

        $request->merge(['code' => $code]);
    }
}
