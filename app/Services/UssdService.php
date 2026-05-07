<?php

namespace App\Services;

use App\Models\User;
use App\Models\Household;
use App\Models\UssdSession;
use App\Models\Location;
use App\Models\Province;
use App\Models\District;
use App\Models\RiskScore;

/**
 * USSD flow handler for Africa's Talking integration.
 * Manages multi-step registration and alert checking via *123#
 */
class UssdService
{
    private UssdSession $session;
    private ?User $user;

    public function handle(
        string $sessionId,
        string $serviceCode,
        string $phoneNumber,
        string $text
    ): string {
        $phoneHash = User::hashPhone($phoneNumber);
        $this->user = User::where('phone_hash', $phoneHash)->with('household')->first();

        $this->session = UssdSession::firstOrCreate(
            ['session_id' => $sessionId],
            [
                'phone_hash'   => $phoneHash,
                'service_code' => $serviceCode,
                'current_step' => 'main_menu',
                'is_active'    => true,
                'session_data' => [],
            ]
        );

        $input = trim(explode('*', $text)[count(explode('*', $text)) - 1]);

        return $this->route($input);
    }

    private function route(string $input): string
    {
        $step = $this->session->current_step;

        return match(true) {
            $step === 'main_menu'                   => $this->mainMenu($input),
            str_starts_with($step, 'register_')    => $this->registerFlow($step, $input),
            $step === 'view_alert'                  => $this->viewAlert($input),
            $step === 'change_location'             => $this->changeLocationFlow($step, $input),
            $step === 'choose_language'             => $this->chooseLanguage($input),
            $step === 'cancel_confirm'              => $this->cancelSubscription($input),
            default                                 => $this->mainMenu(''),
        };
    }

    // ─── Main Menu ────────────────────────────────────────────────────────

    private function mainMenu(string $input): string
    {
        if ($this->user && $this->user->subscription_active) {
            // Registered user menu
            $menu = "CON Bem-vindo ao ChildShield\n";
            $menu .= "1. Ver alerta actual\n";
            $menu .= "2. Alterar localização\n";
            $menu .= "3. Escolher idioma\n";
            $menu .= "4. Ver unidades sanitárias\n";
            $menu .= "5. Cancelar subscrição\n";
            $menu .= "0. Sair";
            return $menu;
        }

        // New user registration
        $menu = "CON Bem-vindo ao ChildShield\n";
        $menu .= "Alertas climáticos para crianças\n\n";
        $menu .= "1. Registar família\n";
        $menu .= "2. Ver alerta da zona\n";
        $menu .= "0. Sair";
        return $menu;
    }

    // ─── Registration Flow ────────────────────────────────────────────────

    private function registerFlow(string $step, string $input): string
    {
        return match($step) {
            'register_province' => $this->regProvince($input),
            'register_district' => $this->regDistrict($input),
            'register_children' => $this->regChildren($input),
            'register_age'      => $this->regAgeGroups($input),
            'register_pregnant' => $this->regPregnant($input),
            'register_language' => $this->regLanguage($input),
            'register_consent'  => $this->regConsent($input),
            default             => $this->startRegistration(),
        };
    }

    private function startRegistration(): string
    {
        $this->session->update(['current_step' => 'register_province']);
        $provinces = Province::query()->orderBy('name')->get(['id', 'name']);
        $menu = "CON Escolha a sua província:\n";
        foreach ($provinces as $i => $prov) {
            $menu .= ($i + 1) . ". {$prov->name}\n";
        }
        return $menu;
    }

    private function regProvince(string $input): string
    {
        $provinces = Province::query()->orderBy('name')->get(['id', 'name']);
        $idx = (int)$input - 1;

        if (!isset($provinces[$idx])) {
            return "CON Opção inválida.\n" . $this->startRegistration();
        }

        $selectedProvince = $provinces[$idx];
        $this->session->setData('province_id', $selectedProvince->id);
        $this->session->update(['current_step' => 'register_district']);

        $districts = District::query()
            ->where('province_id', $selectedProvince->id)
            ->orderBy('name')
            ->get(['id', 'name']);
        $menu = "CON Escolha o seu distrito:\n";
        foreach ($districts as $i => $dist) {
            $menu .= ($i + 1) . ". {$dist->name}\n";
        }
        return $menu;
    }

    private function regDistrict(string $input): string
    {
        $provinceId = (int) $this->session->getData('province_id');
        $districts = District::query()
            ->where('province_id', $provinceId)
            ->orderBy('name')
            ->get(['id', 'name']);
        $idx = (int)$input - 1;

        if (!isset($districts[$idx])) {
            return "CON Opção inválida.\n" . $this->regProvince('1');
        }

        $selectedDistrict = $districts[$idx];
        $location = Location::query()
            ->where('province_id', $provinceId)
            ->where('district_id', $selectedDistrict->id)
            ->first();

        if (!$location) {
            $location = Location::query()->create([
                'province_id' => $provinceId,
                'district_id' => $selectedDistrict->id,
                'locality' => null,
            ]);
        }

        $this->session->setData('location_id', $location?->id);
        $this->session->setData('district_id', $selectedDistrict->id);
        $this->session->update(['current_step' => 'register_children']);

        return "CON Quantas crianças tem no agregado?\n1. Nenhuma\n2. 1-2\n3. 3-5\n4. 6 ou mais";
    }

