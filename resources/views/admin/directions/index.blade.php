<x-layouts.app
    title="Directions"
    eyebrow="Administration"
    description="Consultez les directions et les groupes auxquels elles sont rattachées."
>
    <x-ui.status-message />

    <div class="app-page-actions">
        <a class="app-button app-button--primary" href="{{ route('admin.directions.create') }}">Créer une direction</a>
        <a class="app-button app-button--secondary" href="{{ route('admin.groups.index') }}">Gérer les groupes</a>
    </div>

    @if ($directions->isEmpty())
        <p class="app-empty-state">Aucune direction n'est enregistrée.</p>
    @else
        <div class="app-table-scroll">
            <table class="app-table">
                <thead>
                    <tr>
                        <th scope="col">Direction</th>
                        <th scope="col">Code</th>
                        <th scope="col">Groupe</th>
                        <th scope="col">Description</th>
                        <th scope="col">Action</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($directions as $direction)
                        <tr>
                            <td>{{ $direction->name }}</td>
                            <td>{{ $direction->code ?: '-' }}</td>
                            <td>{{ $direction->group->name }}</td>
                            <td>{{ $direction->description ?: '-' }}</td>
                            <td><a href="{{ route('admin.directions.edit', $direction) }}">Modifier</a></td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        <div class="app-pagination">
            {{ $directions->links() }}
        </div>
    @endif
</x-layouts.app>
