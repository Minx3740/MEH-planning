<x-layouts.app
    title="Tableau de bord"
    eyebrow="Espace d'administration"
    :description="'Bienvenue, '.$user->firstname.'. Retrouvez ici les accès aux modules administratifs disponibles.'"
>
    <section aria-labelledby="modules-title">
        <h2 class="app-section-heading" id="modules-title">Modules</h2>
        <div class="app-module-grid">
            <a class="app-module-card" href="{{ route('admin.users.index') }}">
                <h2>Gestion des utilisateurs</h2>
                <p>Consulter et gérer les comptes utilisateurs.</p>
                <span class="app-module-action">Accéder au module</span>
            </a>

            <a class="app-module-card" href="{{ route('admin.account-requests.index') }}">
                <h2>Demandes de comptes</h2>
                <p>Consulter les demandes de création de compte.</p>
                <span class="app-module-action">Accéder au module</span>
            </a>

            <article class="app-module-card">
                <h2>Groupes et directions</h2>
                <p>Consulter et gérer les groupes ainsi que leurs directions.</p>
                <div class="app-module-actions">
                    <a href="{{ route('admin.groups.index') }}">Groupes</a>
                    <a href="{{ route('admin.directions.index') }}">Directions</a>
                </div>
            </article>

            <article class="app-module-card" aria-label="Journaux d'activité, page à venir">
                <h2>Journaux d'activité</h2>
                <p>Ce module n'est pas encore disponible.</p>
                <span class="app-module-status">À venir</span>
            </article>
        </div>
    </section>
</x-layouts.app>
