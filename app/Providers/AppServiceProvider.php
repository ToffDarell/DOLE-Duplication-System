<?php

namespace App\Providers;

use App\Models\DuplicateFlag;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Request-scoped cache of the duplicate-flag counters shared with the
     * navigation chrome so every badge reports identical numbers.
     *
     * @var array{navDuplicateTotal: int, navDuplicatePending: int}|null
     */
    private ?array $navDuplicateStats = null;

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
        View::composer(
            ['layouts.partials.topbar', 'layouts.partials.sidebar'],
            fn ($view) => $view->with($this->navDuplicateStats()),
        );
    }

    /**
     * Resolve the duplicate counters a single time per request so the header
     * badge, sidebar badge, and dashboard KPI cards stay in sync.
     *
     * @return array{navDuplicateTotal: int, navDuplicatePending: int}
     */
    private function navDuplicateStats(): array
    {
        if ($this->navDuplicateStats === null) {
            $row = DuplicateFlag::selectRaw("COUNT(*) as total_count, SUM(status = 'pending') as pending_count")->first();

            $this->navDuplicateStats = [
                'navDuplicateTotal' => (int) ($row->total_count ?? 0),
                'navDuplicatePending' => (int) ($row->pending_count ?? 0),
            ];
        }

        return $this->navDuplicateStats;
    }
}
