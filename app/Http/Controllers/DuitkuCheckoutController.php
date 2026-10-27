<?php

namespace App\Http\Controllers;

use App\Services\DuitkuService;
use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class DuitkuCheckoutController extends Controller
{
    public function __construct(
        protected DuitkuService $duitkuService
    ) {}

    /**
     * Initiate Duitku checkout for tree quota
     */
    public function checkout(Request $request): JsonResponse|RedirectResponse
    {
        $user = $request->user();

        try {
            $order = $this->duitkuService->createInvoice($user, 1, 10000);

            if ($request->wantsJson()) {
                return response()->json([
                    'success' => true,
                    'order_id' => $order->order_id,
                    'payment_url' => $order->payment_url,
                ]);
            }

            if ($order->payment_url) {
                return redirect()->away($order->payment_url);
            }

            return back()->with('error', 'Gagal mendapatkan URL pembayaran dari Duitku.');
        } catch (Exception $e) {
            if ($request->wantsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => $e->getMessage(),
                ], 422);
            }

            return back()->with('error', $e->getMessage());
        }
    }
}