    private function regChildren(string $input): string
    {
        $countMap = ['1' => 0, '2' => 2, '3' => 4, '4' => 6];
        $this->session->setData('num_children', $countMap[$input] ?? 0);

        if (($countMap[$input] ?? 0) === 0) {
            $this->session->update(['current_step' => 'register_pregnant']);
            return "CON Existe mulher grávida no agregado?\n1. Sim\n2. Não";
        }

        $this->session->update(['current_step' => 'register_age']);
        return "CON Faixa etária das crianças (escolha todas):\n1. 0-1 ano\n2. 1-5 anos\n3. 6-12 anos\n4. Continuar";
    }

    private function regAgeGroups(string $input): string
    {
        if ($input !== '4') {
            $ageMap = ['1' => '0-1', '2' => '1-5', '3' => '6-12'];
            $groups = $this->session->getData('age_groups', []);
            if (isset($ageMap[$input]) && !in_array($ageMap[$input], $groups)) {
                $groups[] = $ageMap[$input];
                $this->session->setData('age_groups', $groups);
            }
            return "CON Faixa etária das crianças:\n1. 0-1 ano\n2. 1-5 anos\n3. 6-12 anos\n4. Continuar";
        }

        $this->session->update(['current_step' => 'register_pregnant']);
        return "CON Existe mulher grávida no agregado?\n1. Sim\n2. Não";
    }

    private function regPregnant(string $input): string
    {
        $this->session->setData('pregnant', $input === '1');
        $this->session->update(['current_step' => 'register_language']);
        return "CON Idioma para mensagens:\n1. Português\n2. Changane\n3. Sena\n4. Macua\n5. Ndau";
    }

    private function regLanguage(string $input): string
    {
        $langMap = ['1' => 'pt', '2' => 'changane', '3' => 'sena', '4' => 'macua', '5' => 'ndau'];
        $this->session->setData('language', $langMap[$input] ?? 'pt');
        $this->session->update(['current_step' => 'register_consent']);

        return "CON Aceita receber alertas ChildShield?\nOs seus dados são protegidos e pode cancelar enviando SAIR.\n1. Sim, aceito\n2. Não";
    }

    private function regConsent(string $input): string
    {
        if ($input !== '1') {
            $this->session->update(['is_active' => false]);
            return "END Registo cancelado. Marque *123# para tentar novamente.";
        }

        $data    = $this->session->session_data;
        $locId   = $data['location_id'] ?? null;
        $location = Location::find($locId);

        // Create user
        $user = User::create([
            'phone_number_encrypted' => User::encryptPhone($this->session->phone_hash), // stores hash temporarily — replace in production
            'phone_hash'             => $this->session->phone_hash,
            'channel'                => 'ussd',
            'language'               => $data['language'] ?? 'pt',
            'location_id'            => $locId,
            'consent_status'         => true,
            'subscription_active'    => true,
            'consent_given_at'       => now(),
        ]);

        // Create household
        Household::create([
            'user_id'            => $user->id,
            'number_of_children' => $data['num_children'] ?? 0,
            'children_age_groups'=> $data['age_groups'] ?? [],
            'pregnant_woman'     => $data['pregnant'] ?? false,
            'vulnerability_score'=> 0,
        ]);

        $this->session->update(['is_active' => false]);

        return "END Registo confirmado! Receberá alertas para " . ($location?->district?->name ?? 'a sua zona') . ". Para cancelar: envie SAIR.";
    }

    // ─── View Alert ───────────────────────────────────────────────────────

    private function viewAlert(string $input): string
    {
        if (!$this->user?->location_id) {
            return "END Registe-se primeiro. Marque *123# e escolha opção 1.";
        }

        $scores = RiskScore::where('location_id', $this->user->location_id)
            ->latestPerLocation()
            ->orderByDesc('score')
            ->get();

        if ($scores->isEmpty()) {
            return "END Sem alertas activos para a sua zona. Volte mais tarde.";
        }

        $top = $scores->first();
        $level = strtoupper($top->risk_level);
        $text = "END ChildShield — {$level}\n{$top->recommendation}";

        return mb_substr($text, 0, 182); // USSD 182 char limit
    }

    // ─── Language ─────────────────────────────────────────────────────────

    private function chooseLanguage(string $input): string
    {
        $langMap = ['1' => 'pt', '2' => 'changane', '3' => 'sena', '4' => 'macua', '5' => 'ndau'];
        if (isset($langMap[$input]) && $this->user) {
            $this->user->update(['language' => $langMap[$input]]);
        }
        return "END Idioma actualizado com sucesso.";
    }

    // ─── Cancel Subscription ──────────────────────────────────────────────

    private function cancelSubscription(string $input): string
    {
        if ($input === '1' && $this->user) {
            $this->user->update(['subscription_active' => false]);
            return "END Subscrição cancelada. Não receberá mais alertas. Obrigado.";
        }
        return "END Cancelamento abortado. Continua a receber alertas.";
    }

    // ─── Change Location ──────────────────────────────────────────────────

    private function changeLocationFlow(string $step, string $input): string
    {
        // Re-use registration province/district steps
        $this->session->update(['current_step' => 'register_province']);
        return $this->startRegistration();
    }
}
