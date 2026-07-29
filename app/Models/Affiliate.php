<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Affiliate extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'affiliate_code',
        'upline_id',
        'group_name',
        'affiliate_type',
        'name',
        'name_normalized',
        'email',
        'phone',
        'raw_upline_text',
        'raw_l1',
        'raw_l2',
        'raw_l3',
        'hierarchy_import_status',
        'hierarchy_import_remark',
        'raw_import_data',
        'status',
        'password_reset_at',
        'password_reset_by',
    ];

    protected $casts = [
        'password_reset_at' => 'datetime',
        'raw_import_data' => 'array',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function upline(): BelongsTo
    {
        return $this->belongsTo(self::class, 'upline_id');
    }

    public function passwordResetBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'password_reset_by');
    }

    public function directDownlines(): HasMany
    {
        return $this->hasMany(self::class, 'upline_id');
    }

    public function tiktokAccounts(): HasMany
    {
        return $this->hasMany(TiktokAccount::class);
    }

    public function tiktokAccountRequests(): HasMany
    {
        return $this->hasMany(AffiliateTiktokAccountRequest::class);
    }

    public function tiktokOrders(): HasMany
    {
        return $this->hasMany(TiktokOrder::class);
    }

    public function receivedCommissionEntries(): HasMany
    {
        return $this->hasMany(CommissionEntry::class, 'receiver_affiliate_id');
    }

    public function sourcedCommissionEntries(): HasMany
    {
        return $this->hasMany(CommissionEntry::class, 'source_affiliate_id');
    }

    public function referral(): HasOne
    {
        return $this->hasOne(AffiliateReferral::class);
    }

    public function referralUrl(): ?string
    {
        return $this->referral?->url();
    }

    public function canManageAffiliateTypes(): bool
    {
        $code = strtoupper((string) $this->affiliate_code);

        return $code !== '' && in_array($code, config('affiliate_management.type_manager_codes', []), true);
    }

    public function hasDescendant(self $affiliate): bool
    {
        if (! $affiliate->exists || $affiliate->id === $this->id) {
            return false;
        }

        $current = $affiliate;
        $guard = 0;

        while ($current->upline_id && $guard < 100) {
            if ((int) $current->upline_id === (int) $this->id) {
                return true;
            }

            $current = self::query()->select(['id', 'upline_id'])->find($current->upline_id);

            if (! $current) {
                return false;
            }

            $guard++;
        }

        return false;
    }
}
