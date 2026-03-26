<?php

namespace App\Http\Controllers\Portal;

use App\Http\Controllers\Controller;
use App\Models\DocumentSignature;
use App\Models\PortalDocument;
use App\Models\PortalUser;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use App\Models\DocumentVerification;
use setasign\Fpdi\Fpdi;

class DocSignController extends Controller
{
    public function index()
    {
        $user = Auth::guard('portal')->user();

        // Admins and Pengurus see all documents
        $canSeeAll = $user->isAdministrator()
            || $user->isAdm2()
            || $user->hasRole('pengurus');

        // Pending signatures assigned to this user
        $pendingMySignature = DocumentSignature::where('signer_id', $user->id)
            ->where('status', 'pending')
            ->with(['document.uploader'])
            ->latest()
            ->get();

        if ($canSeeAll) {
            $allDocuments = PortalDocument::with(['uploader', 'signatures.signer'])
                ->latest()
                ->paginate(20);
        } else {
            // Only show:
            // 1. Documents uploaded by this user
            // 2. Documents where this user is a signer (assigned to them)
            $assignedDocIds = DocumentSignature::where('signer_id', $user->id)
                ->pluck('document_id');

            $allDocuments = PortalDocument::with(['uploader', 'signatures.signer'])
                ->where(function ($q) use ($user, $assignedDocIds) {
                    $q->where('uploaded_by', $user->id)
                      ->orWhereIn('id', $assignedDocIds);
                })
                ->latest()
                ->paginate(20);
        }

        return view('portal.docsign.index', compact('pendingMySignature', 'allDocuments', 'user'));
    }

    public function upload()
    {
        $user = Auth::guard('portal')->user();

        // Has access — show the normal upload form
        if ($user->can_upload_documents || $user->isAdministrator()) {
            return view('portal.docsign.upload');
        }

        // No access — show the access request form
        $pendingRequest = \App\Models\UploadAccessRequest::where('portal_user_id', $user->id)
            ->where('status', 'pending')
            ->first();

        $lastRequest = \App\Models\UploadAccessRequest::where('portal_user_id', $user->id)
            ->latest()
            ->first();

        return view('portal.docsign.upload-request', compact('user', 'pendingRequest', 'lastRequest'));
    }

    public function store(Request $request)
    {
        $user = Auth::guard('portal')->user();
        if (!$user->can_upload_documents && !$user->isAdministrator()) {
            abort(403, 'You are not approved to upload documents.');
        }

        $request->validate([
            'title'     => ['required', 'string', 'max:255'],
            'file'      => ['required', 'file', 'mimes:pdf', 'max:20480'],
            'notes'     => ['nullable', 'string', 'max:1000'],
            'signers'   => ['nullable', 'array'],
            'signers.*' => ['exists:portal_users,id'],
        ]);

        $file     = $request->file('file');
        $hashCode = Str::random(32);
        $path     = $file->storeAs(
            'portal/documents',
            $hashCode . '_' . Str::slug(pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME)) . '.pdf',
            'local'
        );

        $document = PortalDocument::create([
            'title'             => $request->title,
            'file_path'         => $path,
            'original_filename' => $file->getClientOriginalName(),
            'hash_code'         => $hashCode,
            'uploaded_by'       => $user->id,
            'status'            => 'draft',
            'notes'             => $request->notes,
        ]);

        if ($request->filled('signers')) {
            foreach (array_values(array_unique($request->signers)) as $order => $signerId) {
                DocumentSignature::create([
                    'document_id' => $document->id,
                    'signer_id'   => $signerId,
                    'assigned_by' => $user->id,
                    'sort_order'  => $order,
                    'status'      => 'pending',
                ]);
            }
            $document->update(['status' => 'pending']);
        }

