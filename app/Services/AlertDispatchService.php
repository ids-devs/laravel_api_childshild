<?php

namespace App\Services;

use App\Models\Alert;
use App\Models\AlertDelivery;
use App\Models\User;
use App\Jobs\SendSmsAlertJob;
use App\Jobs\SendWhatsappAlertJob;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Manages alert dispatch to families via SMS and WhatsApp.
 * Sends jobs to queue for async processing.
 */
class AlertDispatchService
{
    /**
     * Dispatch an alert to all eligible recipients in its zone.
     */
    public function dispatch(Alert $alert): void
    {
        // Get subscribers in the alert's location
        $users = User::activeSubscribers()
            ->byLocation($alert->location_id)
            ->when($alert->channel !== 'both', fn($q) => $q->byChannel($alert->channel))
            ->with('household')
            ->get();

        if ($users->isEmpty()) {
            $alert->update(['status' => 'sent', 'sent_at' => now()]);
            return;
        }

        DB::transaction(function () use ($alert, $users) {
            $alert->update([
                'status'           => 'processing',
                'recipients_total' => $users->count(),
            ]);

            // Create delivery records and queue jobs
            foreach ($users as $user) {
                $channels = $this->getChannelsForUser($user, $alert);

                foreach ($channels as $channel) {
                    $delivery = AlertDelivery::create([
                        'alert_id' => $alert->id,
                        'user_id'  => $user->id,
                        'channel'  => $channel,
                        'status'   => 'queued',
                    ]);

                    $message = $alert->getMessageForLanguage($user->language);

                    if ($channel === 'sms') {
                        SendSmsAlertJob::dispatch($delivery, $user, $message)
                            ->onQueue('sms-alerts');
                    } else {
                        SendWhatsappAlertJob::dispatch($delivery, $user, $message)
                            ->onQueue('whatsapp-alerts');
                    }
                }
            }
        });

        Log::info("Alert #{$alert->id} dispatched to {$users->count()} recipients");
    }

    private function getChannelsForUser(User $user, Alert $alert): array
    {
        if ($alert->channel === 'both') {
            return [$user->channel === 'whatsapp' ? 'whatsapp' : 'sms'];
        }
        return [$alert->channel];
    }

    /**
     * Handle delivery status callback from Africa's Talking.
     */
    public function handleSmsCallback(string $messageId, string $status): void
    {
        $delivery = AlertDelivery::where('provider_message_id', $messageId)->first();
        if (!$delivery) return;

        $delivery->update([
            'status'       => $this->mapAtStatus($status),
            'delivered_at' => in_array($status, ['Success', 'Delivered']) ? now() : null,
        ]);

        $this->updateAlertStats($delivery->alert_id);
    }

    private function mapAtStatus(string $atStatus): string
    {
        return match($atStatus) {
            'Success', 'Delivered' => 'delivered',
            'Sent'                 => 'sent',
            default                => 'failed',
        };
    }

    private function updateAlertStats(int $alertId): void
    {
        $stats = AlertDelivery::where('alert_id', $alertId)
            ->selectRaw("
                COUNT(*) FILTER (WHERE status IN ('sent','delivered')) as sent_count,
                COUNT(*) FILTER (WHERE status = 'failed') as failed_count,
                COUNT(*) as total
            ")
            ->first();

        $alert = Alert::find($alertId);
        $alert?->update([
            'recipients_sent'   => $stats->sent_count,
            'recipients_failed' => $stats->failed_count,
            'status'            => $stats->sent_count + $stats->failed_count >= $stats->total
                ? 'sent' : 'processing',
        ]);
    }
}
