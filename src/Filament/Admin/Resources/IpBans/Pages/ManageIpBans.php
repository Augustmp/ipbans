<?php

namespace Custom\IpBans\Filament\Admin\Resources\IpBans\Pages;

use Custom\IpBans\Filament\Admin\Resources\IpBans\IpBanResource;
use Filament\Resources\Pages\ManageRecords;

class ManageIpBans extends ManageRecords
{
    protected static string $resource = IpBanResource::class;
}
