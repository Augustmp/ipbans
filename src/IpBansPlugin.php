<?php

namespace Custom\IpBans;

use App\Contracts\Plugins\HasPluginSettings;
use App\Traits\EnvironmentWriterTrait;
use Custom\IpBans\Rules\IpTargetRule;
use Filament\Contracts\Plugin;
use Filament\Forms\Components\TagsInput;
use Filament\Notifications\Notification;
use Filament\Panel;
use Filament\Schemas\Components\Section;

class IpBansPlugin implements HasPluginSettings, Plugin
{
    use EnvironmentWriterTrait;

    public function getId(): string
    {
        return 'ip-bans';
    }

    public function register(Panel $panel): void
    {
        if ($panel->getId() !== 'admin') {
            return;
        }

        $panel->discoverResources(plugin_path($this->getId(), 'src/Filament/Admin/Resources'), 'Custom\\IpBans\\Filament\\Admin\\Resources');
    }

    public function boot(Panel $panel): void {}

    public function getSettingsFormData(): array
    {
        return [
            'whitelist' => array_filter(explode(',', (string) config('ip-bans.whitelist', ''))),
        ];
    }

    public function getSettingsForm(): array
    {
        return [
            Section::make(trans('ip-bans::strings.settings.safety'))
                ->schema([
                    TagsInput::make('whitelist')
                        ->label(trans('ip-bans::strings.settings.whitelist'))
                        ->placeholder(trans('ip-bans::strings.settings.whitelist_placeholder'))
                        ->hintIcon('tabler-question-mark')
                        ->hintIconTooltip(trans('ip-bans::strings.settings.whitelist_hint'))
                        ->nestedRecursiveRules([new IpTargetRule(checkBanState: false)])
                        ->default(fn () => array_filter(explode(',', (string) config('ip-bans.whitelist', '')))),
                ]),
        ];
    }

    public function saveSettings(array $data): void
    {
        $this->writeToEnvironment([
            'IPBANS_WHITELIST' => implode(',', $data['whitelist'] ?? []),
        ]);

        Notification::make()
            ->title(trans('ip-bans::strings.settings.saved'))
            ->success()
            ->send();
    }
}
