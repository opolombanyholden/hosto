<?php
declare(strict_types=1);

namespace App\Modules\Core\Services;

use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Symfony\Component\HttpFoundation\StreamedResponse;

final class UserExportService
{
    /**
     * @param  Builder<User>  $query
     */
    public function exportCsv(Builder $query): StreamedResponse
    {
        $filename = 'users-export-'.now()->format('Y-m-d-His').'.csv';

        return new StreamedResponse(function () use ($query) {
            $out = fopen('php://output', 'w');
            // BOM UTF-8 for Excel compatibility
            fwrite($out, "\xEF\xBB\xBF");
            // Header row
            fputcsv($out, ['uuid', 'name', 'email', 'phone', 'nip', 'roles', 'created_at', 'status'], ',');

            $query->with('roles')->chunk(500, function ($users) use ($out) {
                foreach ($users as $u) {
                    fputcsv($out, [
                        $u->uuid,
                        $u->name,
                        $u->email,
                        $u->phone ?? '',
                        $u->nip ?? '',
                        $u->roles->pluck('slug')->join('|'),
                        $u->created_at?->toIso8601String() ?? '',
                        $u->deleted_at ? 'deleted' : ($u->locked_until && $u->locked_until->isFuture() ? 'locked' : 'active'),
                    ], ',');
                }
            });
            fclose($out);
        }, 200, [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
        ]);
    }
}
