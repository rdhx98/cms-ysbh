<?php

namespace App\Policies;

use App\Models\User;

/**
 * Akses profil pengguna (CMS).
 *   admin          : semua profil (lihat, ubah, buat, hapus, kelola peran/status)
 *   editor, writer : hanya profilnya sendiri (lihat dan ubah); tidak bisa membuat/menghapus/mengelola peran
 * Laravel menemukan kebijakan ini otomatis untuk App\Models\User (App\Policies\UserPolicy).
 */
class UserPolicy
{
    public function viewAny(User $actor): bool
    {
        return $actor->hasRole('admin');
    }

    public function view(User $actor, User $target): bool
    {
        return $actor->hasRole('admin')
            || ($actor->hasAnyRole(['editor', 'writer']) && $actor->is($target));
    }

    public function create(User $actor): bool
    {
        return $actor->hasRole('admin');
    }

    public function update(User $actor, User $target): bool
    {
        return $this->view($actor, $target);
    }

    public function delete(User $actor, User $target): bool
    {
        return $actor->hasRole('admin') && ! $actor->is($target);
    }

    /** kolom sensitif (email, jabatan, status aktif, peran): hanya admin, juga untuk profil sendiri */
    public function manage(User $actor): bool
    {
        return $actor->hasRole('admin');
    }
}