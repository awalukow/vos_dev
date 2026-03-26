<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Backfill DocumentVerification rows for any portal_documents
     * that were already signed BEFORE this integration was added.
     *
     * Safe to run multiple times — uses INSERT IGNORE / upsert logic.
     */
    public function up(): void
    {
        $signedDocs = DB::table('portal_documents')
            ->whereIn('status', ['completed', 'partially_signed'])
            ->whereNull('deleted_at')
            ->get();

        foreach ($signedDocs as $doc) {
            // Skip if already has a verification row
            $exists = DB::table('DocumentVerification')
                ->where('hash_code', $doc->hash_code)
                ->exists();

            if ($exists) continue;

            // Get all signed signers
            $signers = DB::table('document_signatures')
                ->join('portal_users', 'portal_users.id', '=', 'document_signatures.signer_id')
                ->where('document_signatures.document_id', $doc->id)
                ->where('document_signatures.status', 'signed')
                ->pluck('portal_users.name')
                ->implode(', ');

            $isCompleted   = $doc->status === 'completed';
            $firstSignedAt = DB::table('document_signatures')
                ->where('document_id', $doc->id)
                ->where('status', 'signed')
                ->min('signed_at') ?? now();

            $firstSigId = DB::table('document_signatures')
                ->where('document_id', $doc->id)
                ->where('status', 'signed')
                ->value('id') ?? 0;

            $validThru = \Carbon\Carbon::parse($firstSignedAt)->addYears(5);

            DB::table('DocumentVerification')->insert([
                'documentName'         => $doc->title,
                'documentSignature'    => $doc->created_at,
                'signatureBy'          => $signers ?: 'Unknown',
                'signatureId'          => (string) $firstSigId,
                'hash_code'            => $doc->hash_code,
                'documentStatus'       => $isCompleted ? 1 : 0,
                'signatureStatus'      => $isCompleted ? 1 : 0,
                'document_createdDate' => $doc->created_at,
                'document_validThru'   => $isCompleted ? $validThru : null,
                'signature_signedDate' => $firstSignedAt,
                'signature_validThru'  => $isCompleted ? $validThru : null,
                'isForcedInvalidity'   => 0,
                'Rowstatus'            => 1,
                'CreatedBy'            => 'system',
                'CreatedDate'          => now(),
                'ModifiedBy'           => 'system',
                'ModifiedDate'         => now(),
            ]);
        }
    }

    public function down(): void
    {
        // Nothing — don't delete real data on rollback
    }
};
