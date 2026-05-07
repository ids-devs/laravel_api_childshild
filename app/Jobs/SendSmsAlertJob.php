<?php

namespace App\Jobs;

use App\Models\AlertDelivery;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Sends a single SMS alert to a family via Africa's Talking API.
 */
class SendSmsAlertJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $timeout = 30;
    public int $tries   = 3;
    public array $backoff = [10, 60, 300]; // seconds between retries

    public function __construct(
        private AlertDelivery $delivery,
        private User          $user,
        private string        $message,
    ) {}

    public function handle(): void
    {
        try {
            $phone = $this->user->phone_number; // decrypted via accessor

            $response = Http::asForm()->post(
                'https://api.africastalking.com/version1/messaging',
                [
                    'username' => config('services.africastalking.username'),
                    'to'       => $phone,
                    'message'  => mb_substr($this->message, 0, 160),
                    'from'     => config('services.africastalking.sender_id'),
                ]
            )->withHeaders([
                'apiKey' => config('services.africastalking.api_key'),
                'Accept' => 'application/json',
            ]);

            if ($response->successful()) {
                $msgId = $response->json('SMSMessageData.Recipients.0.messageId');
                $this->delivery->update([
                    'status'              => 'sent',
                    'provider_message_id' => $msgId,
                    'sent_at'             => now(),
                ]);
            } else {
                throw new \Exception('AT API error: ' . $response->body());
            }
        } catch (\Throwable $e) {
            Log::error("[SendSmsAlertJob] delivery #{$this->delivery->id}: " . $e->getMessage());

            if ($this->attempts() >= $this->tries) {
                $this->delivery->update([
                    'status'         => 'failed',
                    'failure_reason' => $e->getMessage(),
                ]);
            }
            throw $e;
        }
    }

    public function failed(\Throwable $exception): void
    {
        $this->delivery->update([
            'status'         => 'failed',
            'failure_reason' => $exception->getMessage(),
        ]);
    }
}
