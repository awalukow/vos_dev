<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DocumentVerification extends Model
{
    // Your existing table name (matches the migration exactly)
    protected $table = 'DocumentVerification';

    // Your table uses increments('id') with no timestamps columns,
    // so disable Laravel's automatic created_at / updated_at handling.
    public $timestamps = false;

    protected $fillable = [
        'documentName',
        'documentSignature',
        'signatureBy',
        'signatureId',
        'hash_code',
        'documentStatus',
        'signatureStatus',
        'document_createdDate',
        'document_validThru',
        'signature_signedDate',
        'signature_validThru',
        'isForcedInvalidity',
        'Rowstatus',
        'CreatedBy',
        'CreatedDate',
        'ModifiedBy',
        'ModifiedDate',
    ];

    protected $casts = [
        'documentStatus'      => 'boolean',
        'signatureStatus'     => 'boolean',
        'isForcedInvalidity'  => 'boolean',
        'documentSignature'   => 'datetime',
        'document_createdDate'=> 'datetime',
        'document_validThru'  => 'datetime',
        'signature_signedDate'=> 'datetime',
        'signature_validThru' => 'datetime',
        'CreatedDate'         => 'datetime',
        'ModifiedDate'        => 'datetime',
    ];

    // ── Helpers ───────────────────────────────────────────────────────────────

    /**
     * Derive the validation status the view expects.
     * Rules:
     *  - isForcedInvalidity = true  → NOT VALID
     *  - signatureStatus = false    → NOT VALID
     *  - signature_validThru < now → NOT VALID (expired)
     *  - otherwise                  → VALID
     */
    public function getValidationStatusAttribute(): string
    {
        if ($this->isForcedInvalidity) return 'NOT VALID';
        if (!$this->signatureStatus)   return 'NOT VALID';
        if ($this->signature_validThru && $this->signature_validThru->isPast()) return 'NOT VALID';
        return 'VALID';
    }

    public function getInvalidReasonAttribute(): string
    {
        if ($this->isForcedInvalidity) return 'Validity has been revoked by administrator.';
        if (!$this->signatureStatus)   return 'Signature is not valid.';
        if ($this->signature_validThru && $this->signature_validThru->isPast()) {
            return 'Document validity has expired on ' . $this->signature_validThru->format('d M Y') . '.';
        }
        return '';
    }
}
