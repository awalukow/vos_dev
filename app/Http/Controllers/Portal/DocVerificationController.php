<?php

namespace App\Http\Controllers\Portal;

use App\Http\Controllers\Controller;
use App\Models\DocumentVerification;
use App\Models\PortalDocument;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class DocVerificationController extends Controller
{
    /** Force-invalidate a document (admin only) */
    public function invalidate(Request $request, PortalDocument $document)
    {
        $request->validate(['reason' => ['required', 'string', 'max:500']]);

        $user = Auth::guard('portal')->user();

        DocumentVerification::where('hash_code', $document->hash_code)
            ->update([
                'isForcedInvalidity' => true,
                'signatureStatus'    => false,
                'documentStatus'     => false,
                'ModifiedBy'         => $user->name,
                'ModifiedDate'       => now(),
                // Store reason in signatureBy field temporarily, or add a column
                // We'll prefix it so the verify page shows it properly
            ]);

        // Optionally store reason back into the rejection_reason on the signature
        $document->signatures()
            ->where('status', 'signed')
            ->update(['rejection_reason' => '[ADMIN INVALIDATED] ' . $request->reason]);

        return back()->with('success', 'Document has been force-invalidated. Verify page now shows NOT VALID.');
    }

    /** Restore a force-invalidated document (admin only) */
    public function restore(PortalDocument $document)
    {
        $user = Auth::guard('portal')->user();

        DocumentVerification::where('hash_code', $document->hash_code)
            ->update([
                'isForcedInvalidity' => false,
                'signatureStatus'    => $document->status === 'completed' ? true : false,
                'documentStatus'     => $document->status === 'completed' ? true : false,
                'ModifiedBy'         => $user->name,
                'ModifiedDate'       => now(),
            ]);

        return back()->with('success', 'Document validity has been restored.');
    }

    /** Change the valid thru date (admin only) */
    public function setValidThru(Request $request, PortalDocument $document)
    {
        $request->validate(['valid_thru' => ['required', 'date']]);

        $user    = Auth::guard('portal')->user();
        $newDate = \Carbon\Carbon::parse($request->valid_thru)->endOfDay();

        DocumentVerification::where('hash_code', $document->hash_code)
            ->update([
                'signature_validThru' => $newDate,
                'document_validThru'  => $newDate,
                'ModifiedBy'          => $user->name,
                'ModifiedDate'        => now(),
            ]);

        return back()->with('success', 'Valid thru date updated to ' . $newDate->format('d F Y') . '.');
    }
}
