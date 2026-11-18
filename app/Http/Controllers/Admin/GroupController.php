<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Group;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class GroupController extends Controller
{
    public function index(): View
    {
        return view('admin.groups.index', [
            'groups' => Group::query()
                ->with('directions')
                ->orderBy('name')
                ->paginate(15),
        ]);
    }

    public function create(): View
    {
        return view('admin.groups.create', [
            'group' => new Group,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        Group::query()->create($this->validatedAttributes($request));

        return redirect()
            ->route('admin.groups.index')
            ->with('status', 'Le groupe a bien été créé.');
    }

    public function edit(Group $group): View
    {
        return view('admin.groups.edit', [
            'group' => $group,
        ]);
    }

    public function update(Request $request, Group $group): RedirectResponse
    {
        $group->update($this->validatedAttributes($request, $group));

        return redirect()
            ->route('admin.groups.index')
            ->with('status', 'Le groupe a bien été mis à jour.');
    }

    /**
     * @return array{name: string, description?: string|null}
     */
    private function validatedAttributes(Request $request, ?Group $group = null): array
    {
        return $request->validate(
            [
                'name' => [
                    'required',
                    'string',
                    'max:100',
                    Rule::unique('groups', 'name')->ignore($group?->getKey()),
                ],
                'description' => ['nullable', 'string'],
            ],
            [
                'name.required' => 'Le nom du groupe est obligatoire.',
                'name.string' => 'Le nom du groupe doit être une chaîne de caractères.',
                'name.max' => 'Le nom du groupe ne peut pas dépasser 100 caractères.',
                'name.unique' => 'Un groupe portant ce nom existe déjà.',
                'description.string' => 'La description du groupe doit être une chaîne de caractères.',
            ],
        );
    }
}
