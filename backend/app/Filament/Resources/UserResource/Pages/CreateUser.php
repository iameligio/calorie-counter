<?php

namespace App\Filament\Resources\UserResource\Pages;

use App\Filament\Resources\UserResource;
use App\Models\User;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Database\Eloquent\Model;

class CreateUser extends CreateRecord
{
    protected static string $resource = UserResource::class;

    // is_admin / is_banned are not in $fillable — forceFill in this trusted context.
    protected function handleRecordCreation(array $data): Model
    {
        $user = new User();
        $user->fill(array_diff_key($data, array_flip(['is_admin', 'is_banned'])));
        $user->forceFill([
            'is_admin'  => (bool) ($data['is_admin']  ?? false),
            'is_banned' => (bool) ($data['is_banned'] ?? false),
        ]);
        $user->save();
        return $user;
    }
}
