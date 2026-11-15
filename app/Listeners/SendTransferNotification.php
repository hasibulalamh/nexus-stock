<?php

namespace App\Listeners;

use App\Events\TransferChanged;
use App\Models\Transfer;
use App\Models\User;
use App\Notifications\StockWorkflowNotification;
use App\Support\StockRecipients;
use Illuminate\Support\Facades\Log;
use Throwable;

class SendTransferNotification
{
    public function handle(TransferChanged $event): void
    {
        try {
            $transfer = $event->transfer;
            $recipients = match ($event->action) {
                'sent' => StockRecipients::forLocation('transfers.receive', $transfer->to_type, (int) $transfer->to_id),
                'received' => collect([$transfer->requester])->filter(),
                'cancelled' => collect([$transfer->requester])->filter(),
                default => collect(),
            };

            $message = $this->message($transfer, $event->action);
            if ($message === null) {
                return;
            }

            foreach ($recipients->unique('id') as $user) {
                if ((int) $user->id === (int) $event->actorId) {
                    continue;
                }
                try {
                    $user->notify(new StockWorkflowNotification($message, route('admin.transfers.show', $transfer), [
                        'transfer_id' => $transfer->id,
                        'transfer_no' => $transfer->transfer_no,
                        'action' => $event->action,
                    ]));
                } catch (Throwable $exception) {
                    Log::error('Unable to send transfer notification.', [
                        'transfer_id' => $transfer->id, 'user_id' => $user->id, 'exception' => $exception,
                    ]);
                }
            }
        } catch (Throwable $exception) {
            Log::error('Unable to resolve transfer notification recipients.', [
                'transfer_id' => $event->transfer->id, 'exception' => $exception,
            ]);
        }
    }

    private function message(Transfer $transfer, string $action): ?string
    {
        return match ($action) {
            'sent' => sprintf(
                '%s %s was sent from %s to %s. Confirm receipt.',
                ucfirst($transfer->type), $transfer->transfer_no,
                $transfer->fromLocationName(), $transfer->toLocationName(),
            ),
            'received' => sprintf('%s %s was received at %s.', ucfirst($transfer->type), $transfer->transfer_no, $transfer->toLocationName()),
            'cancelled' => sprintf('%s %s was cancelled.', ucfirst($transfer->type), $transfer->transfer_no),
            default => null,
        };
    }
}
