<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Admin;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\View\View;

class SettingsController extends Controller
{
    public function show(Request $request): View
    {
        $admin = $this->resolveAuthenticatedAdmin($request);

        return view('admin.settings.index', [
            'admin' => $admin,
        ]);
    }

    public function updatePassword(Request $request): RedirectResponse
    {
        $admin = $this->resolveAuthenticatedAdmin($request);

        $validated = $request->validate([
            'current_password' => ['required', 'string'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
        ]);

        if (! Hash::check($validated['current_password'], $admin->password)) {
            return back()
                ->withErrors(['current_password' => 'A senha atual informada está incorreta.'])
                ->withInput();
        }

        $admin->password = Hash::make($validated['password']);
        $admin->save();

        return back()->with('status', 'Senha atualizada com sucesso.');
    }

    public function updateApiKey(Request $request): RedirectResponse
    {
        $admin = $this->resolveAuthenticatedAdmin($request);

        $validated = $request->validate([
            'api_key' => ['required', 'string', 'max:255'],
        ]);

        $admin->api_key = $validated['api_key'];
        $admin->save();

        $request->session()->put('admin_user.api_key', $admin->api_key);

        return back()->with('status', 'Chave de API salva com sucesso.');
    }

    private function resolveAuthenticatedAdmin(Request $request): Admin
    {
        $adminId = (int) ($request->session()->get('admin_user.id') ?? 0);

        /** @var Admin|null $admin */
        $admin = Admin::query()->find($adminId);

        abort_if(! $admin, 403);

        return $admin;
    }
}
