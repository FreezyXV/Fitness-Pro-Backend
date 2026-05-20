<?php

namespace App\Http\Controllers;

use App\Models\Aliment;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Cache;

class AlimentController extends BaseController
{
    public function index(): JsonResponse
    {
        return $this->execute(function () {
            $aliments = Cache::remember('aliments_all', 3600, fn () => Aliment::all());
            return $this->successResponse($aliments, 'Aliments retrieved successfully');
        }, 'Get aliments', false);
    }

    public function getBaseAliments(): JsonResponse
    {
        return $this->index();
    }
}
