<?php
/*
 * This file is part of the overtrue/laravel-wechat.
 *
 * (c) overtrue <i@overtrue.me>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Overtrue\LaravelWeChat;

use EasyWeChat\MiniProgram\Application as MiniProgram;
use EasyWeChat\OfficialAccount\Application as OfficialAccount;
use EasyWeChat\OpenPlatform\Application as OpenPlatform;
use EasyWeChat\Payment\Application as Payment;
use EasyWeChat\Work\Application as Work;
use Illuminate\Foundation\Application as LaravelApplication;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\ServiceProvider as LaravelServiceProvider;
use Laravel\Lumen\Application as LumenApplication;
use Overtrue\LaravelWeChat\Events\OpenPlatformSubscriber;
use Overtrue\LaravelWeChat\Events\WeChatUserAuthorized;
use Overtrue\LaravelWeChat\Events\WeChatUserAuthorizedNotification;

/**
 * Class ServiceProvider.
 *
 * @author overtrue <i@overtrue.me>
 */
class ServiceProvider extends LaravelServiceProvider
{

    /**
     * @var array
     */
    protected $commands = [
        'Overtrue\LaravelWeChat\Commands\InstallCommand',
        'Overtrue\LaravelWeChat\Commands\UpdateCommand',
        'Overtrue\LaravelWeChat\Commands\RefreshAccessTokenCommand',
        'Overtrue\LaravelWeChat\Commands\WechatUserStatisticsCommand',
    ];

    /**
     * The application's route middleware.
     *
     * @var array
     */
    protected $routeMiddleware = [
        "wechat.open_platform_oauth" => \Overtrue\LaravelWeChat\Middleware\PublicPlatformOAuthAuthenticate::class,
        'wechat.oauth'               => \Overtrue\LaravelWeChat\Middleware\OAuthAuthenticate::class,
    ];

    /**
     * The application's route middleware groups.
     *
     * @var array
     */
    protected $middlewareGroups = [
    ];

    /**
     * Boot the provider.
     */
    public function boot()
    {
        $this->loadRoutesFrom(__DIR__.'/../routes/web.php');
        $this->loadRoutesFrom(__DIR__.'/../routes/api.php');

        $this->loadMigrationsFrom(__DIR__.'/../migrations');

        Event::listen(WeChatUserAuthorized::class, WeChatUserAuthorizedNotification::class);
        Event::subscribe(OpenPlatformSubscriber::class);

    }

    /**
     * Setup the config.
     */
    protected function setupConfig()
    {
        $source = realpath(__DIR__.'/config.php');
        if ($this->app instanceof LaravelApplication && $this->app->runningInConsole()) {
            $this->publishes([$source => config_path('wechat.php')], 'laravel-wechat');
        } elseif ($this->app instanceof LumenApplication) {
            $this->app->configure('wechat');
        }
        $this->mergeConfigFrom($source, 'wechat');
    }

    /**
     * Register the provider.
     */
    public function register()
    {
        $this->commands($this->commands);

        $this->registerRouteMiddleware();


        $this->setupConfig();
        $apps = [
            'official_account' => OfficialAccount::class,
            'work'             => Work::class,
            'mini_program'     => MiniProgram::class,
            'payment'          => Payment::class,
            'open_platform'    => OpenPlatform::class,
        ];

        foreach ($apps as $name => $class) {
            if (empty(config('wechat.'.$name))) {
                continue;
            }


            if ($config = config('wechat.route.'.$name)) {
                $this->getRouter()->group($config['attributes'], function ($router) use ($config) {
                    $router->any($config['uri'], $config['action']);
                });
            }
            if (!empty(config('wechat.'.$name.'.app_id')) || !empty(config('wechat.'.$name.'.corp_id'))) {
                $accounts = [
                    'default' => config('wechat.'.$name),
                ];
                config(['wechat.'.$name.'.default' => $accounts['default']]);
            } else {
                $accounts = config('wechat.'.$name);
            }
            foreach ($accounts as $account => $config) {
                $this->app->singleton("wechat.{$name}.{$account}",
                    function ($laravelApp) use ($name, $account, $config, $class) {
                        $app = new $class(array_merge(config('wechat.defaults', []), $config));
                        if (config('wechat.defaults.use_laravel_cache')) {
                            $app['cache'] = new CacheBridge($laravelApp['cache.store']);
                        }
                        $app['request'] = $laravelApp['request'];

                        return $app;
                    });
            }
            $this->app->alias("wechat.{$name}.default", 'wechat.'.$name);
            $this->app->alias("wechat.{$name}.default", 'easywechat.'.$name);
            $this->app->alias('wechat.'.$name, $class);
            $this->app->alias('easywechat.'.$name, $class);
        }
    }

    protected function getRouter()
    {
        if ($this->app instanceof LumenApplication && !class_exists('Laravel\Lumen\Routing\Router')) {
            return $this->app;
        }

        return $this->app->router;
    }

    /**
     * Register the route middleware.
     *
     * @return void
     */
    protected function registerRouteMiddleware()
    {
        // register middleware group.
        foreach ($this->middlewareGroups as $key => $middleware) {
            app('router')->middlewareGroup($key, $middleware);
        }

        // register route middleware.
        foreach ($this->routeMiddleware as $key => $middleware) {
            app('router')->aliasMiddleware($key, $middleware);
        }
    }
}
