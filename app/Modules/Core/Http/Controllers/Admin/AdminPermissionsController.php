<?php
declare(strict_types=1);

namespace App\Modules\Core\Http\Controllers\Admin;

use App\Modules\Core\Models\Permission;
use Illuminate\Contracts\View\View;

final class AdminPermissionsController
{
    public function index(): View
    {
        $permissions = Permission::orderBy('scope')->orderBy('display_order')->get()->groupBy('scope');
        return view('admin.permissions.index', compact('permissions'));
    }
}
