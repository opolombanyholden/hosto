<?php
declare(strict_types=1);

namespace App\Modules\EVax\Database\Seeders;

use App\Modules\EVax\Models\Vaccine;
use Illuminate\Database\Seeder;

/**
 * Programme Élargi de Vaccination (PEV) — calendrier OMS Afrique.
 */
class VaccinePevSeeder extends Seeder
{
    public function run(): void
    {
        $vaccines = [
            ['code' => 'BCG',     'name_fr' => 'BCG',                          'diseases' => ['tuberculose'],          'schedule_age_days' => 0,    'doses_total' => 1, 'order' => 1,  'oms' => 'XM1NL1'],
            ['code' => 'HEPB0',   'name_fr' => 'Hepatite B (naissance)',       'diseases' => ['hepatite_b'],           'schedule_age_days' => 0,    'doses_total' => 1, 'order' => 2,  'oms' => 'XM9QW8'],
            ['code' => 'OPV0',    'name_fr' => 'Polio oral (naissance)',       'diseases' => ['poliomyelite'],         'schedule_age_days' => 0,    'doses_total' => 1, 'order' => 3,  'oms' => 'XM7EE8'],
            ['code' => 'PENTA1',  'name_fr' => 'Pentavalent (DTC-HepB-Hib) 1', 'diseases' => ['dtc', 'hepatite_b', 'hib'], 'schedule_age_days' => 42,'doses_total' => 3, 'order' => 4, 'oms' => 'XM6AT1'],
            ['code' => 'OPV1',    'name_fr' => 'Polio oral 1',                 'diseases' => ['poliomyelite'],         'schedule_age_days' => 42,   'doses_total' => 1, 'order' => 5,  'oms' => 'XM7EE8'],
            ['code' => 'PCV1',    'name_fr' => 'Pneumocoque 1',                'diseases' => ['pneumocoque'],          'schedule_age_days' => 42,   'doses_total' => 3, 'order' => 6,  'oms' => 'XM5DF1'],
            ['code' => 'ROTA1',   'name_fr' => 'Rotavirus 1',                  'diseases' => ['rotavirus'],            'schedule_age_days' => 42,   'doses_total' => 2, 'order' => 7,  'oms' => 'XM6BV3'],
            ['code' => 'PENTA2',  'name_fr' => 'Pentavalent 2',                'diseases' => ['dtc', 'hepatite_b', 'hib'], 'schedule_age_days' => 70,'doses_total' => 3, 'order' => 8, 'oms' => 'XM6AT1'],
            ['code' => 'OPV2',    'name_fr' => 'Polio oral 2',                 'diseases' => ['poliomyelite'],         'schedule_age_days' => 70,   'doses_total' => 1, 'order' => 9,  'oms' => 'XM7EE8'],
            ['code' => 'PCV2',    'name_fr' => 'Pneumocoque 2',                'diseases' => ['pneumocoque'],          'schedule_age_days' => 70,   'doses_total' => 3, 'order' => 10, 'oms' => 'XM5DF1'],
            ['code' => 'ROTA2',   'name_fr' => 'Rotavirus 2',                  'diseases' => ['rotavirus'],            'schedule_age_days' => 70,   'doses_total' => 2, 'order' => 11, 'oms' => 'XM6BV3'],
            ['code' => 'PENTA3',  'name_fr' => 'Pentavalent 3',                'diseases' => ['dtc', 'hepatite_b', 'hib'], 'schedule_age_days' => 98,'doses_total' => 3, 'order' => 12, 'oms' => 'XM6AT1'],
            ['code' => 'OPV3',    'name_fr' => 'Polio oral 3',                 'diseases' => ['poliomyelite'],         'schedule_age_days' => 98,   'doses_total' => 1, 'order' => 13, 'oms' => 'XM7EE8'],
            ['code' => 'PCV3',    'name_fr' => 'Pneumocoque 3',                'diseases' => ['pneumocoque'],          'schedule_age_days' => 98,   'doses_total' => 3, 'order' => 14, 'oms' => 'XM5DF1'],
            ['code' => 'VPI',     'name_fr' => 'Polio inactive (IPV)',         'diseases' => ['poliomyelite'],         'schedule_age_days' => 98,   'doses_total' => 1, 'order' => 15, 'oms' => 'XM7L23'],
            ['code' => 'RR1',     'name_fr' => 'Rougeole-Rubeole 1',           'diseases' => ['rougeole', 'rubeole'],  'schedule_age_days' => 270,  'doses_total' => 2, 'order' => 16, 'oms' => 'XM9NK2'],
            ['code' => 'FJ',      'name_fr' => 'Fievre jaune',                 'diseases' => ['fievre_jaune'],         'schedule_age_days' => 270,  'doses_total' => 1, 'order' => 17, 'oms' => 'XM8AY1'],
            ['code' => 'RR2',     'name_fr' => 'Rougeole-Rubeole 2',           'diseases' => ['rougeole', 'rubeole'],  'schedule_age_days' => 540,  'doses_total' => 2, 'order' => 18, 'oms' => 'XM9NK2'],
            ['code' => 'MENA',    'name_fr' => 'Meningite A',                  'diseases' => ['meningite'],            'schedule_age_days' => 540,  'doses_total' => 1, 'order' => 19, 'oms' => 'XM5LP2'],
            ['code' => 'HPV1',    'name_fr' => 'HPV (papillomavirus) 1',       'diseases' => ['hpv'],                  'schedule_age_days' => 3285, 'doses_total' => 2, 'order' => 20, 'oms' => 'XM4PV8'],
            ['code' => 'HPV2',    'name_fr' => 'HPV (papillomavirus) 2',       'diseases' => ['hpv'],                  'schedule_age_days' => 3465, 'doses_total' => 2, 'order' => 21, 'oms' => 'XM4PV8'],
            ['code' => 'TETA',    'name_fr' => 'Tetanos (adulte)',             'diseases' => ['tetanos'],              'schedule_age_days' => null, 'doses_total' => 5, 'order' => 22, 'oms' => 'XM2TT1'],
            ['code' => 'COVID',   'name_fr' => 'COVID-19',                     'diseases' => ['covid19'],              'schedule_age_days' => null, 'doses_total' => 2, 'order' => 23, 'oms' => 'XM68M0'],
            ['code' => 'GRIPPE',  'name_fr' => 'Grippe saisonniere',           'diseases' => ['grippe'],               'schedule_age_days' => null, 'doses_total' => 1, 'order' => 24, 'oms' => 'XM5LP3'],
            ['code' => 'RAGE',    'name_fr' => 'Rage',                         'diseases' => ['rage'],                 'schedule_age_days' => null, 'doses_total' => 3, 'order' => 25, 'oms' => 'XM4RG1'],
            ['code' => 'TYPHO',   'name_fr' => 'Typhoide',                     'diseases' => ['typhoide'],             'schedule_age_days' => null, 'doses_total' => 1, 'order' => 26, 'oms' => 'XM3TY1'],
        ];

        foreach ($vaccines as $v) {
            Vaccine::updateOrCreate(
                ['code' => $v['code']],
                [
                    'oms_code' => $v['oms'],
                    'name_fr' => $v['name_fr'],
                    'diseases' => $v['diseases'],
                    'schedule_age_days' => $v['schedule_age_days'],
                    'doses_total' => $v['doses_total'],
                    'is_standardized' => true,
                    'display_order' => $v['order'],
                    'is_active' => true,
                ]
            );
        }
    }
}
