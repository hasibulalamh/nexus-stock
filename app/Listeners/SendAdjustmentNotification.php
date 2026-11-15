<?php

namespace App\Listeners;

use App\Events\AdjustmentChanged;
use App\Notifications\StockWorkflowNotification;
use App\Support\StockRecipients;
use Illuminate\Support\Facades\Log;
use Throwable;

class SendAdjustmentNotification
{
    public function handle(AdjustmentChanged $event): void
    {
        try {
            $adjustment = $event->adjustment->loadMissing(['requester', 'lines']);
            $message = match ($event->action) {
                'submitted' => sprintf(
                    'Stock adjustment %s (%s) at %s needs approval.',
                    $adjustment->adj_no, str_replace('_', ' ', $adjustment->reason), $adjustment->locationName(),
                ),
                'approved' => sprintf('Stock adjustment %s was approved and posted.', $adjustment->adj_no),
                'rejected' => sprintf('Stock adjustment %s was rejected: %s', $adjustment->adj_no, $adjustment->rejection_reason),
                'cancelled' => sprintf('Stock adjustment %s was cancelled.', $adjustment->adj_no),
                default => null,
            };
            if ($message === null) {
                return;
            }

            $recipients = in_array($event->action, ['submitted'], true)
                ? StockRecipients::forLocation('adjustments.approve', $adjustment->location_type, (int) $adjustment->location_id)
                : collect([$adjustment->requester])->filter();

            foreach ($recipients->unique('id') as $user) {
                if ((int) $user->id === (int) $event->actorId) {
                    continue;
                }
                try {
                    $user->notify(new StockWorkflowNotification($message, route('admin.adjustments.show', $adjustment), [
                        'adjustment_id' => $adjustment->id,
                        'action' => $event->action,
                    ]));
                } catch (Throwable $exception) {
                    Log::error('Unable to send adjustment notification.', [
                        'adjustment_id' => $adjustment->id, 'user_id' => $user->id, 'exception' => $exception,
                    ]);
                }
            }
        } catch (Throwable $exception) {
            Log::error('Unable to resolve adjustment notification recipients.', [
                'adjustment_id' => $event->adjustment->id, 'exception' => $exception,
            ]);
        }
    }
}
