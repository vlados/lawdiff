<?php

namespace App\Http\Controllers;

use App\Models\Law;
use Illuminate\Http\JsonResponse;

class LawController extends Controller
{
    public function show(Law $law): JsonResponse
    {
        return response()->json($law);
    }
}
