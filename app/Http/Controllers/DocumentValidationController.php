<?php

namespace App\Http\Controllers;

use App\Models\DocumentVerification;

class DocumentValidationController extends Controller
{
    /**
     * Route: GET /verify/{id}
     *
     * {id} is the hash_code from portal_documents (same value embedded in the QR).
     * Looks up DocumentVerification.hash_code and passes variables the existing view expects.
     */
    public function showDocumentValidation(string $id)
    {
        $record = DocumentVerification::where('hash_code', $id)
            ->where('Rowstatus', 1)
            ->first();

        if (!$record) {
            return view('documentValidation', [
                'documentName'     => 'Unknown Document',
                'signedBy'         => 'Unknown',
                'validationStatus' => 'NOT VALID',
                'validThru'        => 'Unknown',
                'invalidReason'    => 'Document not found or expired.',
            ]);
        }

        $validThru = $record->signature_validThru
            ? $record->signature_validThru->format('d F Y')
            : ($record->document_validThru
                ? $record->document_validThru->format('d F Y')
                : 'No expiry date set');

        return view('documentValidation', [
            'documentName'     => $record->documentName,
            'signedBy'         => $record->signatureBy ?: 'Unknown',
            'validationStatus' => $record->validation_status,
            'validThru'        => $validThru,
            'invalidReason'    => $record->invalid_reason,
        ]);
    }
}
