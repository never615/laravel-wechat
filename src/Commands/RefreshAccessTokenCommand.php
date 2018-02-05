<?php
/**
 * Copyright (c) 2017. Mallto.Co.Ltd.<mall-to.com> All rights reserved.
 */

namespace Overtrue\LaravelWechat\Commands;


use Illuminate\Console\Command;
use Overtrue\LaravelWechat\Domain\AccessTokenUsecase;

class RefreshAccessTokenCommand extends Command
{


    /**
     * The console command name.
     *
     * @var string
     */
    protected $name = 'wechat:refresh_access_token';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = '刷新开放平台下各授权公众号的access_token';

    /**
     * Install directory.
     *
     * @var string
     */
    protected $directory = '';
    /**
     * @var AccessTokenUsecase
     */
    private $accessTokenUsecase;

    /**
     *
     * /**
     * ParkRecordCheckCommand constructor.
     *
     */
    public function __construct(AccessTokenUsecase $accessTokenUsecase)
    {
        $this->accessTokenUsecase = $accessTokenUsecase;
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return void
     */
    public function fire()
    {
        \Log::warning("refresh command");

        $this->accessTokenUsecase->refreshAccessToken();

    }


}
