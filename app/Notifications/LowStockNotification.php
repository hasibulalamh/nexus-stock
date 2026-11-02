<?php

namespace App\Notifications;

use App\Models\StockAlert;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class LowStockNotification extends Notification
{
    use Queueable;

    public function __construct(public readonly StockAlert $alert)
    {
    }

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toArray(object $notifiable): array
    {
        $this->alert->loadMissing(['product', 'branch']);

        return [
            'alert_id' => $this->alert->id,
            'product_id' => $this->alert->product_id,
            'product_name' => $this->alert->product?->name ?? 'Unknown product',
            'branch_id' => $this->alert->branch_id,
            'branch_name' => $this->alert->branch?->name ?? 'Unknown branch',
            'warehouse_id' => $this->alert->warehouse_id,
            'quantity' => (string) $this->alert->quantity_at_alert,
            'minimum' => (string) $this->alert->minimum_at_alert,
            'message' => sprintf(
                'Low stock: %s at %s (%s remaining, minimum %s).',
                $this->alert->product?->name ?? 'Product',
                $this->alert->branch?->name ?? 'branch',
                $this->alert->quantity_at_alert,
                $this->alert->minimum_at_alert,
            ),
            'url' => route('admin.stock.alerts', ['alert_id' => $this->alert->id]),
        ];
    }
}
