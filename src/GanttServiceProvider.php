<?php

namespace Reedware\NovaGanttMetric;

use Laravel\Nova\Nova;
use Laravel\Nova\Events\ServingNova;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;

class GanttServiceProvider extends ServiceProvider
{
    /**
     * Bootstrap any application services.
     *
     * @return void
     */
    public function boot()
    {
        Nova::serving(function(ServingNova $event) {

            Nova::script('gantt-metric', __DIR__.'/../dist/js/gantt-metric.js');
            Nova::style('gantt-metric', __DIR__.'/../dist/css/gantt-metric.css');

        });
    }

    /**
     * Register any application services.
     *
     * @return void
     */
    public function register()
    {
        //
    }
}
