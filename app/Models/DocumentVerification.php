<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DocumentVerification extends Model
{
    use HasFactory;

    protected $table = 'DocumentVerification'; 

    protected $fillable = [
        'documentName',
        'documentSignature',
        'signatureBy',
        'signatureId',
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

    // You may also want to define timestamps if you have 'created_at' and 'updated_at' columns in your table
    // If you're using 'CreatedDate' and 'ModifiedDate', you might want to disable timestamps
    public $timestamps = false;
}
