<?php
declare(strict_types=1);

namespace App\Modules\Core\Database\Seeders;

use App\Modules\Core\Models\Permission;
use Illuminate\Database\Seeder;

class PermissionsSeeder extends Seeder
{
    public function run(): void
    {
        $perms = [
            ['slug' => 'users.view',            'scope' => 'users',         'name_fr' => 'Voir la liste / fiche des utilisateurs'],
            ['slug' => 'users.create',          'scope' => 'users',         'name_fr' => 'Créer un utilisateur'],
            ['slug' => 'users.edit',            'scope' => 'users',         'name_fr' => "Modifier les infos d'un utilisateur"],
            ['slug' => 'users.delete',          'scope' => 'users',         'name_fr' => 'Supprimer (soft) / restaurer un utilisateur'],
            ['slug' => 'users.suspend',         'scope' => 'users',         'name_fr' => 'Suspendre / réactiver'],
            ['slug' => 'users.reset_password',  'scope' => 'users',         'name_fr' => 'Réinitialiser le mot de passe'],
            ['slug' => 'users.validate_pro',    'scope' => 'users',         'name_fr' => 'Valider / rejeter un compte pro'],
            ['slug' => 'users.impersonate',     'scope' => 'users',         'name_fr' => 'Se connecter "comme" un utilisateur'],
            ['slug' => 'users.sessions',        'scope' => 'users',         'name_fr' => 'Voir et révoquer les sessions actives'],
            ['slug' => 'users.bulk',            'scope' => 'users',         'name_fr' => 'Actions en lot sur plusieurs utilisateurs'],
            ['slug' => 'roles.view',            'scope' => 'roles',         'name_fr' => 'Voir les rôles'],
            ['slug' => 'roles.create',          'scope' => 'roles',         'name_fr' => 'Créer un rôle'],
            ['slug' => 'roles.edit',            'scope' => 'roles',         'name_fr' => "Modifier nom/description d'un rôle"],
            ['slug' => 'roles.delete',          'scope' => 'roles',         'name_fr' => 'Supprimer un rôle'],
            ['slug' => 'roles.assign',          'scope' => 'roles',         'name_fr' => 'Assigner / retirer des rôles à un user'],
            ['slug' => 'permissions.view',      'scope' => 'permissions',   'name_fr' => 'Voir le catalogue des permissions'],
            ['slug' => 'permissions.assign',    'scope' => 'permissions',   'name_fr' => 'Modifier la matrice rôle ↔ permissions'],
            ['slug' => 'structures.view',       'scope' => 'structures',    'name_fr' => "Voir les structures de l'annuaire"],
            ['slug' => 'structures.edit',       'scope' => 'structures',    'name_fr' => 'Éditer une structure'],
            ['slug' => 'structures.validate',   'scope' => 'structures',    'name_fr' => 'Valider une revendication (claim) de structure'],
            ['slug' => 'structures.delete',     'scope' => 'structures',    'name_fr' => 'Supprimer une structure'],
            ['slug' => 'pro_categories.view',   'scope' => 'pro_categories','name_fr' => 'Voir les catégories de professionnels'],
            ['slug' => 'pro_categories.manage', 'scope' => 'pro_categories','name_fr' => 'CRUD complet sur les catégories pro'],
            ['slug' => 'claims.review',         'scope' => 'claims',        'name_fr' => 'Examiner et trancher les claims de structure'],
            ['slug' => 'consultations.view',    'scope' => 'consultations', 'name_fr' => 'Voir les consultations (lecture globale)'],
            ['slug' => 'consultations.manage',  'scope' => 'consultations', 'name_fr' => 'Gérer ses propres consultations (médecin)'],
            ['slug' => 'prescriptions.create',  'scope' => 'prescriptions', 'name_fr' => 'Créer une ordonnance'],
            ['slug' => 'prescriptions.view',    'scope' => 'prescriptions', 'name_fr' => 'Voir les ordonnances'],
            ['slug' => 'appointments.manage',   'scope' => 'appointments',  'name_fr' => 'Gérer les rendez-vous (secrétariat)'],
            ['slug' => 'appointments.book.self','scope' => 'appointments',  'name_fr' => 'Prendre rendez-vous pour soi (patient)'],
            ['slug' => 'payments.view',         'scope' => 'payments',      'name_fr' => 'Voir les paiements'],
            ['slug' => 'payments.refund',       'scope' => 'payments',      'name_fr' => 'Émettre un remboursement'],
            ['slug' => 'invoices.manage',       'scope' => 'invoices',      'name_fr' => 'Créer / valider des factures (compta)'],
            ['slug' => 'stats.view',            'scope' => 'stats',         'name_fr' => 'Accéder aux tableaux de bord statistiques'],
            ['slug' => 'stats.view.financial',  'scope' => 'stats',         'name_fr' => 'Accéder aux KPI financiers (compta only)'],
            ['slug' => 'exports.users',         'scope' => 'exports',       'name_fr' => 'Exporter la liste users en CSV'],
            ['slug' => 'exports.structures',    'scope' => 'exports',       'name_fr' => 'Exporter les structures'],
            ['slug' => 'exports.financial',     'scope' => 'exports',       'name_fr' => 'Exporter les données financières'],
            ['slug' => 'exports.stats',         'scope' => 'exports',       'name_fr' => 'Exporter les rapports stats'],
            ['slug' => 'self.profile',          'scope' => 'self',          'name_fr' => 'Gérer son propre profil (tous comptes)'],
            ['slug' => 'self.carnet_vaccination','scope' => 'self',         'name_fr' => 'Voir/imprimer son carnet de vaccination'],
        ];

        foreach ($perms as $i => $p) {
            Permission::updateOrCreate(
                ['slug' => $p['slug']],
                array_merge($p, ['display_order' => $i + 1, 'is_active' => true])
            );
        }
    }
}
