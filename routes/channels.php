<?php

use Illuminate\Support\Facades\Broadcast;

Broadcast::channel('App.Models.User.{id}', function ($user, $id) {
    return (int) $user->id === (int) $id;
});

Broadcast::channel('users.{id}', function ($user, $id) {
    return (int) $user->id === (int) $id;
});

Broadcast::channel('warga.{id}', function ($user, $id) {
    return (int) $user->id === (int) $id;
});

Broadcast::channel('admin-channel', function ($user) {
    return $user->isSuperadmin()
        || $user->isAdmin()
        || $user->isSekretaris()
        || $user->isKetuaRt()
        || $user->hasPermission('citizen.manage')
        || $user->hasPermission('citizen.read');
});

Broadcast::channel('citizens.{citizenId}', function ($user, $citizenId) {
    if ($user->isSuperadmin() || $user->hasPermission('citizen.read') || $user->hasPermission('citizen.manage')) {
        return true;
    }

    return $user->citizen && (int) $user->citizen->id === (int) $citizenId;
});
