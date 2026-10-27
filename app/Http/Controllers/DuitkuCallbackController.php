<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Services\DuitkuService;
use App\Services\TelegramNotificationService;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Log;

class DuitkuCallbackController extends Controller
{
    public function __construct(
        protected DuitkuService $duitkuService
    ) {}

    /**
     * Handle incoming callback from Duitku Payment Gateway
     */
    public function handle(Request $request): Response
    {
        Log::info('Duitku Callback Received', $request->all());

        $merchantOrderId = $request->input('merchantOrderId');
        $resultCode = $request->input('resultCode');

        if (! $merchantOrderId) {
            return response('Bad Parameter: merchantOrderId missing', 400);
        }

        if (! $this->duitkuService->validateCallbackSignature($request->all())) {
            Log::warning('Duitku Callback Invalid Signature', $request->all());

            return response('Bad Signature', 400);
        }

        try {
            if ($resultCode === '00') {
                $order = $this->duitkuService->processSuccessfulPayment($merchantOrderId, $request->all());
                app(TelegramNotificationService::class)->sendOrderPaidNotification($order);
            } else {
                Order::where('order_id', $merchantOrderId)
                    ->where('status', 'pending')
                    ->update([
                        'status' => 'failed',
                        'payload' => array_merge($request->all(), ['failed_reason' => 'resultCode: '.$resultCode]),
                    ]);
            }

            return response('OK', 200);
        } catch (Exception $e) {
            Log::error('Duitku Callback Processing Error: '.$e->getMessage(), [
                'exception' => $e,
                'request' => $request->all(),
            ]);

            return response('Internal Error', 500);
        }
    }
}
