<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Direction;
use App\Models\Group;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class DirectionController extends Controller
{
    public function index(): View
    {
        $this->authorize('viewAny', Direction::class);

        return view('admin.directions.index', [
            'directions' => Direction::query()
                ->with('group')
                ->orderBy('name')
                ->paginate(15),
        ]);
    }

    public function create(): View
    {
        $this->authorize('create', Direction::class);

        return view('admin.directions.create', [
            'direction' => new Direction,
            'groups' => Group::query()->orderBy('name')->get(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $this->authorize('create', Direction::class);

        Direction::query()->create($this->validatedAttributes($request));

        return redirect()
            ->route('admin.directions.index')
            ->with('status', 'La direction a bien été créée.');
    }

    public function edit(Direction $direction): View
    {
        $this->authorize('update', $direction);

        return view('admin.directions.edit', [
            'direction' => $direction->load('group'),
            'groups' => Group::query()->orderBy('name')->get(),
        ]);
    }

    public function update(Request $request, Direction $direction): RedirectResponse
    {
        $this->authorize('update', $direction);

        $direction->update($this->validatedAttributes($request, $direction));

        return redirect()
            ->route('admin.directions.index')
            ->with('status', 'La direction a bien été mise à jour.');
    }

    /**
     * @return array{group_id: int, name: string, code?: string|null, description?: string|null}
     */
    private function validatedAttributes(Request $request, ?Direction $direction = null): array
    {
        $submittedGroupId = $request->input('group_id');
        $groupId = is_scalar($submittedGroupId)
            ? filter_var($submittedGroupId, FILTER_VALIDATE_INT)
            : false;

        $attributes = $request->validate(
            [
                'group_id' => ['required', 'integer', 'exists:groups,id'],
                'name' => [
                    'required',
                    'string',
                    'max:150',
                    Rule::unique('directions', 'name')
                        ->where('group_id', $groupId === false ? 0 : $groupId)
                        ->ignore($direction?->getKey()),
                ],
                'code' => [
                    'nullable',
                    'string',
                    'max:50',
                    Rule::unique('directions', 'code')->ignore($direction?->getKey()),
                ],
                'description' => ['nullable', 'string'],
            ],
            [
                'group_id.required' => 'Un groupe est obligatoire pour cette direction.',
                'group_id.integer' => 'Le groupe sélectionné est invalide.',
                'group_id.exists' => "Le groupe sélectionné n'existe pas.",
                'name.required' => 'Le nom de la direction est obligatoire.',
                'name.string' => 'Le nom de la direction doit être une chaîne de caractères.',
                'name.max' => 'Le nom de la direction ne peut pas dépasser 150 caractères.',
                'name.unique' => 'Une direction de ce nom existe déjà dans ce groupe.',
                'code.string' => 'Le code de la direction doit être une chaîne de caractères.',
                'code.max' => 'Le code de la direction ne peut pas dépasser 50 caractères.',
                'code.unique' => 'Ce code de direction est déjà utilisé.',
                'description.string' => 'La description de la direction doit être une chaîne de caractères.',
            ],
        );

        $attributes['group_id'] = (int) $attributes['group_id'];

        return $attributes;
    }
}
