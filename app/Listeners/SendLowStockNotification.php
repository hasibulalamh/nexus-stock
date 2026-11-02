<?php

namespace App\Listeners;

use App\Events\LowStockAlertOpened;
use App\Models\User;
use App\Notifications\LowStockNotification;
use Illuminate\Support\Facades\Log;
use Throwable;

class SendLowStockNotification
{
    public function handle(LowStockAlertOpened $event): void
    {
        $alert = $event->alert;

        try {
            $users = User::query()
                ->where('status', 'active')
                ->when(
                    $alert->warehouse_id,
                    fn ($query) => $query->whereHas(
                        'warehouses',
                        fn ($warehouses) => $warehouses->whereKey($alert->warehouse_id)
                    ),
                    fn ($query) => $query,
                )
                ->get()
                ->filter(function (User $user) use ($alert): bool {
                    $permission = $alert->warehouse_id
                        ? 'stock.alerts.receive'
                        : 'stock.alerts.view_all';

                    return $user->hasPermissionTo($permission);
                })
                ->unique('id');

            foreach ($users as $user) {
                try {
                    $alreadyNotified = $user->notifications()
                        ->where('type', LowStockNotification::class)
                        ->get()
                        ->contains(fn ($notification): bool => (int) ($notification->data['alert_id'] ?? 0) === (int) $alert->id);
                    if ($alreadyNotified) {
                        continue;
                    }

                    $user->notify(new LowStockNotification($alert));
                } catch (Throwable $exception) {
                    Log::error('Unable to send low-stock notification.', [
                        'alert_id' => $alert->id,
                        'user_id' => $user->id,
                        'exception' => $exception,
                    ]);
                }
            }

            $alert->forceFill(['last_notified_at' => now()])->save();
        } catch (Throwable $exception) {
            Log::error('Unable to resolve low-stock notification recipients.', [
                'alert_id' => $alert->id,
                'exception' => $exception,
            ]);
        }
    }
}
