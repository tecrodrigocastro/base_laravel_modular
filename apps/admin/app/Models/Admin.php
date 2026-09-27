<?php

namespace App\Models;

use Acme\Identity\Models\Admin as BaseAdmin;
use Filament\Models\Contracts\FilamentUser;
use Filament\Models\Contracts\HasAvatar;
use Filament\Panel;
use Illuminate\Support\Facades\Storage;

/**
 * Columns, casts, factory and the admins table itself come from
 * acme/identity. Filament-specific contracts live here, not in the shared
 * package, since acme/identity must not depend on Filament.
 *
 * canAccessPanel() is reimplemented locally rather than delegated to
 * jeffersongoncalves/filament-admin's FilamentAdmin facade: that facade's
 * canAccessPanel() is type-hinted to its own vendor Admin model, which
 * $this no longer is now that this class extends the shared package's
 * Admin instead. Logic mirrors that facade's default (status-gated, with
 * an optional config-driven override) so behavior is unchanged.
 */
class Admin extends BaseAdmin implements FilamentUser, HasAvatar
{
    public function canAccessPanel(Panel $panel): bool
    {
        /** @var class-string|null $gate */
        $gate = config('filament-admin.can_access_panel');

        if ($gate) {
            return (bool) resolve($gate)($this, $panel);
        }

        return $this->status === true;
    }

    public function canImpersonate(): bool
    {
        return true;
    }

    public function getFilamentAvatarUrl(): ?string
    {
        $avatarColumn = config('filament-edit-profile.avatar_column', 'avatar_url');

        return $this->$avatarColumn ? Storage::url($this->$avatarColumn) : null;
    }
}