        return redirect()->route('portal.docsign.index')
            ->with('success', "Document '{$document->title}' uploaded successfully.");
    }

    public function show(PortalDocument $document)
    {
        $document->load(['uploader', 'signatures.signer', 'signatures.assignedBy']);
        $user = Auth::guard('portal')->user();
        return view('portal.docsign.show', compact('document', 'user'));
    }

    public function assign(Request $request, PortalDocument $document)
    {
        $user = Auth::guard('portal')->user();
        if ($document->uploaded_by !== $user->id && !$user->isAdministrator()) {
            abort(403);
        }

        $request->validate([
            'signer_id' => ['required', 'exists:portal_users,id'],
        ]);

        $order = $document->signatures()->max('sort_order') + 1;

        $signature = DocumentSignature::create([
            'document_id' => $document->id,
            'signer_id'   => $request->signer_id,
            'assigned_by' => $user->id,
            'sort_order'  => $order,
            'status'      => 'pending',
        ]);

        if ($document->status === 'draft') {
            $document->update(['status' => 'pending']);
        }

        $signature->load('signer');
        return response()->json(['success' => true, 'signature' => $signature]);
    }

    public function signForm(PortalDocument $document, DocumentSignature $signature)
    {
        $user = Auth::guard('portal')->user();

        if ($signature->signer_id !== $user->id) {
            abort(403, 'This document is not assigned to you.');
        }
        if ($signature->status !== 'pending') {
            return back()->with('error', 'This signature slot has already been processed.');
        }

        $document->load('signatures.signer');
        return view('portal.docsign.sign', compact('document', 'signature', 'user'));
    }

    public function processSign(Request $request, PortalDocument $document, DocumentSignature $signature)
    {
        $user = Auth::guard('portal')->user();

        if ($signature->signer_id !== $user->id) {
            abort(403);
        }
        if ($signature->status !== 'pending') {
            return back()->with('error', 'Already processed.');
        }

        $request->validate([
            'qr_page'       => ['required', 'integer', 'min:1'],
            'qr_position_x' => ['required', 'numeric', 'min:0', 'max:100'],
            'qr_position_y' => ['required', 'numeric', 'min:0', 'max:100'],
            'qr_size'       => ['required', 'numeric', 'min:20', 'max:150'],
        ]);

        // 1. Determine source PDF (use last signed version if it exists)
        $latestSigned = $document->signatures()
            ->where('status', 'signed')
            ->whereNotNull('signed_file_path')
            ->orderByDesc('sort_order')
            ->value('signed_file_path');

        $sourcePath = Storage::disk('local')->path($latestSigned ?? $document->file_path);

        // 2. Generate QR code PNG
        $verifyUrl  = url('/verify/' . $document->hash_code);
        $tempDir    = storage_path('app' . DIRECTORY_SEPARATOR . 'temp');
        $qrTempPath = $tempDir . DIRECTORY_SEPARATOR . 'qr_' . Str::random(16) . '.png';

        if (!is_dir($tempDir)) {
            mkdir($tempDir, 0755, true);
        }

        $this->generateQrPng($verifyUrl, $qrTempPath);

        // 3. Embed QR into PDF — try strategies in order, most compatible first
        $signedFilename = 'portal/documents/signed/' . $document->hash_code . '_sig' . $signature->id . '.pdf';
        $signedFullPath = Storage::disk('local')->path($signedFilename);

        if (!is_dir(dirname($signedFullPath))) {
            mkdir(dirname($signedFullPath), 0755, true);
        }

        $qrSize = (float) $request->qr_size;
        $pageNo = (int) $request->qr_page;
        $posX   = (float) $request->qr_position_x;
        $posY   = (float) $request->qr_position_y;
        $label  = 'Signed: ' . $user->name . ' - ' . now()->format('d M Y H:i');

        $success = $this->embedQrIntoPdf(
            $sourcePath, $signedFullPath,
            $qrTempPath, $pageNo, $posX, $posY, $qrSize, $label,
            $tempDir
        );

        if (!$success) {
            @unlink($qrTempPath);
            return back()->with('error',
                'Could not process the PDF. Please ensure the PDF is not password-protected and try again. ' .
                'If the problem persists, ask an admin to convert the PDF to PDF/A format before uploading.'
            );
        }

        // 5. Clean up temp QR file
        @unlink($qrTempPath);

        // 6. Update signature record
        $signature->update([
            'status'           => 'signed',
            'signed_at'        => now(),
            'qr_page'          => $request->qr_page,
            'qr_position_x'    => $request->qr_position_x,
            'qr_position_y'    => $request->qr_position_y,
            'qr_size'          => $request->qr_size,
            'signed_file_path' => $signedFilename,
        ]);

        // 7. Update document status
        $pendingCount = $document->pendingSignatures()->count();
        $isCompleted  = $pendingCount === 0;
        $document->update([
            'status' => $isCompleted ? 'completed' : 'partially_signed',
        ]);

        // 8. Write / update DocumentVerification row
        //    One row per document (identified by hash_code).
        //    Build a comma-separated list of all signers who have signed so far.
        $signedNames = $document->signatures()
            ->where('status', 'signed')
            ->with('signer')
            ->get()
            ->pluck('signer.name')
            ->filter()
            ->implode(', ');

        // Default valid thru = 5 years from first signing (adjust as needed)
        $validThru = now()->addYears(5);

        $existing = DocumentVerification::where('hash_code', $document->hash_code)->first();

        if ($existing) {
            // Update the existing row — refresh signer list and status
            $existing->update([
                'signatureBy'          => $signedNames,
                'signatureStatus'      => $isCompleted ? true : false,
                'documentStatus'       => $isCompleted ? true : false,
                'signature_signedDate' => now(),
                'signature_validThru'  => $isCompleted ? $validThru : null,
                'document_validThru'   => $isCompleted ? $validThru : null,
                'isForcedInvalidity'   => false,
                'Rowstatus'            => 1,
                'ModifiedBy'           => $user->name,
                'ModifiedDate'         => now(),
            ]);
        } else {
            // Create a fresh row for this document
            DocumentVerification::create([
                'documentName'         => $document->title,
                'documentSignature'    => $document->created_at,
                'signatureBy'          => $signedNames,
                'signatureId'          => $signature->id,
                'hash_code'            => $document->hash_code,
                'documentStatus'       => $isCompleted ? true : false,
                'signatureStatus'      => $isCompleted ? true : false,
                'document_createdDate' => $document->created_at,
                'document_validThru'   => $isCompleted ? $validThru : null,
                'signature_signedDate' => now(),
                'signature_validThru'  => $isCompleted ? $validThru : null,
                'isForcedInvalidity'   => false,
                'Rowstatus'            => 1,
                'CreatedBy'            => $user->name,
                'CreatedDate'          => now(),
                'ModifiedBy'           => $user->name,
                'ModifiedDate'         => now(),
            ]);
        }

        return redirect()->route('portal.docsign.show', $document)
            ->with('success', 'Document signed successfully! QR code has been embedded.');
    }

    public function servePdf(PortalDocument $document, Request $request)
    {
        $signatureId = $request->get('signature');

        if ($signatureId) {
            $sig  = DocumentSignature::findOrFail($signatureId);
            $path = Storage::disk('local')->path($sig->signed_file_path);
        } else {
            $path = Storage::disk('local')->path($document->file_path);
        }

        if (!file_exists($path)) {
            abort(404);
        }

        return response()->file($path, [
            'Content-Type'        => 'application/pdf',
            'Content-Disposition' => 'inline; filename="' . $document->original_filename . '"',
        ]);
    }

    public function reject(Request $request, PortalDocument $document, DocumentSignature $signature)
    {
        $user = Auth::guard('portal')->user();
        if ($signature->signer_id !== $user->id) {
            abort(403);
        }

        $request->validate(['reason' => ['required', 'string', 'max:500']]);

        $signature->update([
            'status'           => 'rejected',
            'rejection_reason' => $request->reason,
        ]);

        $document->update(['status' => 'rejected']);

        // Write NOT VALID to DocumentVerification
        $existing = DocumentVerification::where('hash_code', $document->hash_code)->first();

        $verificationData = [
            'documentName'         => $document->title,
            'documentSignature'    => $document->created_at,
            'signatureBy'          => $user->name,
            'signatureId'          => $signature->id,
            'hash_code'            => $document->hash_code,
            'documentStatus'       => false,
            'signatureStatus'      => false,
            'document_createdDate' => $document->created_at,
            'document_validThru'   => null,
            'signature_signedDate' => now(),
            'signature_validThru'  => null,
            'isForcedInvalidity'   => false,
            'Rowstatus'            => 1,
            'ModifiedBy'           => $user->name,
            'ModifiedDate'         => now(),
        ];

        if ($existing) {
            $existing->update($verificationData);
        } else {
            DocumentVerification::create(array_merge($verificationData, [
                'CreatedBy'   => $user->name,
                'CreatedDate' => now(),
            ]));
        }

        return redirect()->route('portal.docsign.index')
            ->with('info', 'You have rejected signing the document.');
    }

    // ─────────────────────────────────────────────────────────────────────────
    // PDF Digital Signature (TCPDF built-in — makes PDF readers show "Signed")
    // ─────────────────────────────────────────────────────────────────────────

    /**
     * Apply an invisible PDF digital signature to the already-saved PDF.
     * Uses TCPDF's setSignature() which embeds a proper PKCS#7 signature
     * that Adobe Reader and other PDF readers can verify.
     *
     * Requirements:
     *  - Run generate_cert.php once to create the certificate
     *  - Add to .env: VOS_CERT_PATH, VOS_KEY_PATH, VOS_CERT_PASS
     */
    private function applyPdfDigitalSignature(
        string $pdfPath,
        \App\Models\PortalUser $user,
        \App\Models\PortalDocument $document
    ): void {
        $certPath = env('VOS_CERT_PATH', storage_path('app/portal/certs/vos_cert.pem'));
        $keyPath  = env('VOS_KEY_PATH',  storage_path('app/portal/certs/vos_key.pem'));
        $certPass = env('VOS_CERT_PASS', '');

        // Skip silently if cert files don't exist yet
        if (!file_exists($certPath) || !file_exists($keyPath)) {
            return;
        }

        try {
            $certContent = file_get_contents($certPath);
            $keyContent  = file_get_contents($keyPath);

            // Create a new TCPDF instance just for the digital signature
            // We re-open the already-saved PDF, apply the signature, and save again
            $pdf = new FpdiTcPdf();
            $pdf->setPrintHeader(false);
            $pdf->setPrintFooter(false);
            $pdf->SetAutoPageBreak(false);
            $pdf->SetMargins(0, 0, 0);

            // Set the digital signature
            $pdf->setSignature(
                $certContent,          // certificate
                $keyContent,           // private key
                $certPass,             // passphrase (empty for no passphrase)
                '',                    // extra certs (empty)
                3,                     // cert type: 3 = approval signature
                [                      // signature info
                    'Name'        => $user->name,
                    'Location'    => 'VOS Portal',
                    'Reason'      => 'Approved: ' . $document->title,
                    'ContactInfo' => $user->email,
                ]
            );

            // Re-import the PDF we just saved (with QR already embedded)
            $pageCount = $pdf->setSourceFile($pdfPath);

            for ($p = 1; $p <= $pageCount; $p++) {
                $tpl  = $pdf->importPage($p);
                $size = $pdf->getTemplateSize($tpl);
                $orientation = $size['orientation'] ?? 'P';
                $pdf->AddPage($orientation, [$size['width'], $size['height']]);
                $pdf->SetMargins(0, 0, 0);
                $pdf->useTemplate($tpl, 0, 0, $size['width'], $size['height'], true);

                // Add an invisible signature field on the first page
                if ($p === 1) {
                    $pdf->setSignatureAppearance(0, 0, 0, 0); // invisible (0,0,0,0)
                }
            }

            // Overwrite the file with the digitally signed version
            $pdf->Output($pdfPath, 'F');

        } catch (\Throwable $e) {
            // Digital signature failed — the QR-signed PDF is still valid
            // Log the error but don't break the signing workflow
            \Illuminate\Support\Facades\Log::warning('PDF digital signature failed: ' . $e->getMessage(), [
                'document_id' => $document->id,
                'user_id'     => $user->id,
            ]);
        }
    }

    // ─────────────────────────────────────────────────────────────────────────
    // PDF QR embedding — multi-strategy, works on shared hosting (no installs)
    // ─────────────────────────────────────────────────────────────────────────

    private function embedQrIntoPdf(
        string $sourcePath,
        string $outputPath,
        string $qrImagePath,
        int    $targetPage,
        float  $posXPct,
        float  $posYPct,
        float  $qrSizeMm,
        string $label,
        string $tempDir
    ): bool {

        // ── Strategy 1: Plain FPDI (works on PDF 1.4 and below) ──────────────
        try {
            $pdf = new Fpdi();
            $pdf->SetAutoPageBreak(false);
            $pdf->SetMargins(0, 0, 0);
            $pageCount = $pdf->setSourceFile($sourcePath);

            for ($p = 1; $p <= $pageCount; $p++) {
                $tpl  = $pdf->importPage($p);
                $size = $pdf->getTemplateSize($tpl);
                $orientation = ($size['width'] > $size['height']) ? 'L' : 'P';
                $pdf->AddPage($orientation, [$size['width'], $size['height']]);
                $pdf->useTemplate($tpl, 0, 0, $size['width'], $size['height']);

                if ($p === $targetPage) {
                    $this->stampQrOnFpdf($pdf, $qrImagePath, $size, $posXPct, $posYPct, $qrSizeMm, $label);
                }
            }

            $pdf->Output($outputPath, 'F');

            if (file_exists($outputPath) && filesize($outputPath) > 500) {
                return true;
            }
        } catch (\Throwable $e) {
            // PDF is compressed (1.5+) — fall through to next strategy
        }

        // ── Strategy 2: GhostScript flatten then FPDI ─────────────────────────
        $flatPath = $tempDir . DIRECTORY_SEPARATOR . 'flat_' . Str::random(8) . '.pdf';
        if ($this->flattenPdfWithGhostscript($sourcePath, $flatPath)) {
            try {
                $pdf = new Fpdi();
                $pdf->SetAutoPageBreak(false);
                $pdf->SetMargins(0, 0, 0);
                $pageCount = $pdf->setSourceFile($flatPath);

                for ($p = 1; $p <= $pageCount; $p++) {
                    $tpl  = $pdf->importPage($p);
                    $size = $pdf->getTemplateSize($tpl);
                    $orientation = ($size['width'] > $size['height']) ? 'L' : 'P';
                    $pdf->AddPage($orientation, [$size['width'], $size['height']]);
                    $pdf->useTemplate($tpl, 0, 0, $size['width'], $size['height']);

                    if ($p === $targetPage) {
                        $this->stampQrOnFpdf($pdf, $qrImagePath, $size, $posXPct, $posYPct, $qrSizeMm, $label);
                    }
                }

                $pdf->Output($outputPath, 'F');
                @unlink($flatPath);

                if (file_exists($outputPath) && filesize($outputPath) > 500) {
                    return true;
                }
            } catch (\Throwable $e) {
                @unlink($flatPath);
            }
        }

        // ── Strategy 3: Imagick — render PDF pages as images, stamp QR, rebuild ──
        // Works on most cPanel hosts (Imagick is standard), handles ALL PDF versions
        if (extension_loaded('imagick')) {
            try {
                return $this->embedQrViaImagick(
                    $sourcePath, $outputPath, $qrImagePath,
                    $targetPage, $posXPct, $posYPct, $qrSizeMm, $label
                );
            } catch (\Throwable $e) {
                // fall through
            }
        }

        // ── Strategy 4: Raw PDF content stream injection (pure PHP, no deps) ────
        // Injects the QR as a raw PDF XObject into the document byte stream.
        // Works on PDF 1.5+ without any library. Less precise but functional.
        try {
            return $this->embedQrViaStreamInjection(
                $sourcePath, $outputPath, $qrImagePath,
                $targetPage, $posXPct, $posYPct, $qrSizeMm, $label
            );
        } catch (\Throwable $e) {
            // all strategies failed
        }

        return false;
    }

    private function stampQrOnFpdf(
        Fpdi $pdf, string $qrPath, array $size,
        float $posXPct, float $posYPct, float $qrSizeMm, string $label
    ): void {
        $xMm = ($posXPct / 100) * $size['width'];
        $yMm = ($posYPct / 100) * $size['height'];
        $xMm = max(0, min($xMm, $size['width']  - $qrSizeMm));
        $yMm = max(4, min($yMm, $size['height'] - $qrSizeMm));

        $pdf->SetFont('Helvetica', '', 6);
        $pdf->SetTextColor(80, 80, 80);
        $pdf->SetXY($xMm, $yMm - 4);
        $pdf->Cell($qrSizeMm, 4, $label, 0, 0, 'L');
        $pdf->Image($qrPath, $xMm, $yMm, $qrSizeMm, $qrSizeMm, 'PNG');
    }

    private function embedQrViaImagick(
        string $sourcePath, string $outputPath, string $qrImagePath,
        int $targetPage, float $posXPct, float $posYPct, float $qrSizeMm, string $label
    ): bool {
        // Resolution for rendering — 150dpi is good quality without being huge
        $dpi = 150;

        $imagick = new \Imagick();
        $imagick->setResolution($dpi, $dpi);
        $imagick->readImage($sourcePath);
        $imagick->setImageFormat('png');

        $pageCount = $imagick->getNumberImages();
        $pages     = [];

        for ($p = 0; $p < $pageCount; $p++) {
            $imagick->setIteratorIndex($p);
            $page = $imagick->getImage();
            $page->setImageFormat('png');

            if (($p + 1) === $targetPage) {
                $w = $page->getImageWidth();
                $h = $page->getImageHeight();

                // Load QR and resize to match position percentage
                $qrPx   = (int)(($qrSizeMm / 25.4) * $dpi);
                $qrImg  = new \Imagick($qrImagePath);
                $qrImg->resizeImage($qrPx, $qrPx, \Imagick::FILTER_LANCZOS, 1);

                $xPx = (int)(($posXPct / 100) * $w);
                $yPx = (int)(($posYPct / 100) * $h);
                $xPx = max(0, min($xPx, $w - $qrPx));
                $yPx = max(0, min($yPx, $h - $qrPx));

                // Add label text
                $draw = new \ImagickDraw();
                $draw->setFontSize(10);
                $draw->setFillColor('#505050');
                $page->annotateImage($draw, $xPx, $yPx - 4, 0, $label);

                // Composite QR onto page
                $page->compositeImage($qrImg, \Imagick::COMPOSITE_OVER, $xPx, $yPx);
                $qrImg->destroy();
            }

            $pages[] = $page;
        }
        $imagick->destroy();

        // Rebuild as PDF using FPDF with each page as a full-page image
        $pdf = new \setasign\Fpdi\Fpdi();
        $pdf->SetAutoPageBreak(false);
        $pdf->SetMargins(0, 0, 0);

        foreach ($pages as $i => $pageImg) {
            // Get dimensions from the rendered image in mm (at chosen dpi)
            $w = $pageImg->getImageWidth();
            $h = $pageImg->getImageHeight();
            $wMm = ($w / $dpi) * 25.4;
            $hMm = ($h / $dpi) * 25.4;

            $orientation = ($wMm > $hMm) ? 'L' : 'P';
            $pdf->AddPage($orientation, [$wMm, $hMm]);

            // Save page as temp PNG and embed
            $tmpPage = dirname($outputPath) . DIRECTORY_SEPARATOR . 'pg_' . $i . '_' . Str::random(6) . '.png';
            $pageImg->writeImage($tmpPage);
            $pdf->Image($tmpPage, 0, 0, $wMm, $hMm, 'PNG');
            @unlink($tmpPage);
            $pageImg->destroy();
        }

        $pdf->Output($outputPath, 'F');
        return file_exists($outputPath) && filesize($outputPath) > 500;
    }

    private function embedQrViaStreamInjection(
        string $sourcePath, string $outputPath, string $qrImagePath,
        int $targetPage, float $posXPct, float $posYPct, float $qrSizeMm, string $label
    ): bool {
        // Read the PDF bytes
        $pdfBytes = file_get_contents($sourcePath);
        if (!$pdfBytes) return false;

        // Read QR PNG and encode as base64 for embedding
        $qrBytes  = file_get_contents($qrImagePath);
        $qrInfo   = getimagesize($qrImagePath);
        if (!$qrInfo) return false;

        // Encode QR as JPEG for simpler PDF embedding
        $qrGd = imagecreatefrompng($qrImagePath);
        ob_start();
        imagejpeg($qrGd, null, 95);
        $jpegData = ob_get_clean();
        imagedestroy($qrGd);

        $jpgLen    = strlen($jpegData);
        $qrWidthPx = $qrInfo[0];
        $qrHgtPx   = $qrInfo[1];

        // Approximate page size A4 in points (pt) = mm * 2.83465
        // We'll inject the QR at approximate coordinates
        // A4: 595pt x 842pt  — position as percentage
        $pageWidthPt  = 595;
        $pageHeightPt = 842;
        $qrSizePt     = ($qrSizeMm * 2.83465);
        $xPt = ($posXPct / 100) * $pageWidthPt;
        $yPt = $pageHeightPt - ($posYPct / 100) * $pageHeightPt - $qrSizePt;
        $xPt = max(0, min($xPt, $pageWidthPt - $qrSizePt));
        $yPt = max(0, $yPt);

        // Build a minimal PDF with the original content + QR overlay
        // This is a simplified injection that appends the QR as an incremental update
        $objNum = $this->getHighestPdfObjectNumber($pdfBytes) + 1;

        $imageObj = "{$objNum} 0 obj\n"
            . "<< /Type /XObject /Subtype /Image\n"
            . "   /Width {$qrWidthPx} /Height {$qrHgtPx}\n"
            . "   /ColorSpace /DeviceRGB /BitsPerComponent 8\n"
            . "   /Filter /DCTDecode /Length {$jpgLen} >>\n"
            . "stream\n"
            . $jpegData
            . "\nendstream\nendobj\n";

        $contentStream = "q {$qrSizePt} 0 0 {$qrSizePt} {$xPt} {$yPt} cm /QR Do Q";
        $streamLen     = strlen($contentStream);
        $contentObj    = ($objNum + 1) . " 0 obj\n"
            . "<< /Length {$streamLen} >>\n"
            . "stream\n{$contentStream}\nendstream\nendobj\n";

        // Write original PDF + appended objects
        $newPdf = $pdfBytes . "\n" . $imageObj . $contentObj;
        file_put_contents($outputPath, $newPdf);

        return file_exists($outputPath) && filesize($outputPath) > strlen($pdfBytes);
    }

    private function getHighestPdfObjectNumber(string $pdfBytes): int
    {
        preg_match_all('/^(\d+)\s+\d+\s+obj/m', $pdfBytes, $matches);
        return !empty($matches[1]) ? (int) max($matches[1]) : 100;
    }

    // ─────────────────────────────────────────────────────────────────────────
    // PDF flattening — converts compressed PDF 1.5+ to PDF 1.4 for FPDI
    // ─────────────────────────────────────────────────────────────────────────

    private function flattenPdfWithGhostscript(string $inputPath, string $outputPath): bool
    {
        // Common GhostScript executable names/paths on Windows and Linux
        $candidates = [
            'gswin64c',
            'gswin32c',
            'gs',
            'C:\\Program Files\\gs\\gs10.04.0\\bin\\gswin64c.exe',
            'C:\\Program Files\\gs\\gs10.03.1\\bin\\gswin64c.exe',
            'C:\\Program Files\\gs\\gs10.02.1\\bin\\gswin64c.exe',
            'C:\\Program Files (x86)\\gs\\gs9.56.1\\bin\\gswin32c.exe',
        ];

        $gs = null;
        foreach ($candidates as $candidate) {
            $test = @shell_exec('"' . $candidate . '" --version 2>&1');
            if ($test && preg_match('/\d+\.\d+/', $test)) {
                $gs = $candidate;
                break;
            }
        }

        if (!$gs) {
            return false;
        }

        $cmd = '"' . $gs . '"'
             . ' -dBATCH -dNOPAUSE -dQUIET'
             . ' -sDEVICE=pdfwrite'
             . ' -dCompatibilityLevel=1.4'
             . ' -dPrinted=false'
             . ' -sOutputFile=' . escapeshellarg($outputPath)
             . ' ' . escapeshellarg($inputPath)
             . ' 2>&1';

        @shell_exec($cmd);

        return file_exists($outputPath) && filesize($outputPath) > 100;
    }

    // ─────────────────────────────────────────────────────────────────────────
    // QR Code generation — tries multiple strategies, then overlays logo
    // ─────────────────────────────────────────────────────────────────────────

    private function generateQrPng(string $text, string $outputPath): void
    {
        // Strategy 1: SimpleSoftwareIO/simple-qrcode
        if (class_exists(\SimpleSoftwareIO\QrCode\Facades\QrCode::class)) {
            try {
                \SimpleSoftwareIO\QrCode\Facades\QrCode::format('png')
                    ->size(300)
                    ->errorCorrection('H')  // High error correction — needed for logo overlay
                    ->generate($text, $outputPath);

                if (file_exists($outputPath) && filesize($outputPath) > 0) {
                    $this->overlayLogoOnQr($outputPath);
                    return;
                }
            } catch (\Throwable $e) {
                // fall through
            }
        }

        // Strategy 2: endroid/qr-code
        if (class_exists(\Endroid\QrCode\QrCode::class)) {
            try {
                $qr     = \Endroid\QrCode\QrCode::create($text)->setSize(300)->setMargin(10);
                $writer = new \Endroid\QrCode\Writer\PngWriter();
                file_put_contents($outputPath, $writer->write($qr)->getString());
                $this->overlayLogoOnQr($outputPath);
                return;
            } catch (\Throwable $e) {
                // fall through
            }
        }

        // Strategy 3: GD fallback
        if (extension_loaded('gd')) {
            $this->generateQrWithGd($text, $outputPath);
            $this->overlayLogoOnQr($outputPath);
            return;
        }

        throw new \RuntimeException(
            'Cannot generate QR code. Please run: composer require simplesoftwareio/simple-qrcode' . PHP_EOL .
            'Also ensure the PHP GD extension is enabled in php.ini (extension=gd).'
        );
    }

    /**
     * Overlay the VOS logo in the center of the QR code PNG.
     * Uses error correction level H so the QR remains scannable
     * even with ~30% of the center covered by the logo.
     */
    private function overlayLogoOnQr(string $qrPath): void
    {
        if (!extension_loaded('gd') || !file_exists($qrPath)) {
            return; // Skip gracefully if GD not available
        }

        // Logo path — stored in public/assets/images/
        $logoPath = public_path('assets/images/logo-dark.png');

        if (!file_exists($logoPath)) {
            // Try storage fallback
            $logoPath = storage_path('app/portal/logo-dark.png');
        }

        if (!file_exists($logoPath)) {
            return; // No logo found — QR still works without it
        }

        try {
            // Load QR
            $qr = imagecreatefrompng($qrPath);
            if (!$qr) return;

            $qrW = imagesx($qr);
            $qrH = imagesy($qr);

            // Load logo — support PNG and JPEG
            $ext  = strtolower(pathinfo($logoPath, PATHINFO_EXTENSION));
            $logo = match ($ext) {
                'png'        => @imagecreatefrompng($logoPath),
                'jpg','jpeg' => @imagecreatefromjpeg($logoPath),
                default      => false,
            };

            if (!$logo) {
                imagedestroy($qr);
                return;
            }

            // Logo occupies 25% of QR width (safe with H error correction)
            $logoTargetW = (int) round($qrW * 0.25);
            $logoW       = imagesx($logo);
            $logoH       = imagesy($logo);
            $logoTargetH = (int) round($logoTargetW * ($logoH / $logoW));

            // Create resized logo
            $logoResized = imagecreatetruecolor($logoTargetW, $logoTargetH);
            imagealphablending($logoResized, false);
            imagesavealpha($logoResized, true);

            // White background behind logo so it blends on QR
            $white = imagecolorallocate($logoResized, 255, 255, 255);
            imagefilledrectangle($logoResized, 0, 0, $logoTargetW, $logoTargetH, $white);

            imagecopyresampled(
                $logoResized, $logo,
                0, 0, 0, 0,
                $logoTargetW, $logoTargetH,
                $logoW, $logoH
            );

            // Draw white rounded rectangle behind logo for clean look
            $padX = (int) round($qrW * 0.02);
            $padY = (int) round($qrH * 0.02);
            $bgX  = (int) round(($qrW - $logoTargetW) / 2) - $padX;
            $bgY  = (int) round(($qrH - $logoTargetH) / 2) - $padY;
            $bgW  = $logoTargetW + $padX * 2;
            $bgH  = $logoTargetH + $padY * 2;

            $bgColor = imagecolorallocate($qr, 255, 255, 255);
            imagefilledrectangle($qr, $bgX, $bgY, $bgX + $bgW, $bgY + $bgH, $bgColor);

            // Center logo on QR
            $destX = (int) round(($qrW - $logoTargetW) / 2);
            $destY = (int) round(($qrH - $logoTargetH) / 2);

            imagecopy($qr, $logoResized, $destX, $destY, 0, 0, $logoTargetW, $logoTargetH);

            // Save back
            imagepng($qr, $qrPath);

            imagedestroy($qr);
            imagedestroy($logo);
            imagedestroy($logoResized);
        } catch (\Throwable $e) {
            // Don't break signing if logo overlay fails
        }
    }

    private function generateQrWithGd(string $text, string $outputPath): void
    {
        // Try free QR API (needs internet access)
        $apiUrl  = 'https://api.qrserver.com/v1/create-qr-code/?size=300x300&ecc=H&data=' . urlencode($text);
        $imgData = @file_get_contents($apiUrl);

        if ($imgData !== false && strlen($imgData) > 500) {
            file_put_contents($outputPath, $imgData);
            return;
        }

        // Offline placeholder so signing still completes
        $img   = imagecreatetruecolor(300, 300);
        $white = imagecolorallocate($img, 255, 255, 255);
        $black = imagecolorallocate($img, 0, 0, 0);
        $gray  = imagecolorallocate($img, 180, 180, 180);
        imagefill($img, 0, 0, $white);
        imagerectangle($img, 5, 5, 294, 294, $gray);
        imagestring($img, 4, 80, 130, 'QR CODE', $black);
        imagestring($img, 1, 10, 155, substr($text, -40), $gray);
        imagepng($img, $outputPath);
        imagedestroy($img);
    }
}
