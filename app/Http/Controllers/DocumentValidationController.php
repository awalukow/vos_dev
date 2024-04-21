<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\DocumentVerification;

class DocumentValidationController extends Controller
{
    public function showDocumentValidation_old()
    {
        // Assuming you have fetched this data from somewhere
        $documentName = "MOU Konser Gibeon Bermazmur";
        $signedBy = "Chandra Budiharto";
        $validationStatus = "NOT VALID";
        $validThru = "2024-04-30";
        $invalidReason = "Dokumen Expired";

        // Pass data to the Blade view
        return view('documentValidation', compact('documentName', 'signedBy', 'validationStatus', 'validThru', 'invalidReason'));
    }
    public function showDocumentValidation($signatureId)
    {
        // Fetch the document from the database using the $id parameter
        $document = DocumentVerification::where('signatureId', $signatureId)->first();

        // Check if the document exists and meets the validation criteria
        if ($document && $document->Rowstatus >= 0 && $document->document_validThru >= now()) {
            $validationStatus = "VALID";
            $invalidReason = null; // No invalid reason if document is valid
        } else {
            $validationStatus = "NOT VALID";
            $invalidReason = "Document not found or expired";
        }

        // Pass data to the Blade view
        return view('documentValidation', [
            'documentName' => $document->documentName ?? 'Document Not Found',
            'signedBy' => $document->signatureBy ?? 'Unknown',
            'validThru' => $document->document_validThru ?? 'Unknown',
            'validationStatus' => $validationStatus,
            'invalidReason' => $invalidReason,
        ]);
    }
}
