<?php

namespace App\Services;

use App\Models\RiskScore;
use App\Models\Location;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Generates localised alert messages using templates or OpenAI.
 */
class MessageGeneratorService
{
    public function generateForAlert(RiskScore $riskScore, Location $location): array
    {
        try {
            if (config('services.openai.key')) {
                return $this->generateWithAI($riskScore, $location);
            }
        } catch (\Throwable $e) {
            Log::warning("AI message generation failed: " . $e->getMessage());
        }

        return $this->generateFromTemplate($riskScore);
    }

    private function generateWithAI(RiskScore $riskScore, Location $location): array
    {
        $prompt = $this->buildPrompt($riskScore, $location);

        $response = Http::withHeaders([
            'Authorization' => 'Bearer ' . config('services.openai.key'),
            'Content-Type'  => 'application/json',
        ])->timeout(15)->post('https://api.openai.com/v1/chat/completions', [
            'model'       => 'gpt-4o-mini',
            'max_tokens'  => 400,
            'messages'    => [
                ['role' => 'system', 'content' => 'Você é um assistente de saúde pública em Moçambique. Gere alertas SMS curtos (máx 160 caracteres), claros e accionáveis para famílias com crianças. Responda em JSON com campos: pt, changane. Não faça diagnósticos médicos.'],
                ['role' => 'user',   'content' => $prompt],
            ],
        ]);

        $content = $response->json('choices.0.message.content', '');

        // Parse JSON response
        preg_match('/\{.*\}/s', $content, $matches);
        $messages = json_decode($matches[0] ?? '{}', true);

        return [
            'message_pt'       => $messages['pt'] ?? $this->generateFromTemplate($riskScore)['message_pt'],
            'message_changane' => $messages['changane'] ?? null,
        ];
    }

    private function buildPrompt(RiskScore $riskScore, Location $location): string
    {
        return "Risco: " . ($riskScore->riskType?->code ?? 'desconhecido') . " | Nível: {$riskScore->risk_level} | Score: {$riskScore->score} | "
             . "Zona: {$location->district?->name}, {$location->province?->name}. "
             . "Gera alerta SMS para famílias com crianças. Máx 160 chars. Com instrução SAIR para cancelar.";
    }

    private function generateFromTemplate(RiskScore $riskScore): array
    {
        $templates = [
            'heat' => [
                'critical' => 'ChildShield: ALERTA CRÍTICO Calor extremo hoje. Mantenha crianças em local fresco e hidratadas. Procure unidade sanitária se necessário. Para cancelar: SAIR',
                'high'     => 'ChildShield: Calor intenso previsto. Evite sol 11h-15h. Ofereça mais água às crianças. Para cancelar: SAIR',
                'medium'   => 'ChildShield: Temperatura elevada. Mantenha crianças hidratadas e com chapéu ao sair. Para cancelar: SAIR',
            ],
            'malaria' => [
                'critical' => 'ChildShield: ALERTA Risco alto malária. Use rede mosquiteira sempre. Elimine água parada. Febre? Vá ao posto. Para cancelar: SAIR',
                'high'     => 'ChildShield: Chuvas aumentam malária. Use rede mosquiteira tratada. Elimine recipientes com água. Para cancelar: SAIR',
                'medium'   => 'ChildShield: Use rede mosquiteira e elimine água estagnada. Dica de prevenção. Para cancelar: SAIR',
            ],
            'diarrhea' => [
                'critical' => 'ChildShield: ALERTA Água pode estar contaminada. Use água fervida. Lave mãos frequentemente. Diarreia? Vá ao posto. Para cancelar: SAIR',
                'high'     => 'ChildShield: Risco de contaminação da água. Ferva ou trate a água. Lave mãos antes de cozinhar. Para cancelar: SAIR',
                'medium'   => 'ChildShield: Lave sempre as mãos. Use água limpa para beber. Dica de higiene. Para cancelar: SAIR',
            ],
            'respiratory' => [
                'critical' => 'ChildShield: ALERTA Ar muito poluído. Evite crianças ao ar livre. Use máscara se necessário sair. Para cancelar: SAIR',
                'high'     => 'ChildShield: Qualidade do ar reduzida. Mantenha janelas fechadas. Limite tempo ao ar livre. Para cancelar: SAIR',
                'medium'   => 'ChildShield: Limite tempo ao ar livre hoje. Qualidade do ar abaixo do normal. Para cancelar: SAIR',
            ],
        ];

        return [
            'message_pt' => $templates[$riskScore->riskType?->code][$riskScore->risk_level]
                ?? "ChildShield: Alerta climático para a sua zona. Fique atento. Para cancelar: SAIR",
        ];
    }
}
