<?php

declare(strict_types=1);

namespace App\Http\Controllers\Concerns;

use App\Modules\Annuaire\Models\Hosto;
use App\Modules\Core\Models\Role;
use App\Modules\Core\Models\UserRoleAssignment;
use Illuminate\Http\Request;

/**
 * Pharmacists and lab staff work for one structure, given by the scope of
 * their role. Without such a scoped role the space is forbidden.
 */
trait ResolvesStaffStructure
{
    private function staffStructure(Request $request, string $roleSlug): Hosto
    {
        $assignment = UserRoleAssignment::where('user_id', $request->user()->id)
            ->where('role_id', Role::where('slug', $roleSlug)->value('id'))
            ->where('scope_type', Hosto::class)
            ->first();

        abort_if($assignment === null, 403);

        return Hosto::findOrFail($assignment->scope_id);
    }
}
