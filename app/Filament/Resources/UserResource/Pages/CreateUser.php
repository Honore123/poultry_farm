<?php

namespace App\Filament\Resources\UserResource\Pages;

use App\Filament\Resources\UserResource;
use App\Mail\UserInvitationMail;
use App\Tenancy\TenantContext;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;

class CreateUser extends CreateRecord
{
    protected static string $resource = UserResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        // Generate a random password - user will set their own via email
        $data['password'] = Hash::make(Str::random(32));

        if (empty($data['tenant_id'])) {
            $data['tenant_id'] = app(TenantContext::class)->currentTenantId()
                ?? auth()->user()?->tenant_id;
        }

        if (empty($data['tenant_id'])) {
            throw new \Exception('A tenant must be selected before creating a user.');
        }
        
        return $data;
    }

    protected function afterCreate(): void
    {
        $user = $this->record;
        
        // Generate password reset token
        $token = Str::random(64);
        
        // Store the token in password_reset_tokens table
        DB::table('password_reset_tokens')->updateOrInsert(
            ['email' => $user->email],
            [
                'email' => $user->email,
                'token' => Hash::make($token),
                'created_at' => now(),
            ]
        );
        
        // Send invitation email
        try {
            Mail::to($user->email)->send(new UserInvitationMail(
                user: $user,
                token: $token,
                invitedBy: auth()->user()->name,
            ));
            
            Notification::make()
                ->title('Invitation sent!')
                ->body("An invitation email has been sent to {$user->email}")
                ->success()
                ->send();
        } catch (\Exception $e) {
            Notification::make()
                ->title('User created but email failed')
                ->body("Could not send invitation email: {$e->getMessage()}")
                ->warning()
                ->send();
        }
    }

    protected function getCreatedNotificationTitle(): ?string
    {
        return 'User created successfully';
    }
}
