<?php

namespace App\Listeners;

use App\Events\OpeningStockRequestChanged;
use App\Models\User;
use App\Notifications\OpeningStockRequestNotification;
use App\Support\StockRecipients;
use Illuminate\Support\Facades\Log;
use Throwable;

class SendOpeningStockRequestNotification
{
    public function handle(OpeningStockRequestChanged $event): void
    {
        try {
            $request = $event->request->loadMissing(['lines', 'requester', 'reviewer', 'location']);
            $users = match ($event->action) {
                'submitted' => StockRecipients::forLocation(
                    'stock.opening.approve',
                    $request->location_type,
                    (int) $request->location_id
                ),
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
        $team = collect($request->location?->users ?: [])
            ->filter(fn (User $user) => $user->hasAccessAll() || $user->can('stock.view'));

        // access_all users do not receive Gate::before and may not be attached
        // to the location, so add them explicitly.
        $accessAll = User::query()->where('status', 'active')->where('access_all', true)->get();

        return collect([$request->requester])->merge($team)->merge($accessAll);
    }
}
