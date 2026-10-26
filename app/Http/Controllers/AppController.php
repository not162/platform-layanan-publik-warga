<?php

namespace App\Http\Controllers;

use App\Enums\UserRole;
use App\Models\User;
use App\Support\Navigation;
use Illuminate\Http\Request;
use Illuminate\View\View;

class AppController extends Controller
{
    public function dashboard(Request $request): View
    {
        return $this->render($request, 'dashboard', 'app.dashboard');
    }

    public function profile(Request $request): View
    {
        return $this->render($request, 'profile', 'app.profile');
    }

    public function page(Request $request, string $page): View
    {
        if ($page === 'system') {
            return $this->render($request, 'system', 'app.system');
        }

        return $this->render($request, $page, 'app.page');
    }

    protected function render(Request $request, string $page, string $view): View
    {
        /** @var User $user */
        $user = $request->user();

        abort_unless(Navigation::allowed($user, $page), 403, 'Anda tidak memiliki akses ke halaman ini.');

        $items = Navigation::for($user);

        $extra = $page === 'system' ? $this->rbacMatrix() : [];

        return view($view, $extra + [
            'user' => $user->loadMissing('citizen'),
            'page' => $page,
            'pageDef' => Navigation::pages()[$page],
            'canCreate' => Navigation::canCreate($user, $page),
            'nav' => $items,
            'bottomNav' => Navigation::bottom($items),
        ]);
    }

    /**
     * Matriks peran × fitur, dihitung dari User::hasPermission() agar selalu sama dengan aturan nyata.
     *
     * @return array{roles: array<string, array<string, string>>, matrix: array<string, array<string, bool>>}
     */
    protected function rbacMatrix(): array
    {
        $features = [
            'Kelola pengguna & peran' => 'user.manage',
            'Lihat data warga' => 'citizen.read',
            'Kelola data warga & verifikasi pendaftar' => 'citizen.manage',
            'Verifikasi berkas surat' => 'letter.verify',
            'Sahkan surat (Ketua RT)' => 'letter.approve',
            'Tanggapi pengaduan' => 'complaint.manage',
            'Tindak laporan keamanan' => 'security.manage',
            'Lihat buku kas' => 'finance.transaction.read',
            'Catat transaksi kas' => 'finance.transaction.create',
            'Terbitkan transaksi kas' => 'finance.transaction.publish',
            'Kelola pengumuman' => 'announcement.manage',
            'Kelola agenda' => 'event.manage',
            'Kelola struktur pengurus' => 'officer.manage',
            'Kelola jadwal ronda' => 'round_schedule.manage',
            'Kelola kontak darurat' => 'emergency.manage',
            'Lihat audit log' => 'audit.read',
        ];

        $roles = [];
        $matrix = [];
        foreach (UserRole::cases() as $role) {
            $roles[$role->value] = ['label' => $role->label(), 'tier' => $role->tier()];
            $probe = (new User)->forceFill(['role' => $role]);
            $probe->setRelation('permissions', collect());
            foreach ($features as $label => $permission) {
                $matrix[$label][$role->value] = $probe->hasPermission($permission);
            }
        }

        return compact('roles', 'matrix');
    }
}
