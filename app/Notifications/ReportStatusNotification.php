<?php

namespace App\Notifications;

use App\Models\Report;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class ReportStatusNotification extends Notification
{
    use Queueable;

    public function __construct(private readonly Report $report) {}

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toArray(object $notifiable): array
    {
        return [
            'report_id' => $this->report->getKey(),
            'ticket_no' => $this->report->ticket_no,
            'status' => $this->report->status,
            'message' => 'Status laporan diperbarui.',
        ];
    }
}
