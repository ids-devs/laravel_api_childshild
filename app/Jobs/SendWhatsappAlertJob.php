<?php

namespace App\Jobs;

use App\Models\AlertDelivery;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

/**
 * Sends a WhatsApp message via Baileys (MVP) or WhatsApp Business API (prod).
 */
class SendWhatsappAlertJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $timeout = 30;
    public int $tries   = 3;
    public array $backoff = [15, 60, 300];

    public function __construct(
        private AlertDelivery $delivery,
        private User          $user,
        private string        $message,
    ) {}

    public function handle(): void
    {
        try {
            $phone = $this->user->phone_number;

            if (config('services.whatsapp.driver') === 'waba') {
                $this->sendViaWABA($phone);
            } else {
                $this->sendViaBaileys($phone);
            }
        } catch (\Throwable $e) {
            Log::error("[SendWhatsappAlertJob] delivery #{$this->delivery->id}: " . $e->getMessage());
            if ($this->attempts() >= $this->tries) {
                $this->delivery->update(['status' => 'failed', 'failure_reason' => $e->getMessage()]);
            }
            throw $e;
        }
    }

    private function sendViaBaileys(string $phone): void
    {
        $baileysUrl = (string) config('services.whatsapp.baileys_url');
        if (
            blank($baileysUrl) ||
            (app()->environment('local') && str_contains($baileysUrl, 'localhost'))
        ) {
            $this->delivery->update([
                'status' => 'sent',
                'provider_message_id' => 'mock-wa-' . $this->delivery->id,
                'sent_at' => now(),
            ]);
            return;
        }

        // Baileys microservice running locally (Node.js)
        $response = \Illuminate\Support\Facades\Http::timeout(20)
            ->post($baileysUrl . '/send', [
                'phone'   => $phone,
                'message' => $this->message,
            ]);

        if ($response->successful()) {
            $this->delivery->update(['status' => 'sent', 'sent_at' => now()]);
        } else {
            throw new \Exception('Baileys error: ' . $response->body());
        }
    }

    private function sendViaWABA(string $phone): void
    {
        if (blank(config('services.whatsapp.waba_url')) || blank(config('services.whatsapp.waba_token'))) {
            $this->delivery->update([
                'status' => 'sent',
                'provider_message_id' => 'mock-wa-' . $this->delivery->id,
                'sent_at' => now(),
            ]);
            return;
        }

        // WhatsApp Business API via 360dialog
        $response = \Illuminate\Support\Facades\Http::withToken(config('services.whatsapp.waba_token'))
            ->timeout(20)
            ->post(config('services.whatsapp.waba_url') . '/messages', [
                'messaging_product' => 'whatsapp',
                'to'                => ltrim($phone, '+'),
                'type'              => 'text',
                'text'              => ['body' => $this->message],
            ]);

        if ($response->successful()) {
            $msgId = $response->json('messages.0.id');
            $this->delivery->update([
                'status'              => 'sent',
                'provider_message_id' => $msgId,
                'sent_at'             => now(),
            ]);
        } else {
            throw new \Exception('WABA error: ' . $response->body());
        }
    }

    public function failed(\Throwable $exception): void
    {
        $this->delivery->update(['status' => 'failed', 'failure_reason' => $exception->getMessage()]);
    }
}
