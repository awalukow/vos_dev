<?php

namespace App\Http\Controllers\Portal;

use App\Http\Controllers\Controller;
use App\Models\PortalDocument;
use App\Models\PortalUser;
use Illuminate\Support\Facades\Auth;

class DashboardController extends Controller
{
    public function index()
    {
        $user = Auth::guard('portal')->user();

        $stats = [
            'total_users'       => PortalUser::count(),
            'pending_docs'      => \App\Models\DocumentSignature::where('signer_id', $user->id)
                                        ->where('status', 'pending')->count(),
            'total_docs'        => PortalDocument::where('uploaded_by', $user->id)->count(),
            'docs_to_sign'      => \App\Models\DocumentSignature::where('signer_id', $user->id)
                                        ->where('status', 'pending')
                                        ->with('document')
                                        ->latest()
                                        ->take(5)
                                        ->get(),
        ];

        return view('portal.dashboard.index', compact('user', 'stats'));
    }
}
