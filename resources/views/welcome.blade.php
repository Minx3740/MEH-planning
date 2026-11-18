
<x-layouts.public title="Accueil">
    <section class="public-card" aria-labelledby="public-home-title">
        <h1 id="public-home-title">Gestion des plannings MEH</h1>
        <p>Accédez à votre espace ou soumettez une demande de création de compte.</p>
        <div class="public-actions">
            <a class="public-button" href="{{ route('login') }}">Connexion</a>
            <a class="public-secondary-link" href="{{ route('account-requests.create') }}">Demander un compte</a>
        </div>
    </section>
</x-layouts.public>
