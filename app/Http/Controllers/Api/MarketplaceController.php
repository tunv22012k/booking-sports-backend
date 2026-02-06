<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\MarketplaceService;
use Illuminate\Http\Request;

class MarketplaceController extends Controller
{
    protected $marketplaceService;

    public function __construct(MarketplaceService $marketplaceService)
    {
        $this->marketplaceService = $marketplaceService;
    }

    public function index()
    {
        $listings = $this->marketplaceService->getListings();
        return $this->successResponse($listings);
    }

    public function purchase(Request $request, $id)
    {
        try {
            $booking = $this->marketplaceService->processPurchase($id, $request->user());
            return $this->successResponse(['booking' => $booking], 'Transfer successful');
        } catch (\Exception $e) {
            return $this->errorResponse($e->getMessage(), $e->getCode() ?: 400);
        }
    }
}
