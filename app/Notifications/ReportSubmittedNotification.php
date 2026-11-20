<?php

namespace App\Notifications;

use App\Models\Report;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class ReportSubmittedNotification extends Notification
{
    use Queueable;

    public function __construct(private readonly Report $report, private readonly string $message) {}

    /** @return array<int, string> */
    public function via(object $notifiable): array
    {
        return ['database'];
    }

    /** @return array<string, int|string> */
    public function toArray(object $notifiable): array
    {
        return [
            'report_id' => $this->report->getKey(),
            'ticket_no' => $this->report->ticket_no,
            'report_type' => $this->report->reportType->code,
            'status' => $this->report->status,
            'message' => $this->message,
        ];
    }
}
