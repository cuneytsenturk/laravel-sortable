<?php

namespace Akaunting\Sortable;

use Akaunting\Sortable\View\Components\SortableLink;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\ServiceProvider;
use Illuminate\View\Compilers\BladeCompiler;

final class Provider extends ServiceProvider
{
    /**
     * Register the application services.
     */
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__ . '/Config/sortable.php', 'sortable');
    }

    /**
     * Bootstrap the application services.
     */
    public function boot(): void
    {
        $this->publishes([
            __DIR__ . '/Config/sortable.php' => config_path('sortable.php'),
        ], 'sortable');

        $this->registerBladeDirectives();
        $this->registerBladeComponents();
        $this->registerMacros();
    }

    public function registerBladeDirectives(): void
    {
        $this->callAfterResolving('blade.compiler', function (BladeCompiler $compiler): void {
            $compiler->directive('sortablelink', function (string $expression): string {
                $expression = ($expression[0] === '(') ? substr($expression, 1, -1) : $expression;

                return "<?php echo \Akaunting\Sortable\Support\SortableLink::render(array ({$expression}));?>";
            });
        });
    }

    public function registerBladeComponents(): void
    {
        Blade::component('sortablelink', SortableLink::class);
    }

    public function registerMacros(): void
    {
        request()->macro('allFilled', function (array $keys): bool {
            foreach ($keys as $key) {
                if (! $this->filled($key)) {
                    return false;
                }
            }

            return true;
        });
    }
}
