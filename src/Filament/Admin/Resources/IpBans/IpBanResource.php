<?php

namespace Custom\IpBans\Filament\Admin\Resources\IpBans;

use App\Enums\CustomizationKey;
use App\Filament\Admin\Resources\Users\Pages\EditUser;
use App\Models\User;
use Custom\IpBans\Filament\Admin\Resources\IpBans\Pages\ManageIpBans;
use Custom\IpBans\Models\IpBan;
use Custom\IpBans\Rules\IpTargetRule;
use Custom\IpBans\Services\IpBanService;
use Filament\Actions\Action;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Infolists\Components\TextEntry;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class IpBanResource extends Resource
{
    protected static ?string $model = IpBan::class;

    protected static string|\BackedEnum|null $navigationIcon = 'tabler-ban';

    public static function getNavigationLabel(): string
    {
        return trans_choice('ip-bans::strings.ip_ban', 2);
    }

    public static function getModelLabel(): string
    {
        return trans_choice('ip-bans::strings.ip_ban', 1);
    }

    public static function getPluralModelLabel(): string
    {
        return trans_choice('ip-bans::strings.ip_ban', 2);
    }

    public static function getNavigationGroup(): ?string
    {
        if (user()?->getCustomization(CustomizationKey::TopNavigation) === 'topbar') {
            return null;
        }

        return trans('admin/dashboard.user');
    }

    public static function getNavigationBadge(): ?string
    {
        return (string) IpBan::query()->active()->count() ?: null;
    }

    public static function getNavigationBadgeColor(): ?string
    {
        return 'danger';
    }

    /** Validation rule for one IP / CIDR value: valid format, not your own IP, not already banned. */
    public static function targetRule(): IpTargetRule
    {
        return new IpTargetRule();
    }

    /** @return array<int, \Filament\Tables\Columns\Column> */
    public static function getTableColumns(bool $withUser = true): array
    {
        $columns = [
            TextColumn::make('ip')
                ->label(trans('ip-bans::strings.ip'))
                ->icon('tabler-network')
                ->fontFamily('mono')
                ->copyable()
                ->searchable()
                ->sortable(),
        ];

        if ($withUser) {
            $columns[] = TextColumn::make('user.username')
                ->label(trans_choice('ip-bans::strings.user', 1))
                ->icon('tabler-user')
                ->placeholder('—')
                ->searchable()
                ->url(fn (IpBan $ban) => $ban->user && auth()->user()->can('update', $ban->user) ? EditUser::getUrl(['record' => $ban->user]) : null);
        }

        return [
            ...$columns,
            TextColumn::make('unbanned_at')
                ->label(trans('ip-bans::strings.status'))
                ->badge()
                ->sortable()
                ->formatStateUsing(fn ($state) => $state ? trans('ip-bans::strings.lifted') : trans('ip-bans::strings.active'))
                ->color(fn ($state) => $state ? 'success' : 'danger')
                ->icon(fn ($state) => $state ? 'tabler-lock-open' : 'tabler-ban')
                ->tooltip(fn (IpBan $ban) => $ban->unbanned_at
                    ? trans('ip-bans::strings.unbanned_tooltip', ['time' => $ban->unbanned_at->diffForHumans(), 'user' => $ban->unbannedBy?->username ?? '—'])
                    : null),
            TextColumn::make('reason')
                ->label(trans('ip-bans::strings.reason'))
                ->limit(40)
                ->tooltip(fn (IpBan $ban) => $ban->reason)
                ->placeholder(trans('ip-bans::strings.no_reason'))
                ->wrap(),
            TextColumn::make('bannedBy.username')
                ->label(trans('ip-bans::strings.banned_by'))
                ->icon('tabler-shield')
                ->placeholder('—')
                ->toggleable(),
            TextColumn::make('created_at')
                ->label(trans('ip-bans::strings.banned_at'))
                ->since()
                ->dateTimeTooltip()
                ->sortable(),
        ];
    }

    public static function makeUnbanAction(): Action
    {
        return Action::make('unban')
            ->label(trans('ip-bans::strings.unban'))
            ->icon('tabler-lock-open')
            ->color('success')
            ->visible(fn (IpBan $record) => $record->isActive() && static::canEdit($record))
            ->requiresConfirmation()
            ->modalHeading(trans('ip-bans::strings.modals.unban_heading'))
            ->modalDescription(fn (IpBan $record) => trans('ip-bans::strings.modals.unban_description', ['ip' => $record->ip]))
            ->modalSubmitActionLabel(trans('ip-bans::strings.unban'))
            ->action(function (IpBan $record) {
                app(IpBanService::class)->unban($record, auth()->user());

                Notification::make()
                    ->title(trans('ip-bans::strings.notifications.unbanned'))
                    ->body($record->ip)
                    ->success()
                    ->send();
            });
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('created_at', 'desc')
            ->columns(static::getTableColumns())
            ->filters([
                TernaryFilter::make('active')
                    ->label(trans('ip-bans::strings.status'))
                    ->placeholder(trans('ip-bans::strings.all'))
                    ->trueLabel(trans('ip-bans::strings.active'))
                    ->falseLabel(trans('ip-bans::strings.lifted'))
                    ->default(true)
                    ->queries(
                        true: fn (Builder $query) => $query->whereNull('unbanned_at'),
                        false: fn (Builder $query) => $query->whereNotNull('unbanned_at'),
                        blank: fn (Builder $query) => $query,
                    ),
            ])
            ->recordActions([
                static::makeUnbanAction(),
                ViewAction::make()
                    ->hidden(fn ($record) => static::getEditAuthorizationResponse($record)->allowed()),
                EditAction::make(),
                DeleteAction::make(),
            ])
            ->toolbarActions([
                CreateAction::make()
                    ->label(trans('ip-bans::strings.ban_ip'))
                    ->icon('tabler-ban')
                    ->createAnother(false)
                    ->using(fn (array $data, string $model) => $model::create([
                        ...$data,
                        'banned_by' => auth()->id(),
                    ])),
            ])
            ->emptyStateIcon('tabler-ban')
            ->emptyStateHeading(trans('ip-bans::strings.empty'))
            ->emptyStateDescription('');
    }

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('ip')
                    ->label(trans('ip-bans::strings.ip'))
                    ->required()
                    ->maxLength(64)
                    ->placeholder('203.0.113.5')
                    ->prefixIcon('tabler-network')
                    ->hint(trans('ip-bans::strings.ip_hint'))
                    ->rules([static::targetRule()])
                    ->dehydrateStateUsing(fn ($state) => IpBanService::normalize((string) $state) ?? $state)
                    ->disabledOn('edit')
                    ->columnSpanFull(),
                Select::make('user_id')
                    ->label(trans_choice('ip-bans::strings.user', 1))
                    ->prefixIcon('tabler-user')
                    ->relationship('user', 'username')
                    ->searchable(['username', 'email'])
                    ->getOptionLabelFromRecordUsing(fn (User $user) => "$user->username ($user->email)")
                    ->placeholder(trans('ip-bans::strings.no_user'))
                    ->helperText(trans('ip-bans::strings.user_hint'))
                    ->columnSpanFull(),
                Textarea::make('reason')
                    ->label(trans('ip-bans::strings.reason'))
                    ->maxLength(500)
                    ->rows(3)
                    ->columnSpanFull(),
            ]);
    }

    public static function infolist(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextEntry::make('ip')
                    ->label(trans('ip-bans::strings.ip'))
                    ->fontFamily('mono')
                    ->copyable(),
                TextEntry::make('unbanned_at')
                    ->label(trans('ip-bans::strings.status'))
                    ->badge()
                    ->formatStateUsing(fn ($state) => $state ? trans('ip-bans::strings.lifted') : trans('ip-bans::strings.active'))
                    ->color(fn ($state) => $state ? 'success' : 'danger'),
                TextEntry::make('user.username')
                    ->label(trans_choice('ip-bans::strings.user', 1))
                    ->placeholder('—'),
                TextEntry::make('bannedBy.username')
                    ->label(trans('ip-bans::strings.banned_by'))
                    ->placeholder('—'),
                TextEntry::make('created_at')
                    ->label(trans('ip-bans::strings.banned_at'))
                    ->dateTime(),
                TextEntry::make('unbanned_at')
                    ->label(trans('ip-bans::strings.unbanned_at'))
                    ->dateTime()
                    ->placeholder('—'),
                TextEntry::make('reason')
                    ->label(trans('ip-bans::strings.reason'))
                    ->placeholder(trans('ip-bans::strings.no_reason'))
                    ->columnSpanFull(),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ManageIpBans::route('/'),
        ];
    }
}
