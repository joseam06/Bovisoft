<?php

namespace App\Models;

use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Fortify\TwoFactorAuthenticatable;
use Laravel\Jetstream\HasProfilePhoto;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable implements MustVerifyEmail
{
    protected $attributes=['legacy_email_access'=>false,'password_login_enabled'=>true,'credentials_version'=>1];
    private bool $confirmedEmailChange = false;

    public static function normalizeEmail(string $email): string { return mb_strtolower(trim($email)); }
    public function hasOwnPassword(): bool { return $this->password_login_enabled && $this->password_set_at !== null; }
    public function canAccessRecords(): bool { return $this->hasVerifiedEmail() || $this->legacy_email_access; }
    public function accountStatus(): array
    {
        return ['email'=>$this->email,'email_verified'=>$this->hasVerifiedEmail(),'google_linked'=>$this->google_id!==null,
            'has_user_password'=>$this->hasOwnPassword(),'legacy_email_access'=>(bool)$this->legacy_email_access];
    }
    public function applyConfirmedEmail(string $email): void
    {
        $this->confirmedEmailChange=true;
        try{$this->forceFill(['email'=>self::normalizeEmail($email),'email_verified_at'=>now()])->save();}
        finally{$this->confirmedEmailChange=false;}
    }
    public function sendEmailVerificationNotification(): void { $this->notify(new \App\Notifications\AccountVerification); }
    public function sendPasswordResetNotification($token): void { $this->notify(new \App\Notifications\AccountPasswordReset($token)); }

    public function save(array $options = [])
    {
        if($this->exists && $this->isDirty('email') && !$this->confirmedEmailChange){
            throw \Illuminate\Validation\ValidationException::withMessages(['email'=>'Cambia tu correo desde Seguridad y acceso a la cuenta; se confirmará antes de sustituir el actual.']);
        }
        if(!$this->exists || $this->isDirty('email'))$this->email_identity=self::normalizeEmail($this->email);
        if(!$this->exists && !$this->google_id && !$this->password_set_at)$this->password_set_at=now();
        if (! $this->exists || ! $this->isDirty('name')) return parent::save($options);
        return app(\App\Services\WorkspaceJournal::class)->transaction((int) $this->id, function () use ($options) {
            $saved = parent::save($options);
            if ($saved) {
                $profile = WorkspaceProfile::firstOrNew(['user_id'=>$this->id]);
                $profile->name = $this->name;$profile->save();
            }
            return $saved;
        });
    }
    use HasApiTokens;

    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory;
    use HasProfilePhoto;
    use Notifiable;
    use TwoFactorAuthenticatable;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'telefono',
        'foto_perfil',
        'google_id',           
        'profile_photo_path',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'password',
        'remember_token',
        'two_factor_recovery_codes',
        'two_factor_secret',
        'pending_email_token_hash',
        'pending_email',
        'pending_email_expires_at',
    ];

    /**
     * The accessors to append to the model's array form.
     *
     * @var array<int, string>
     */
    protected $appends = [
        'profile_photo_url',
        'foto_url',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'legacy_email_access'=>'boolean',
            'password_login_enabled'=>'boolean',
            'password_set_at'=>'datetime',
            'pending_email_expires_at'=>'datetime',
            'credentials_version'=>'integer',
        ];
    }

/**
 * Retorna la URL de la foto de perfil, sea local o de Google.
 */
public function getFotoUrlAttribute(): ?string
{
    if (!$this->profile_photo_path) {
        return null;
    }

    // Si es URL externa (avatar de Google), devolverla tal cual
    if (str_starts_with($this->profile_photo_path, 'http')) {
        return $this->profile_photo_path;
    }

    // Si es archivo local, construir URL de storage
    return asset('storage/' . $this->profile_photo_path);
}

}
