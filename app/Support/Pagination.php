<?php

namespace App\Support;

use Illuminate\Http\Request;

class Pagination
{
    public static function perPage(Request $request): int
    {
        $default = (int) config('shop.pagination.default_per_page', 10);
        $max = (int) config('shop.pagination.max_per_page', 100);

        return max(1, min((int) $request->integer('rows', $default), $max));
    }
}
