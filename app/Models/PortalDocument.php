<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class PortalDocument extends Model
{
    use SoftDeletes;

    protected $table = 'portal_documents';

    protected $fillable = [
        'title', 'file_path', 'original_filename',
        'hash_code', 'uploaded_by', 'status', 'notes',
    ];

    protected static function booted(): void
    {
        static::creating(function (self $doc) {
            if (empty($doc->hash_code)) {
                $doc->hash_code = Str::random(32);
            }
        });
    }

    public function uploader()
    {
        return $this->belongsTo(PortalUser::class, 'uploaded_by');
    }

    public function signatures()
    {
        return $this->hasMany(DocumentSignature::class, 'document_id')->orderBy('sort_order');
    }

    public function pendingSignatures()
    {
        return $this->signatures()->where('status', 'pending');
    }

    public function isPendingForUser(PortalUser $user): bool
    {
        return $this->signatures()
            ->where('signer_id', $user->id)
            ->where('status', 'pending')
            ->exists();
    }

    public function getVerifyUrlAttribute(): string
    {
        return url('/verify/' . $this->hash_code);
    }
}
