<?php

namespace App\Providers;

use App\Models\Form;
use App\Models\Membership;
use App\Models\Submission;
use App\Models\Team;
use App\Observers\FormObserver;
use App\Observers\MembershipObserver;
use App\Observers\SubmissionObserver;
use App\Observers\TeamObserver;
use Carbon\CarbonImmutable;
use Filament\Events\TenantSet;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\ServiceProvider;
use Illuminate\Validation\Rules\Password;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        $this->configureDefaults();

        Event::listen(TenantSet::class, function (TenantSet $event): void {
            $user = $event->getUser();
            $tenant = $event->getTenant();

            if ($user->current_team_id !== $tenant->id) {
                $user->update(['current_team_id' => $tenant->id]);
            }
        });

        $this->registerAuditObservers();
    }

    protected function registerAuditObservers(): void
    {
        Form::observe(FormObserver::class);
        Submission::observe(SubmissionObserver::class);
        Team::observe(TeamObserver::class);
        Membership::observe(MembershipObserver::class);
    }

    /**
     * Configure default behaviors for production-ready applications.
     */
    protected function configureDefaults(): void
    {
        Date::use(CarbonImmutable::class);

        DB::prohibitDestructiveCommands(
            app()->isProduction(),
        );

        Password::defaults(fn (): ?Password => app()->isProduction()
            ? Password::min(12)
                ->mixedCase()
                ->letters()
                ->numbers()
                ->symbols()
                ->uncompromised()
            : null,
        );
    }
}
