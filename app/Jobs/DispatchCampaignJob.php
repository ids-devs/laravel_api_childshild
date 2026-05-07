<?php

namespace App\Jobs;

use App\Models\Campaign;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

/**
 * Dispatches an SMS/WhatsApp campaign to all matching subscribers.
 */
class DispatchCampaignJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $timeout = 1800; // 30 min
    public int $tries   = 1;

    public function __construct(private Campaign $campaign) {}

    public function handle(): void
    {
        $this->campaign->update(['status' => 'sending']);

        $users = User::activeSubscribers()
            ->when($this->campaign->target_provinces, fn($q) =>
                $q->whereHas('location.province', fn($pq) =>
                    $pq->whereIn('name', $this->campaign->target_provinces)))
            ->when($this->campaign->target_districts, fn($q) =>
                $q->whereHas('location.district', fn($dq) =>
                    $dq->whereIn('name', $this->campaign->target_districts)))
            ->get();

        $sent = 0;
        foreach ($users as $user) {
            $channel = $this->campaign->channel === 'both'
                ? ($user->channel === 'whatsapp' ? 'whatsapp' : 'sms')
                : $this->campaign->channel;

            // Create a lightweight delivery entry
            $delivery = \App\Models\AlertDelivery::create([
                'alert_id' => null, // campaigns don't use alert model
                'user_id'  => $user->id,
                'channel'  => $channel,
                'status'   => 'queued',
            ]);

            if ($channel === 'sms') {
                SendSmsAlertJob::dispatch($delivery, $user, $this->campaign->message)->onQueue('sms-alerts');
            } else {
                SendWhatsappAlertJob::dispatch($delivery, $user, $this->campaign->message)->onQueue('whatsapp-alerts');
            }
            $sent++;
        }

        $this->campaign->update([
            'status'           => 'sent',
            'recipients_total' => $sent,
            'sent_at'          => now(),
        ]);

        Log::info("[DispatchCampaignJob] Campaign #{$this->campaign->id} dispatched to {$sent} recipients");
    }

    public function failed(\Throwable $exception): void
    {
        $this->campaign->update(['status' => 'cancelled']);
        Log::error("[DispatchCampaignJob] Campaign #{$this->campaign->id} failed: " . $exception->getMessage());
    }
}
