<?php

namespace App\Providers;

use App\Models\Alert;
use App\Models\Campaign;
use App\Models\ClinicUser;
use App\Policies\AlertPolicy;
use App\Policies\CampaignPolicy;
use App\Policies\ClinicUserPolicy;
use Dedoc\Scramble\Scramble;
use Dedoc\Scramble\Support\Generator\OpenApi;
use Dedoc\Scramble\Support\Generator\SecurityScheme;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Gate;

class AppServiceProvider extends ServiceProvider
{
    // ─── Policy Mappings ──────────────────────────────────────────────────

    protected $policies = [
        Alert::class      => AlertPolicy::class,
        Campaign::class   => CampaignPolicy::class,
        ClinicUser::class => ClinicUserPolicy::class,
    ];

    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        // Register policies
        foreach ($this->policies as $model => $policy) {
            Gate::policy($model, $policy);
        }

        // Configure Scramble OpenAPI docs
        Scramble::configure()
            ->withDocumentTransformers(function (OpenApi $openApi) {
                $openApi->secure(
                    SecurityScheme::http('bearer', 'JWT')
                );
            });

    }
}
