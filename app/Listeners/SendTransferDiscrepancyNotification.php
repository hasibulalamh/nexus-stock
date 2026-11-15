<?php

namespace App\Listeners;

use App\Events\TransferDiscrepancyOpened;
use App\Notifications\StockWorkflowNotification;
use App\Support\StockRecipients;
use Illuminate\Support\Facades\Log;
use Throwable;

class SendTransferDiscrepancyNotification
{
    public function handle(TransferDiscrepancyOpened $event): void
    {
        try {
            $discrepancy = $event->discrepancy->loadMissing(['item.product', 'item.transfer']);
            $transfer = $discrepancy->item?->transfer;
            if (! $transfer) {
                return;
            }

            $message = sprintf(
                '%s discrepancy of %s on %s (%s). Resolve it before closing the transfer.',
                ucfirst($discrepancy->type),
                (string) $discrepancy->qty,
                $discrepancy->item->product?->name ?? 'a product',
                $transfer->transfer_no,
            );

            foreach (StockRecipients::forLocation('transfers.create', $transfer->from_type, (int) $transfer->from_id) as $user) {
                if ((int) $user->id === (int) $event->actorId) {
                    continue;
                }
                try {
                    $user->notify(new StockWorkflowNotification($message, route('admin.transfers.show', $transfer), [
                        'transfer_id' => $transfer->id,
                        'discrepancy_id' => $discrepancy->id,
                        'type' => $discrepancy->type,
                    ]));
                } catch (Throwable $exception) {
                    Log::error('Unable to send discrepancy notification.', [
                        'discrepancy_id' => $discrepancy->id, 'user_id' => $user->id, 'exception' => $exception,
                    ]);
                }
            }
        } catch (Throwable $exception) {
            Log::error('Unable to resolve discrepancy notification recipients.', [
                'discrepancy_id' => $event->discrepancy->id, 'exception' => $exception,
            ]);
        }
    }
}
