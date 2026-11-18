<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AccountRequest;
use App\Models\Direction;
use App\Models\Role;
use App\Services\AccountRequestService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class AccountRequestController extends Controller
{
    public function index(Request $request): View
    {
        $this->authorize('viewAny', AccountRequest::class);

        $query = AccountRequest::query()->with(['requestedRole', 'direction', 'reviewer']);

        if ($request->filled('status')) {
            $query->where('status', strtoupper((string) $request->input('status')));
        }

        if ($request->filled('search')) {
            $search = trim((string) $request->input('search'));
            $query->where(function ($inner) use ($search) {
                $inner->where('name', 'like', "%{$search}%")
                    ->orWhere('firstname', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%");
            });
        }

        $requests = $query->orderByDesc('created_at')->paginate(15)->appends($request->query());

        return view('admin.account-requests.index', [
            'requests' => $requests,
            'directions' => Direction::query()->orderBy('name')->get(),
            'roles' => Role::query()->orderBy('name')->get(),
        ]);
    }

    public function show(AccountRequest $accountRequest): View
    {
        $this->authorize('view', $accountRequest);

        return view('admin.account-requests.show', [
            'accountRequest' => $accountRequest->load(['requestedRole', 'direction', 'reviewer', 'createdUser']),
        ]);
    }

    public function approve(Request $request, AccountRequest $accountRequest, AccountRequestService $service): RedirectResponse
    {
        $this->authorize('approve', $accountRequest);

        $service->approve($accountRequest, $request->user());

        return redirect()->route('admin.account-requests.show', $accountRequest)->with('status', 'La demande a été approuvée.');
    }

    public function reject(Request $request, AccountRequest $accountRequest, AccountRequestService $service): RedirectResponse
    {
        $this->authorize('reject', $accountRequest);

        $service->reject(
            $accountRequest,
            $request->user(),
            (string) $request->input('decision_reason', '') !== '' ? (string) $request->input('decision_reason') : null,
        );

        return redirect()->route('admin.account-requests.show', $accountRequest)->with('status', 'La demande a été refusée.');
    }
}
