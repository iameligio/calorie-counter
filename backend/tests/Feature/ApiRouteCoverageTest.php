<?php

namespace Tests\Feature;

use Illuminate\Routing\Route;
use Illuminate\Support\Facades\Route as RouteFacade;
use Tests\TestCase;

class ApiRouteCoverageTest extends TestCase
{
    /**
     * The group-level throttle was removed because stacking it on top of each
     * route's own limiter doubled the rate-limit cache writes and deadlocked
     * the database cache driver. That safety net is replaced by this test.
     */
    public function test_every_api_route_declares_exactly_one_throttle(): void
    {
        $problems = [];

        foreach ($this->apiRoutes() as $route) {
            $throttles = array_values(array_filter(
                $route->gatherMiddleware(),
                fn ($m) => is_string($m) && str_starts_with($m, 'throttle:')
            ));

            $label = implode('|', $route->methods()).' /'.$route->uri();

            if (count($throttles) === 0) {
                $problems[] = "{$label} has no throttle middleware";
            } elseif (count($throttles) > 1) {
                $problems[] = "{$label} stacks throttles: ".implode(', ', $throttles);
            }
        }

        $this->assertSame([], $problems, implode("\n", $problems));
    }

    /**
     * @return array<int, Route>
     */
    private function apiRoutes(): array
    {
        return array_values(array_filter(
            RouteFacade::getRoutes()->getRoutes(),
            fn (Route $route) => str_starts_with($route->uri(), 'api/')
        ));
    }
}
