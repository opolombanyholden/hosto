<?php
declare(strict_types=1);

namespace App\Modules\RendezVous\Services;

use App\Models\User;
use App\Modules\RendezVous\Models\Appointment;
use App\Modules\RendezVous\Models\AppointmentDocument;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

final class DocumentUploadService
{
    public const MAX_PER_FILE_BYTES = 10 * 1024 * 1024;
    public const MAX_FILES_PER_APPOINTMENT = 5;
    public const MAX_TOTAL_BYTES = 30 * 1024 * 1024;
    public const DISK = 'private_appointments';

    private const ALLOWED_MIMES = [
        'application/pdf', 'image/jpeg', 'image/png', 'image/heic', 'image/heif',
    ];

    public function store(
        Appointment $apt,
        UploadedFile $file,
        User $uploader,
        ?string $category = null,
    ): AppointmentDocument {
        $mime = $file->getMimeType();
        if (! in_array($mime, self::ALLOWED_MIMES, true)) {
            throw new \InvalidArgumentException("Mime type not allowed: {$mime}");
        }
        $size = $file->getSize();
        if ($size > self::MAX_PER_FILE_BYTES) {
            throw new \DomainException('File exceeds 10 MB limit');
        }
        $existing = AppointmentDocument::where('appointment_id', $apt->id)->get();
        if ($existing->count() >= self::MAX_FILES_PER_APPOINTMENT) {
            throw new \DomainException('Max 5 documents per appointment');
        }
        $total = $existing->sum('size_bytes') + $size;
        if ($total > self::MAX_TOTAL_BYTES) {
            throw new \DomainException('Total size exceeds 30 MB limit');
        }

        $ext = $file->getClientOriginalExtension() ?: $this->extensionForMime($mime);
        $hash = hash('sha256', $apt->uuid.$file->getClientOriginalName().microtime(true));
        $relativePath = $apt->uuid.'/'.substr($hash, 0, 32).'.'.$ext;

        Storage::disk(self::DISK)->putFileAs($apt->uuid, $file, substr($hash, 0, 32).'.'.$ext);

        return AppointmentDocument::create([
            'appointment_id' => $apt->id,
            'uploaded_by_id' => $uploader->id,
            'original_name' => $file->getClientOriginalName(),
            'stored_path' => 'appointments/'.$relativePath,
            'mime_type' => $mime,
            'size_bytes' => $size,
            'category' => $category,
            'keep_in_dpe' => false,
        ]);
    }

    public function download(AppointmentDocument $doc, User $accessor): StreamedResponse
    {
        if (! $this->canAccess($doc, $accessor)) {
            abort(403, 'Accès refusé à ce document.');
        }
        $relativeOnDisk = str_replace('appointments/', '', $doc->stored_path);
        if (! Storage::disk(self::DISK)->exists($relativeOnDisk)) {
            abort(404, 'Fichier introuvable.');
        }
        return Storage::disk(self::DISK)->download($relativeOnDisk, $doc->original_name, [
            'Content-Type' => $doc->mime_type,
        ]);
    }

    public function delete(AppointmentDocument $doc, User $by): void
    {
        $doc->delete();
    }

    public function promoteToDpe(AppointmentDocument $doc): void
    {
        $doc->update(['keep_in_dpe' => true]);
    }

    public function canAccess(AppointmentDocument $doc, User $user): bool
    {
        $apt = $doc->appointment;
        if ($apt->patient_id === $user->id) return true;
        if ($apt->third_party_user_id === $user->id) return true;
        if ($apt->practitioner && $apt->practitioner->user_id === $user->id) return true;
        if ($user->can('appointments.manage') || $user->can('users.view')) return true;
        return false;
    }

    private function extensionForMime(string $mime): string
    {
        return match ($mime) {
            'application/pdf' => 'pdf',
            'image/jpeg' => 'jpg',
            'image/png' => 'png',
            'image/heic', 'image/heif' => 'heic',
            default => 'bin',
        };
    }
}
