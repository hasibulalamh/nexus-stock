<?php

namespace App\Listeners;

use App\Events\StockArrivedAtLocation;
use App\Models\TransferItem;
use App\Notifications\StockWorkflowNotification;
use App\Support\StockRecipients;
use Illuminate\Support\Facades\Log;
use Throwable;

class SendBackorderNotification
{
    public function handle(StockArrivedAtLocation $event): void
    {
        if ($event->locationType !== 'warehouse' || $event->locationId < 1) {
            return;
        }

        try {
            $items = TransferItem::query()
                ->with(['transfer', 'product'])
                ->where('product_id', $event->productId)
                ->whereColumn('sent_qty', '<', 'requested_qty')
                ->whereHas('transfer', fn ($query) => $query
                    ->whereIn('status', ['draft', 'sent'])
                    ->where('type', 'transfer')
                    ->where('from_type', 'warehouse')
                    ->where('from_id', $event->locationId))
                ->limit(10)
                ->get();

            if ($items->isEmpty()) {
                return;
            }

            $message = sprintf(
                'Stock arrived at %s and can fulfil %d backorder line(s) (%s).',
                $items->first()->transfer->fromLocationName(),
                $items->count(),
                $items->take(3)->map(fn (TransferItem $item) => $item->product?->name.' x'.$item->backorderQty())->implode(', '),
            );

            foreach (StockRecipients::forLocation('transfers.create', 'warehouse', $event->locationId) as $user) {
                if ((int) $user->id === (int) $event->actorId) {
                    continue;
                }
                try {
                    $user->notify(new StockWorkflowNotification($message, route('admin.transfers.backorders'), [
                        'warehouse_id' => $event->locationId,
                        'product_id' => $event->productId,
                        'kind' => 'backorder',
                    ]));
                } catch (Throwable $exception) {
                    Log::error('Unable to send backorder notification.', [
                        'warehouse_id' => $event->locationId, 'user_id' => $user->id, 'exception' => $exception,
                    ]);
                }
            }
        } catch (Throwable $exception) {
            Log::error('Unable to resolve backorder notification recipients.', [
                'warehouse_id' => $event->locationId, 'exception' => $exception,
            ]);
        }
    }
}
