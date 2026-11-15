<?php

namespace App\Listeners;

use App\Events\OpeningStockRequestChanged;
use App\Models\User;
use App\Notifications\OpeningStockRequestNotification;
use App\Support\StockAccess;
use Illuminate\Support\Facades\Log;
use Throwable;

class SendOpeningStockRequestNotification
{
    public function handle(OpeningStockRequestChanged $event): void
    {
        try {
            $request = $event->request->loadMissing(['lines', 'requester', 'reviewer', 'location']);
            $users = match ($event->action) {
                'submitted' => User::query()->where('status', 'active')->get()
                    ->filter(fn (User $user) => $user->can('stock.opening.approve')
                        && StockAccess::canWrite($user, $request->location_type, (int) $request->location_id)),
                'approved' => $this->approvalRecipients($request),
                'rejected' => collect([$request->requester]),
                default => collect(),
            };

            foreach ($users->filter()->unique('id') as $user) {
                if ($user->id === $event->actorId) {
                    continue;
                }
                try {
                    $user->notify(new OpeningStockRequestNotification($request, $event->action));
                } catch (Throwable $exception) {
                    Log::error('Unable to send opening stock request notification.', [
                        'request_id' => $request->id, 'user_id' => $user->id, 'exception' => $exception,
                    ]);
                }
            }
        } catch (Throwable $exception) {
            Log::error('Unable to resolve opening stock request notification recipients.', [
                'request_id' => $event->request->id, 'exception' => $exception,
            ]);
        }
    }

    private function approvalRecipients($request)
    {
        $team = $request->location_type === 'branch'
            ? $request->location?->users
            : $request->location?->users;

        return collect([$request->requester])->merge(
            collect($team ?: [])->filter(fn (User $user) => $user->can('stock.view'))
        );
    }
}
