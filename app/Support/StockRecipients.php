<?php

namespace App\Support;

use App\Models\User;
use Illuminate\Support\Collection;

final class StockRecipients
{
    /**
     * Active users allowed to act at a location.
     *
     * `Gate::before` bypasses permissions for superadmins but not for plain
     * access_all users, so both are handled explicitly here. access_all users
     * receive operational notifications for every location.
     *
     * @return Collection<int, User>
     */
    public static function forLocation(string $permission, string $locationType, int $locationId): Collection
    {
        return User::query()->where('status', 'active')->get()
            ->filter(function (User $user) use ($permission, $locationType, $locationId): bool {
                if ($user->hasAccessAll()) {
                    return true;
                }

                return $user->can($permission) && StockAccess::canWrite($user, $locationType, $locationId);
            })
            ->unique('id')
            ->values();
    }

    /**
     * Active users with a permission, regardless of location scope.
     *
     * @return Collection<int, User>
     */
    public static function withPermission(string $permission): Collection
    {
        return User::query()->where('status', 'active')->get()
            ->filter(fn (User $user): bool => $user->hasAccessAll() || $user->can($permission))
            ->unique('id')
            ->values();
    }
}
