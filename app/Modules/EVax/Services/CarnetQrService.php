<?php
declare(strict_types=1);

namespace App\Modules\EVax\Services;

use App\Models\User;
use App\Modules\EVax\Models\Dependent;
use App\Modules\EVax\Models\VaccinationRecord;
use BaconQrCode\Renderer\Image\SvgImageBackEnd;
use BaconQrCode\Renderer\ImageRenderer;
use BaconQrCode\Renderer\RendererStyle\RendererStyle;
use BaconQrCode\Writer;

final class CarnetQrService
{
    public function __construct(private readonly CarnetSignerService $signer) {}

    public function identityUrl(User $user): string
    {
        return url('/carnet/identity/'.$user->carnet_qr_secret);
    }

    public function identityQrSvg(User $user): string
    {
        return $this->renderSvg($this->identityUrl($user));
    }

    public function verificationUrl(User|Dependent $carnet): string
    {
        $base = rtrim((string) config('hosto.carnet.verify_base_url', url('/c/v')), '/');
        $secret = $carnet->carnet_qr_secret;
        $jws = $this->signer->sign($this->buildSignedPayload($carnet));

        return $base.'/'.$secret.'#jws='.$jws;
    }

    public function verificationQrSvg(User|Dependent $carnet): string
    {
        return $this->renderSvg($this->verificationUrl($carnet));
    }

    /** @return array<string, mixed> */
    public function buildSignedPayload(User|Dependent $carnet): array
    {
        $isUser = $carnet instanceof User;

        if ($isUser) {
            $recordsQuery = VaccinationRecord::where('patient_id', $carnet->id);
            $firstName = explode(' ', $carnet->name)[0] ?? '';
            $lastName = trim(substr($carnet->name, strlen($firstName)));
            $dob = $carnet->date_of_birth?->toDateString() ?? '';
            $nip = $carnet->nip;
        } else {
            $recordsQuery = VaccinationRecord::where('dependent_id', $carnet->id);
            $firstName = $carnet->first_name;
            $lastName = $carnet->last_name;
            $dob = $carnet->date_of_birth?->toDateString() ?? '';
            $nip = $carnet->nip;
        }

        $records = $recordsQuery->orderBy('administered_at')->get();

        $vacc = $records->map(fn (VaccinationRecord $r) => [
            'c' => $r->vaccine_code ?: $r->vaccine_name,
            'd' => $r->administered_at?->toDateString(),
            'n' => $r->dose_number,
            'std' => (bool) $r->is_standardized,
        ])->values()->all();

        $fullHashSource = json_encode($vacc, JSON_UNESCAPED_UNICODE);
        $vaccHash = hash('sha256', (string) $fullHashSource);

        // Cap to last 15 doses to keep QR scanner-friendly.
        if (count($vacc) > 15) {
            $vacc = array_slice($vacc, -15);
        }

        $nameHash = hash('sha256', strtolower($firstName.'|'.$lastName.'|'.$dob));
        $rev = (int) ($records->max('carnet_revision') ?? 1);
        $now = time();

        $payload = [
            'iss' => 'hosto.ga',
            'sub' => substr($carnet->uuid, 0, 8),
            'kind' => $isUser ? 'user' : 'dependent',
            'name_hash' => $nameHash,
            'rev' => $rev,
            'vacc' => $vacc,
            'vacc_hash' => $vaccHash,
            'iat' => $now,
            'exp' => $now + (10 * 365 * 24 * 3600),
        ];
        if ($nip) {
            $payload['nip'] = $nip;
        }

        return $payload;
    }

    private function renderSvg(string $data): string
    {
        $renderer = new ImageRenderer(new RendererStyle(220, 1), new SvgImageBackEnd());
        return (new Writer($renderer))->writeString($data);
    }
}
