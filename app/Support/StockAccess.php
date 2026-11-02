<?php

namespace App\Support;

use App\Models\User;

class StockAccess
{
    public static function readableLocationIds(User $user, string $locationType): ?array
    {
        if ($user->hasAccessAll()) {
            return null;
        }

        if ($locationType === 'branch') {
            return array_values(array_unique(array_merge(
                $user->accessibleBranchIds() ?: [],
                $user->readableSuppliedBranchIds() ?: [],
            )));
        }

        if ($locationType === 'warehouse') {
            return $user->accessibleWarehouseIds() ?: [];
        }

        return [];
    }

    public static function canRead(User $user, string $locationType, int $locationId): bool
    {
        $ids = self::readableLocationIds($user, $locationType);

        return $ids === null || in_array($locationId, $ids, true);
    }

    public static function canWrite(User $user, string $locationType, int $locationId): bool
    {
        if ($user->hasAccessAll()) {
            return true;
        }

        $ids = $locationType === 'branch'
            ? $user->accessibleBranchIds()
            : ($locationType === 'warehouse' ? $user->accessibleWarehouseIds() : []);

        return in_array($locationId, $ids ?: [], true);
    }
}
