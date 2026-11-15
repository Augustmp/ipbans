<?php

namespace Custom\IpBans\Providers;

use App\Filament\Admin\Resources\Users\UserResource;
use App\Models\Role;
use App\Models\User;
use Custom\IpBans\Filament\Admin\Resources\Users\RelationManagers\IpBansRelationManager;
use Custom\IpBans\Http\Middleware\BlockBannedIps;
use Custom\IpBans\Listeners\RecordUserIp;
use Custom\IpBans\Models\IpBan;
use Custom\IpBans\Services\IpBanService;
use Illuminate\Auth\Events\Authenticated;
use Illuminate\Contracts\Http\Kernel;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\ServiceProvider;

class IpBansPluginProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(IpBanService::class);

        UserResource::registerCustomRelations(IpBansRelationManager::class);

        Role::registerCustomDefaultPermissions('ipBan');
        Role::registerCustomModelIcon('ipBan', 'tabler-ban');
    }

    public function boot(): void
    {
        User::resolveRelationUsing('ipBans', fn (User $user) => $user->hasMany(IpBan::class, 'user_id'));

        Event::listen(Authenticated::class, RecordUserIp::class);

        $this->registerMiddleware();
    }

    private function registerMiddleware(): void
    {
        $kernel = $this->app->make(Kernel::class);

        // Appended (not prepended) so TrustProxies has already resolved the real client IP behind proxies / Cloudflare.
        if (method_exists($kernel, 'pushMiddleware')) {
            $kernel->pushMiddleware(BlockBannedIps::class);

            return;
        }

        $router = $this->app->make('router');

        foreach (['web', 'api'] as $group) {
            $router->pushMiddlewareToGroup($group, BlockBannedIps::class);
        }
    }
}
