<?php

namespace App\Services;

use App\Enums\UserRole;
use App\Models\User;
use App\Notifications\AppNotification;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Notification;

class NotifyService
{
    /**
     * Kirim notifikasi ke satu atau banyak pengguna (disimpan di tabel notifications).
     *
     * @param  User|int|Collection<int, User>|array<int, User>|null  $users
     */
    public function to(User|int|Collection|array|null $users, string $title, string $body, ?string $url = null, string $level = 'info'): void
    {
        if ($users === null) {
            return;
        }

        if (is_int($users)) {
            $users = User::query()->find($users);
        }

        $recipients = collect($users instanceof User ? [$users] : $users)->filter()->unique('id')->values();

        if ($recipients->isEmpty()) {
            return;
        }

        Notification::send($recipients, new AppNotification($title, $body, $url, $level));
    }

    /**
     * @param  array<int, UserRole>  $roles
     */
    public function toRoles(array $roles, string $title, string $body, ?string $url = null, string $level = 'info'): void
    {
        $users = User::query()
            ->whereIn('role', array_map(fn (UserRole $role) => $role->value, $roles))
            ->where('is_active', true)
            ->get();

        $this->to($users, $title, $body, $url, $level);
    }

    /**
     * Seluruh pengurus (semua peran non-warga).
     */
    public function toStaff(string $title, string $body, ?string $url = null, string $level = 'info'): void
    {
        $this->toRoles(array_values(array_filter(UserRole::cases(), fn (UserRole $role) => $role->isStaff())), $title, $body, $url, $level);
    }

    public function toEveryone(string $title, string $body, ?string $url = null, string $level = 'info'): void
    {
        $this->to(User::query()->where('is_active', true)->get(), $title, $body, $url, $level);
    }
}
