<?php

namespace App\Notifications;

use App\Models\OpeningStockRequest;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class OpeningStockRequestNotification extends Notification
{
    use Queueable;

    public function __construct(public readonly OpeningStockRequest $request, public readonly string $action)
    {
    }

    public function via(object $notifiable): array
    {
        $channels = ['database'];
        if (config('stock.notifications.mail', false) && $notifiable->email) {
            $channels[] = 'mail';
        }

        return $channels;
    }

    public function toArray(object $notifiable): array
    {
        $location = $this->request->location?->name ?? ucfirst($this->request->location_type).' #'.$this->request->location_id;
        $message = match ($this->action) {
            'submitted' => "Opening stock request {$this->request->request_no} needs approval at {$location}.",
            'approved' => sprintf(
                '[Nexus Stock] Opening stock approved: %s. %d product(s) added at %s. Approved by %s on %s.',
                $this->request->request_no,
                $this->request->lines->count(),
                $location,
                $this->request->reviewer?->full_name ?? 'Unknown user',
                $this->request->reviewed_at?->toDateString() ?? now()->toDateString(),
            ),
            'rejected' => "Opening stock request {$this->request->request_no} was rejected: {$this->request->rejection_reason}.",
            default => "Opening stock request {$this->request->request_no} was {$this->action}.",
        };

        return [
            'request_id' => $this->request->id,
            'request_no' => $this->request->request_no,
            'action' => $this->action,
            'message' => $message,
            'url' => route('admin.stock.requests.show', $this->request),
        ];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)->subject('Opening stock request '.$this->request->request_no)
            ->line($this->toArray($notifiable)['message'])
            ->action('View request', route('admin.stock.requests.show', $this->request));
    }
}
