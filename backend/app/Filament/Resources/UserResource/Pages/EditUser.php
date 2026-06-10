<?php

namespace App\Filament\Resources\UserResource\Pages;

use App\Filament\Resources\UserResource;
use App\Models\User;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Database\Eloquent\Model;

class EditUser extends EditRecord
{
    protected static string $resource = UserResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }

    // is_admin / is_banned are not in $fillable (privilege-escalation guard).
    // Filament is a trusted admin context, so we forceFill them explicitly.
    protected function handleRecordUpdate(Model $record, array $data): Model
    {
        $record->fill(array_diff_key($data, array_flip(['is_admin', 'is_banned'])));
        $record->forceFill([
            'is_admin'  => (bool) ($data['is_admin']  ?? $record->is_admin),
            'is_banned' => (bool) ($data['is_banned'] ?? $record->is_banned),
        ]);
        $record->save();
        return $record;
    }
}
