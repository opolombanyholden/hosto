<?php
declare(strict_types=1);

namespace App\Modules\Core\Http\Controllers\Admin;

use App\Modules\Core\Services\ImpersonationService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;

final class ImpersonationController
{
    public function stop(ImpersonationService $svc): RedirectResponse
    {
        $adminId = session('impersonator_id');
        $svc->stop();
        if ($adminId) {
            Auth::loginUsingId($adminId);
        }
        return redirect('/admin');
    }
}
