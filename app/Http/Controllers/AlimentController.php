<?php

namespace App\Http\Controllers;

use App\Models\Aliment;
use Illuminate\Http\JsonResponse;

class AlimentController extends BaseController
{
    /**
     * Get all aliments
     */
    public function index(): JsonResponse
    {
        return $this->execute(function () {
            $aliments = Aliment::all();
            return $this->successResponse($aliments, 'Aliments retrieved successfully');
        }, 'Get aliments', false);
    }

    /**
     * Get base aliments (alias for index)
     */
    public function getBaseAliments(): JsonResponse
    {
        return $this->index();
    }
}
