<?php

namespace App\Services;

use App\Models\Direction;
use App\Models\Role;
use App\Models\User;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class DirectorAssignmentService
{
    /**
     * Lock all relevant direction rows in a stable order.
     *
     * @param  array<int, int|null>  $directionIds
     * @return array<int, Direction>
     */
    public function lockDirections(array $directionIds): array
    {
        $ids = array_values(array_unique(array_filter(
            array_map(static fn ($id): ?int => $id === null ? null : (int) $id, $directionIds),
            static fn (?int $id): bool => $id !== null,
        )));
        sort($ids, SORT_NUMERIC);

        $directions = [];

        foreach ($ids as $id) {
            $direction = Direction::query()
                ->whereKey($id)
                ->lockForUpdate()
                ->first();

            if ($direction === null) {
                throw (new ModelNotFoundException)->setModel(Direction::class, [$id]);
            }

            $directions[$id] = $direction;
        }

        return $directions;
    }

    public function assertAvailable(int $directionId, ?int $excludeUserId = null): void
    {
        if (DB::transactionLevel() < 1) {
            throw new \LogicException('Director availability must be checked inside a transaction.');
        }

        $this->lockDirections([$directionId]);

        $directorRole = Role::query()->where('code', Role::DIRECTEUR)->first();

        if ($directorRole === null) {
            return;
        }

        $query = User::query()
            ->where('direction_id', $directionId)
            ->where('status', 'ACTIVE')
            ->where('role_id', $directorRole->id);

        if ($excludeUserId !== null) {
            $query->whereKeyNot($excludeUserId);
        }

        if ($query->lockForUpdate()->first() !== null) {
            throw ValidationException::withMessages([
                'direction_id' => 'Cette direction est déjà occupée par un directeur actif.',
            ]);
        }
    }
}
