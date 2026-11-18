<?php

return [
    'roles' => [
        'ADMIN' => [
            'label' => 'Administration',
            'brand' => 'Administration',
            'sidebarLabel' => 'Espace ADMIN',
            'items' => [
                [
                    'label' => 'Tableau de bord',
                    'route' => 'admin.dashboard',
                ],
                [
                    'label' => 'Gestion des utilisateurs',
                    'route' => 'admin.users.index',
                    'active' => 'admin.users.*',
                ],
                [
                    'label' => 'Demandes de comptes',
                    'route' => 'admin.account-requests.index',
                    'active' => 'admin.account-requests.*',
                ],
                [
                    'label' => 'Groupes',
                    'route' => 'admin.groups.index',
                    'active' => 'admin.groups.*',
                ],
                [
                    'label' => 'Directions',
                    'route' => 'admin.directions.index',
                    'active' => 'admin.directions.*',
                ],
                [
                    'label' => "Journaux d'activité",
                    'status' => 'À venir',
                ],
            ],
        ],
        'SG' => [
            'label' => 'Secrétariat général',
            'brand' => 'Secrétariat général',
            'sidebarLabel' => 'Espace SG',
            'items' => [
                [
                    'label' => 'Accueil',
                    'route' => 'sg.dashboard',
                ],
            ],
        ],
        'DIRECTEUR' => [
            'label' => 'Direction',
            'brand' => 'Direction',
            'sidebarLabel' => 'Espace DIRECTEUR',
            'items' => [
                [
                    'label' => 'Accueil',
                    'route' => 'director.dashboard',
                ],
            ],
        ],
    ],
];
