<?php

namespace App\Models;

use Acme\Identity\Models\User as BaseUser;
use Filament\Models\Contracts\FilamentUser;
use Filament\Models\Contracts\HasAvatar;
use Filament\Panel;
use Illuminate\Support\Facades\Storage;
use JeffersonGoncalves\Filament\User\Facades\PanelAccess;

/**
 * Columns, casts, factory and the users table itself come from
 * acme/identity — the same package apps/backend requires, so both apps
 * authenticate against the same users table instead of two independent
 * ones. Filament-specific contracts (panel access, avatar) live here, not
 * in the shared package, since acme/identity must not depend on Filament.
 */
class User extends BaseUser implements FilamentUser, HasAvatar
{
    public function canAccessPanel(Panel $panel): bool
    {
        return PanelAccess::check($this, $panel);
    }

    public function canImpersonate(): bool
    {
        return false;
    }

    public function getFilamentAvatarUrl(): ?string
    {
        $avatarColumn = config('filament-edit-profile.avatar_column', 'avatar_url');

        return $this->$avatarColumn ? Storage::url($this->$avatarColumn) : null;
    }
}
