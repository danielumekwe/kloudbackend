<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Admin;
use BaconQrCode\Renderer\Image\SvgImageBackEnd;
use BaconQrCode\Renderer\ImageRenderer;
use BaconQrCode\Renderer\RendererStyle\RendererStyle;
use BaconQrCode\Writer;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\View\View;
use PragmaRX\Google2FA\Google2FA;

class AdminTwoFactorController extends Controller
{
    public function __construct(private Google2FA $google2fa) {}

    public function show(): View
    {
        return view('admin.security.index', [
            'admin' => $this->currentAdmin(),
            'freshRecoveryCodes' => session()->pull('admin_2fa_just_enabled_codes'),
        ]);
    }

    public function setup(): View
    {
        $admin = $this->currentAdmin();

        // Reuse a secret already generated earlier in this setup session, so a page
        // refresh doesn't invalidate a code the admin already scanned.
        $secret = session('admin_2fa_setup_secret') ?? $this->google2fa->generateSecretKey();
        session(['admin_2fa_setup_secret' => $secret]);

        $qrUri = $this->google2fa->getQRCodeUrl('Kloud101 Admin', $admin->email, $secret);

        return view('admin.security.setup', [
            'secret' => $secret,
            'qrSvg' => $this->renderQrSvg($qrUri),
        ]);
    }

    public function confirm(Request $request): RedirectResponse
    {
        $request->validate(['code' => ['required', 'string']]);

        $secret = session('admin_2fa_setup_secret');

        if (! $secret) {
            return redirect()->route('admin.security.two-factor.setup')
                ->withErrors(['code' => 'Your setup session expired — please start again.']);
        }

        if (! $this->google2fa->verifyKey($secret, str_replace(' ', '', $request->code))) {
            return back()->withErrors(['code' => 'That code is invalid. Check your authenticator app and try again.']);
        }

        $recoveryCodes = $this->generateRecoveryCodes();

        $this->currentAdmin()->update([
            'two_factor_secret' => $secret,
            'two_factor_recovery_codes' => $recoveryCodes,
            'two_factor_confirmed_at' => now(),
        ]);

        session()->forget('admin_2fa_setup_secret');
        session(['admin_2fa_just_enabled_codes' => $recoveryCodes]);

        return redirect()->route('admin.security')->with('success', 'Two-factor authentication is now enabled.');
    }

    public function disable(Request $request): RedirectResponse
    {
        $admin = $this->currentAdmin();

        $request->validate(['password' => ['required', 'string']]);

        if (! $admin->checkPassword($request->password)) {
            return back()->withErrors(['password' => 'Incorrect password.']);
        }

        $admin->update([
            'two_factor_secret' => null,
            'two_factor_recovery_codes' => null,
            'two_factor_confirmed_at' => null,
        ]);

        return redirect()->route('admin.security')->with('success', 'Two-factor authentication has been disabled.');
    }

    public function regenerateRecoveryCodes(Request $request): RedirectResponse
    {
        $admin = $this->currentAdmin();

        $request->validate(['password' => ['required', 'string']]);

        if (! $admin->checkPassword($request->password)) {
            return back()->withErrors(['password' => 'Incorrect password.']);
        }

        $codes = $this->generateRecoveryCodes();
        $admin->update(['two_factor_recovery_codes' => $codes]);
        session(['admin_2fa_just_enabled_codes' => $codes]);

        return redirect()->route('admin.security')->with('success', 'New recovery codes generated — your old codes no longer work.');
    }

    private function currentAdmin(): Admin
    {
        return Admin::findOrFail(session('adminId'));
    }

    private function generateRecoveryCodes(): array
    {
        return collect(range(1, 8))
            ->map(fn () => Str::upper(Str::random(4) . '-' . Str::random(4)))
            ->all();
    }

    private function renderQrSvg(string $uri): string
    {
        $renderer = new ImageRenderer(new RendererStyle(200), new SvgImageBackEnd());

        return (new Writer($renderer))->writeString($uri);
    }
}
