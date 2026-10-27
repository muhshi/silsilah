<?php

namespace App\Http\Controllers;

use App\Models\PricingPackage;
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
        $request->validate([
            'package_id' => ['nullable', 'exists:pricing_packages,id'],
        ]);

        $user = $request->user();

        $package = null;
        if ($request->filled('package_id')) {
            $package = PricingPackage::find($request->input('package_id'));
        } else {
            $package = PricingPackage::where('slug', 'satuan')->first() ?? PricingPackage::active()->first();
        }

        $quotaCount = 1;
        $amount = 10000;
        $packageId = null;

        if ($package) {
            if (! $package->is_active) {
                if ($request->wantsJson()) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Paket ini sedang tidak aktif.',
                    ], 422);
                }

                return back()->with('error', 'Paket ini sedang tidak aktif.');
            }

            if (! $package->isAvailableNow()) {
                $msg = 'Paket ini berada di luar periode promo yang ditentukan.';
                if ($request->wantsJson()) {
                    return response()->json([
                        'success' => false,
                        'message' => $msg,
                    ], 422);
                }

                return back()->with('error', $msg);
            }

            if (! $package->canUserPurchase($user)) {
                $msg = 'Anda sudah pernah menggunakan promo ini sebelumnya.';
                if ($request->wantsJson()) {
                    return response()->json([
                        'success' => false,
                        'message' => $msg,
                    ], 422);
                }

                return back()->with('error', $msg);
            }

            $quotaCount = $package->tree_quota;
            $amount = $package->price;
            $packageId = $package->id;
        }

        try {
            $order = $this->duitkuService->createInvoice($user, $quotaCount, $amount, $packageId);

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
