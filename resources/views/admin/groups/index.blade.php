<x-layouts.app
    title="Groupes"
    eyebrow="Administration"
    description="Consultez les groupes et les directions qui leur sont rattachées."
>
    <x-ui.status-message />

    <div class="app-page-actions">
        <a class="app-button app-button--primary" href="{{ route('admin.groups.create') }}">Créer un groupe</a>
        <a class="app-button app-button--secondary" href="{{ route('admin.directions.index') }}">Gérer les directions</a>
    </div>

    @if ($groups->isEmpty())
        <p class="app-empty-state">Aucun groupe n'est enregistré.</p>
    @else
        <div class="app-table-scroll">
            <table class="app-table">
                <thead>
                    <tr>
                        <th scope="col">Groupe</th>
                        <th scope="col">Description</th>
                        <th scope="col">Directions rattachées</th>
                        <th scope="col">Action</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($groups as $group)
                        <tr>
                            <td>{{ $group->name }}</td>
                            <td>{{ $group->description ?: '-' }}</td>
                            <td>
                                @if ($group->directions->isEmpty())
                                    Aucune direction
                                @else
                                    <ul class="app-table-list">
                                        @foreach ($group->directions as $direction)
                                            <li><a href="{{ route('admin.directions.edit', $direction) }}">{{ $direction->name }}</a></li>
                                        @endforeach
                                    </ul>
                                @endif
                            </td>
                            <td><a href="{{ route('admin.groups.edit', $group) }}">Modifier</a></td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        <div class="app-pagination">
            {{ $groups->links() }}
        </div>
    @endif
</x-layouts.app>
