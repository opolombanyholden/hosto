<?php

declare(strict_types=1);

namespace App\Modules\Annuaire\Database\Seeders;

use App\Modules\Annuaire\Models\Hosto;
use App\Modules\Annuaire\Models\HostoMedia;
use Illuminate\Database\Seeder;

/**
 * Seeds demo media for the seeded Libreville structures.
 *
 * Uses placeholder images for demonstration. In production, structures
 * will upload their own images via the admin interface (Phase 1.8).
 */
final class HostoMediaSeeder extends Seeder
{
    public function run(): void
    {
        $structures = Hosto::all();

        foreach ($structures as $hosto) {
            // Profile image (unique per structure, derived from primary type icon)
            $primaryType = $hosto->structureTypes()->wherePivot('is_primary', true)->first();
            $icon = $primaryType->icon ?? 'icon-hopitaux';

            if (! $hosto->media()->where('type', 'profile')->exists()) {
                HostoMedia::create([
                    'hosto_id' => $hosto->id,
                    'type' => 'profile',
                    'url' => "/images/icons/{$icon}.png",
                    'alt_text' => $hosto->name,
                    'mime_type' => 'image/png',
                    'display_order' => 0,
                ]);
            }

            // Cover image (placeholder gradient SVG)
            if (! $hosto->media()->where('type', 'cover')->exists()) {
                HostoMedia::create([
                    'hosto_id' => $hosto->id,
                    'type' => 'cover',
                    'url' => $this->coverPlaceholder($hosto->name),
                    'alt_text' => "Couverture {$hosto->name}",
                    'mime_type' => 'image/svg+xml',
                    'display_order' => 0,
                ]);
            }

            // Gallery images (2-3 demo images per structure)
            if (! $hosto->media()->where('type', 'gallery')->exists()) {
                $galleryItems = $this->galleryFor($hosto);
                foreach ($galleryItems as $i => $item) {
                    HostoMedia::create([
                        'hosto_id' => $hosto->id,
                        'type' => 'gallery',
                        'url' => $item['url'],
                        'alt_text' => $item['alt'],
                        'mime_type' => 'image/png',
                        'is_primary' => $i === 0,
                        'display_order' => $i,
                    ]);
                }
            }
        }
    }

    /**
     * Generate an inline SVG data URI as a cover placeholder.
     */
    private function coverPlaceholder(string $name): string
    {
        $hash = crc32($name);
        $hue = $hash % 360;
        $text = mb_substr($name, 0, 20);
        $encodedText = htmlspecialchars($text, ENT_XML1 | ENT_QUOTES, 'UTF-8');

        return "data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='800' height='250'%3E"
            ."%3Cdefs%3E%3ClinearGradient id='g' x1='0' y1='0' x2='1' y2='1'%3E"
            ."%3Cstop offset='0' stop-color='hsl({$hue},50%25,35%25)'/%3E"
            ."%3Cstop offset='1' stop-color='hsl(".($hue + 40).",60%25,50%25)'/%3E"
            .'%3C/linearGradient%3E%3C/defs%3E'
            ."%3Crect width='800' height='250' fill='url(%23g)'/%3E"
            ."%3Ctext x='50%25' y='55%25' text-anchor='middle' fill='white' font-family='Poppins,sans-serif' font-size='28' font-weight='600' opacity='.7'%3E{$encodedText}%3C/text%3E"
            .'%3C/svg%3E';
    }

    /**
     * @return list<array{url: string, alt: string}>
     */
    private function galleryFor(Hosto $hosto): array
    {
        $icons = ['icon-hopitaux', 'icon-doctor', 'icon-pharmacie', 'icon-laboratoire', 'icon-infosantes'];
        $items = [];

        for ($i = 0; $i < min(3, count($icons)); $i++) {
            $items[] = [
                'url' => '/images/icons/'.$icons[($hosto->id + $i) % count($icons)].'.png',
                'alt' => "{$hosto->name} - Photo ".($i + 1),
            ];
        }

        return $items;
    }
}
