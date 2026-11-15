<?php

namespace Custom\IpBans\Filament\Admin\Resources\Users\RelationManagers;

use App\Models\User;
use Custom\IpBans\Filament\Admin\Resources\IpBans\IpBanResource;
use Custom\IpBans\Models\IpBan;
use Custom\IpBans\Services\IpBanService;
use Filament\Actions\Action;
use Filament\Actions\DeleteAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TagsInput;
use Filament\Forms\Components\Textarea;
use Filament\Notifications\Notification;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;

class IpBansRelationManager extends RelationManager
{
    protected static string $relationship = 'ipBans';

    public static function canViewForRecord(Model $ownerRecord, string $pageClass): bool
    {
        if (static::shouldSkipAuthorization()) {
            return true;
        }

        return IpBanResource::canViewAny();
    }

    /** @return array<string, string> ip => label, only IPs that aren't banned yet */
    private function banOptions(): array
    {
        /** @var User $user */
        $user = $this->getOwnerRecord();
        $service = app(IpBanService::class);

        return $service->knownIps($user)
            ->reject(fn ($lastSeen, string $ip) => $service->isBanned($ip))
            ->mapWithKeys(fn ($lastSeen, string $ip) => [
                $ip => $lastSeen ? "$ip · " . trans('ip-bans::strings.last_seen', ['time' => $lastSeen->diffForHumans()]) : $ip,
            ])
            ->all();
    }

    public function table(Table $table): Table
    {
        return $table
            ->heading(trans_choice('ip-bans::strings.ip_ban', 2))
            ->defaultSort('created_at', 'desc')
            ->columns(IpBanResource::getTableColumns(withUser: false))
            ->headerActions([
                Action::make('ban')
                    ->label(trans('ip-bans::strings.ban_user'))
                    ->icon('tabler-ban')
                    ->color('danger')
                    ->visible(fn () => IpBanResource::canCreate())
                    ->modalHeading(trans('ip-bans::strings.modals.ban_heading'))
                    ->modalDescription(trans('ip-bans::strings.modals.ban_description'))
                    ->modalSubmitActionLabel(trans('ip-bans::strings.ban'))
                    ->schema(function () {
                        $options = $this->banOptions();

                        return [
                            Select::make('ips')
                                ->label(trans('ip-bans::strings.known_ips'))
                                ->multiple()
                                ->options($options)
                                ->default(array_keys($options))
                                ->placeholder(trans('ip-bans::strings.no_known_ips'))
                                ->helperText($options === [] ? trans('ip-bans::strings.no_known_ips_hint') : trans('ip-bans::strings.known_ips_hint')),
                            TagsInput::make('extra_ips')
                                ->label(trans('ip-bans::strings.additional_ips'))
                                ->placeholder('203.0.113.5 / 203.0.113.0/24')
                                ->helperText(trans('ip-bans::strings.ip_hint'))
                                ->nestedRecursiveRules([IpBanResource::targetRule()]),
                            Textarea::make('reason')
                                ->label(trans('ip-bans::strings.reason'))
                                ->maxLength(500)
                                ->rows(3),
                        ];
                    })
                    ->action(function (array $data, Action $action) {
                        $service = app(IpBanService::class);

                        $targets = collect([...($data['ips'] ?? []), ...($data['extra_ips'] ?? [])])
                            ->map(fn ($ip) => IpBanService::normalize((string) $ip))
                            ->filter()
                            ->unique()
                            ->values();

                        if ($targets->isEmpty()) {
                            Notification::make()
                                ->title(trans('ip-bans::strings.notifications.no_ips'))
                                ->warning()
                                ->send();

                            $action->halt();

                            return;
                        }

                        foreach ($targets as $target) {
                            if ($service->wouldLockOut($target, request()->ip())) {
                                Notification::make()
                                    ->title(trans('ip-bans::strings.validation.self_ban'))
                                    ->body($target)
                                    ->danger()
                                    ->send();

                                $action->halt();

                                return;
                            }
                        }

                        /** @var User $user */
                        $user = $this->getOwnerRecord();

                        $bans = $service->banUser($user, $targets->all(), auth()->user(), $data['reason'] ?? null);

                        Notification::make()
                            ->title(trans('ip-bans::strings.notifications.banned', ['count' => $bans->count()]))
                            ->body($bans->pluck('ip')->implode(', '))
                            ->success()
                            ->send();
                    }),
                Action::make('unban_all')
                    ->label(trans('ip-bans::strings.unban_all'))
                    ->icon('tabler-lock-open')
                    ->color('success')
                    ->visible(fn () => IpBanResource::canEdit(new IpBan()) && $this->getOwnerRecord()->ipBans()->active()->exists())
                    ->requiresConfirmation()
                    ->modalHeading(trans('ip-bans::strings.modals.unban_all_heading'))
                    ->modalDescription(trans('ip-bans::strings.modals.unban_all_description'))
                    ->action(function () {
                        /** @var User $user */
                        $user = $this->getOwnerRecord();

                        $count = app(IpBanService::class)->unbanAllForUser($user, auth()->user());

                        Notification::make()
                            ->title(trans('ip-bans::strings.notifications.unbanned_many', ['count' => $count]))
                            ->success()
                            ->send();
                    }),
            ])
            ->recordActions([
                IpBanResource::makeUnbanAction(),
                DeleteAction::make(),
            ])
            ->emptyStateIcon('tabler-shield-check')
            ->emptyStateHeading(trans('ip-bans::strings.empty_user'))
            ->emptyStateDescription('');
    }
}
