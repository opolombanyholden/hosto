<?php

declare(strict_types=1);

namespace App\Modules\Referentiel\Database\Seeders;

use App\Modules\Referentiel\Models\ReferenceData;
use Illuminate\Database\Seeder;

/**
 * Seeds all enum-type reference data for dropdown menus.
 */
final class ReferenceDataSeeder extends Seeder
{
    public function run(): void
    {
        foreach ($this->data() as $item) {
            ReferenceData::firstOrCreate(
                ['category' => $item['category'], 'code' => $item['code']],
                $item,
            );
        }
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function data(): array
    {
        return [
            // --- ID Document Types ---
            ['category' => 'id_document_type', 'code' => 'cni', 'label_fr' => 'Carte Nationale d\'Identite', 'label_en' => 'National ID Card', 'display_order' => 1],
            ['category' => 'id_document_type', 'code' => 'passeport', 'label_fr' => 'Passeport', 'label_en' => 'Passport', 'display_order' => 2],
            ['category' => 'id_document_type', 'code' => 'carte_sejour', 'label_fr' => 'Carte de sejour', 'label_en' => 'Residence Permit', 'display_order' => 3],
            ['category' => 'id_document_type', 'code' => 'permis_conduire', 'label_fr' => 'Permis de conduire', 'label_en' => 'Driver\'s License', 'display_order' => 4],

            // --- Gender ---
            ['category' => 'gender', 'code' => 'male', 'label_fr' => 'Masculin', 'label_en' => 'Male', 'display_order' => 1],
            ['category' => 'gender', 'code' => 'female', 'label_fr' => 'Feminin', 'label_en' => 'Female', 'display_order' => 2],

            // --- Blood Groups ---
            ['category' => 'blood_group', 'code' => 'A+', 'label_fr' => 'A+', 'label_en' => 'A+', 'display_order' => 1],
            ['category' => 'blood_group', 'code' => 'A-', 'label_fr' => 'A-', 'label_en' => 'A-', 'display_order' => 2],
            ['category' => 'blood_group', 'code' => 'B+', 'label_fr' => 'B+', 'label_en' => 'B+', 'display_order' => 3],
            ['category' => 'blood_group', 'code' => 'B-', 'label_fr' => 'B-', 'label_en' => 'B-', 'display_order' => 4],
            ['category' => 'blood_group', 'code' => 'AB+', 'label_fr' => 'AB+', 'label_en' => 'AB+', 'display_order' => 5],
            ['category' => 'blood_group', 'code' => 'AB-', 'label_fr' => 'AB-', 'label_en' => 'AB-', 'display_order' => 6],
            ['category' => 'blood_group', 'code' => 'O+', 'label_fr' => 'O+', 'label_en' => 'O+', 'display_order' => 7],
            ['category' => 'blood_group', 'code' => 'O-', 'label_fr' => 'O-', 'label_en' => 'O-', 'display_order' => 8],

            // --- Security Questions ---
            ['category' => 'security_question', 'code' => 'animal', 'label_fr' => 'Quel est le nom de votre premier animal de compagnie ?', 'label_en' => 'What is the name of your first pet?', 'display_order' => 1],
            ['category' => 'security_question', 'code' => 'mere', 'label_fr' => 'Quel est le nom de jeune fille de votre mere ?', 'label_en' => 'What is your mother\'s maiden name?', 'display_order' => 2],
            ['category' => 'security_question', 'code' => 'ville_naissance', 'label_fr' => 'Dans quelle ville etes-vous ne(e) ?', 'label_en' => 'In which city were you born?', 'display_order' => 3],
            ['category' => 'security_question', 'code' => 'ami', 'label_fr' => 'Quel est le nom de votre meilleur ami d\'enfance ?', 'label_en' => 'What is the name of your childhood best friend?', 'display_order' => 4],
            ['category' => 'security_question', 'code' => 'ecole', 'label_fr' => 'Quel est le nom de votre premiere ecole ?', 'label_en' => 'What is the name of your first school?', 'display_order' => 5],
            ['category' => 'security_question', 'code' => 'plat', 'label_fr' => 'Quel est votre plat prefere ?', 'label_en' => 'What is your favorite dish?', 'display_order' => 6],

            // --- Contact Relations ---
            ['category' => 'contact_relation', 'code' => 'enfant', 'label_fr' => 'Enfant', 'label_en' => 'Child', 'display_order' => 1],
            ['category' => 'contact_relation', 'code' => 'parent', 'label_fr' => 'Parent', 'label_en' => 'Parent', 'display_order' => 2],
            ['category' => 'contact_relation', 'code' => 'conjoint', 'label_fr' => 'Conjoint(e)', 'label_en' => 'Spouse', 'display_order' => 3],
            ['category' => 'contact_relation', 'code' => 'frere_soeur', 'label_fr' => 'Frere / Soeur', 'label_en' => 'Sibling', 'display_order' => 4],
            ['category' => 'contact_relation', 'code' => 'ami', 'label_fr' => 'Ami(e)', 'label_en' => 'Friend', 'display_order' => 5],
            ['category' => 'contact_relation', 'code' => 'autre', 'label_fr' => 'Autre', 'label_en' => 'Other', 'display_order' => 6],

            // --- Publication Types ---
            ['category' => 'publication_type', 'code' => 'activity', 'label_fr' => 'Activite', 'label_en' => 'Activity', 'display_order' => 1],
            ['category' => 'publication_type', 'code' => 'research', 'label_fr' => 'Travaux / Recherche', 'label_en' => 'Research', 'display_order' => 2],
            ['category' => 'publication_type', 'code' => 'tip', 'label_fr' => 'Conseil sante', 'label_en' => 'Health Tip', 'display_order' => 3],
            ['category' => 'publication_type', 'code' => 'video', 'label_fr' => 'Video', 'label_en' => 'Video', 'display_order' => 4],

            // --- Care Types ---
            ['category' => 'care_type', 'code' => 'injection', 'label_fr' => 'Injection', 'label_en' => 'Injection', 'display_order' => 1],
            ['category' => 'care_type', 'code' => 'perfusion', 'label_fr' => 'Perfusion', 'label_en' => 'Infusion', 'display_order' => 2],
            ['category' => 'care_type', 'code' => 'pansement', 'label_fr' => 'Pansement', 'label_en' => 'Dressing', 'display_order' => 3],
            ['category' => 'care_type', 'code' => 'suture', 'label_fr' => 'Suture', 'label_en' => 'Suture', 'display_order' => 4],
            ['category' => 'care_type', 'code' => 'kine', 'label_fr' => 'Kinesitherapie', 'label_en' => 'Physiotherapy', 'display_order' => 5],
            ['category' => 'care_type', 'code' => 'dialyse', 'label_fr' => 'Dialyse', 'label_en' => 'Dialysis', 'display_order' => 6],
            ['category' => 'care_type', 'code' => 'autre', 'label_fr' => 'Autre', 'label_en' => 'Other', 'display_order' => 7],

            // --- Treatment Types ---
            ['category' => 'treatment_type', 'code' => 'medication', 'label_fr' => 'Medicament', 'label_en' => 'Medication', 'display_order' => 1],
            ['category' => 'treatment_type', 'code' => 'diet', 'label_fr' => 'Regime alimentaire', 'label_en' => 'Diet', 'display_order' => 2],
            ['category' => 'treatment_type', 'code' => 'rest', 'label_fr' => 'Repos', 'label_en' => 'Rest', 'display_order' => 3],
            ['category' => 'treatment_type', 'code' => 'rehabilitation', 'label_fr' => 'Reeducation', 'label_en' => 'Rehabilitation', 'display_order' => 4],
            ['category' => 'treatment_type', 'code' => 'follow_up', 'label_fr' => 'Suivi', 'label_en' => 'Follow-up', 'display_order' => 5],
            ['category' => 'treatment_type', 'code' => 'lifestyle', 'label_fr' => 'Hygiene de vie', 'label_en' => 'Lifestyle', 'display_order' => 6],
            ['category' => 'treatment_type', 'code' => 'other', 'label_fr' => 'Autre', 'label_en' => 'Other', 'display_order' => 7],

            // --- Urgency Levels ---
            ['category' => 'urgency_level', 'code' => 'normal', 'label_fr' => 'Normal', 'label_en' => 'Normal', 'display_order' => 1],
            ['category' => 'urgency_level', 'code' => 'urgent', 'label_fr' => 'Urgent', 'label_en' => 'Urgent', 'display_order' => 2],

            // --- Insurance Providers ---
            ['category' => 'insurance_provider', 'code' => 'CNAMGS', 'label_fr' => 'CNAMGS', 'label_en' => 'CNAMGS', 'display_order' => 1],
            ['category' => 'insurance_provider', 'code' => 'ASCOMA', 'label_fr' => 'ASCOMA', 'label_en' => 'ASCOMA', 'display_order' => 2],
            ['category' => 'insurance_provider', 'code' => 'OGAR', 'label_fr' => 'OGAR', 'label_en' => 'OGAR', 'display_order' => 3],
            ['category' => 'insurance_provider', 'code' => 'AXA', 'label_fr' => 'AXA', 'label_en' => 'AXA', 'display_order' => 4],
            ['category' => 'insurance_provider', 'code' => 'NSIA', 'label_fr' => 'NSIA', 'label_en' => 'NSIA', 'display_order' => 5],
            ['category' => 'insurance_provider', 'code' => 'SUNU', 'label_fr' => 'SUNU', 'label_en' => 'SUNU', 'display_order' => 6],
            ['category' => 'insurance_provider', 'code' => 'Saham', 'label_fr' => 'Saham', 'label_en' => 'Saham', 'display_order' => 7],

            // --- Country Codes (phone) ---
            ['category' => 'country_code', 'code' => '+241', 'label_fr' => 'Gabon', 'label_en' => 'Gabon', 'metadata' => ['iso' => 'GA'], 'display_order' => 1],
            ['category' => 'country_code', 'code' => '+237', 'label_fr' => 'Cameroun', 'label_en' => 'Cameroon', 'metadata' => ['iso' => 'CM'], 'display_order' => 2],
            ['category' => 'country_code', 'code' => '+242', 'label_fr' => 'Congo', 'label_en' => 'Congo', 'metadata' => ['iso' => 'CG'], 'display_order' => 3],
            ['category' => 'country_code', 'code' => '+243', 'label_fr' => 'RD Congo', 'label_en' => 'DR Congo', 'metadata' => ['iso' => 'CD'], 'display_order' => 4],
            ['category' => 'country_code', 'code' => '+240', 'label_fr' => 'Guinee Equatoriale', 'label_en' => 'Equatorial Guinea', 'metadata' => ['iso' => 'GQ'], 'display_order' => 5],
            ['category' => 'country_code', 'code' => '+235', 'label_fr' => 'Tchad', 'label_en' => 'Chad', 'metadata' => ['iso' => 'TD'], 'display_order' => 6],
            ['category' => 'country_code', 'code' => '+236', 'label_fr' => 'Centrafrique', 'label_en' => 'Central African Republic', 'metadata' => ['iso' => 'CF'], 'display_order' => 7],
            ['category' => 'country_code', 'code' => '+221', 'label_fr' => 'Senegal', 'label_en' => 'Senegal', 'metadata' => ['iso' => 'SN'], 'display_order' => 8],
            ['category' => 'country_code', 'code' => '+225', 'label_fr' => 'Cote d\'Ivoire', 'label_en' => 'Ivory Coast', 'metadata' => ['iso' => 'CI'], 'display_order' => 9],
            ['category' => 'country_code', 'code' => '+229', 'label_fr' => 'Benin', 'label_en' => 'Benin', 'metadata' => ['iso' => 'BJ'], 'display_order' => 10],
            ['category' => 'country_code', 'code' => '+228', 'label_fr' => 'Togo', 'label_en' => 'Togo', 'metadata' => ['iso' => 'TG'], 'display_order' => 11],
            ['category' => 'country_code', 'code' => '+223', 'label_fr' => 'Mali', 'label_en' => 'Mali', 'metadata' => ['iso' => 'ML'], 'display_order' => 12],
            ['category' => 'country_code', 'code' => '+226', 'label_fr' => 'Burkina Faso', 'label_en' => 'Burkina Faso', 'metadata' => ['iso' => 'BF'], 'display_order' => 13],
            ['category' => 'country_code', 'code' => '+227', 'label_fr' => 'Niger', 'label_en' => 'Niger', 'metadata' => ['iso' => 'NE'], 'display_order' => 14],
            ['category' => 'country_code', 'code' => '+261', 'label_fr' => 'Madagascar', 'label_en' => 'Madagascar', 'metadata' => ['iso' => 'MG'], 'display_order' => 15],
            ['category' => 'country_code', 'code' => '+33', 'label_fr' => 'France', 'label_en' => 'France', 'metadata' => ['iso' => 'FR'], 'display_order' => 16],
        ];
    }
}
