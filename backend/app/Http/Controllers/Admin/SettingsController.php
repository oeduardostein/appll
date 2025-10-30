<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Admin;
use App\Models\AdminSetting;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\View\View;

class SettingsController extends Controller
{
    public function show(Request $request): View
    {
        $admin = $this->resolveAuthenticatedAdmin($request);

        $apiKeySetting = $this->getSetting($admin->id, 'api_key');

        return view('admin.settings.index', [
            'admin' => $admin,
            'apiKey' => $apiKeySetting?->value ?? '',
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

        $request->session()->put('admin_user.password_changed_at', now()->toIso8601String());

        return back()->with('status', 'Senha atualizada com sucesso.');
    }

    public function updateApiKey(Request $request): RedirectResponse
    {
        $admin = $this->resolveAuthenticatedAdmin($request);

        $validated = $request->validate([
            'api_key' => ['required', 'string', 'max:255'],
        ]);

        $setting = $this->getSetting($admin->id, 'api_key');

        if ($setting) {
            $setting->value = $validated['api_key'];
            $setting->save();
        } else {
            $setting = AdminSetting::query()->create([
                'admin_id' => $admin->id,
                'key' => 'api_key',
                'value' => $validated['api_key'],
            ]);
        }

        $request->session()->put('admin_user.api_key', $setting->value);

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

    private function getSetting(int $adminId, string $key): ?AdminSetting
    {
        return AdminSetting::query()
            ->where('admin_id', $adminId)
            ->where('key', $key)
            ->first();
    }
}
