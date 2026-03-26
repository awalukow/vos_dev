<?php

namespace App\Http\Controllers\Portal;

use App\Http\Controllers\Controller;
use App\Models\UploadAccessRequest;
use App\Models\PortalUser;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class UploadAccessController extends Controller
{
    /**
     * GET portal/docsign/access-approval
     * For admins/pengurus: shows all pending requests.
     * For regular users: shows their own request history.
     */
    public function index()
    {
        $user = Auth::guard('portal')->user();
        $canApprove = $user->isAdministrator()
            || $user->isAdm2()
            || $user->hasRole('pengurus');

        if ($canApprove) {
            $pending = UploadAccessRequest::where('status', 'pending')
                ->with('user')
                ->latest()
                ->get();

            $history = UploadAccessRequest::whereIn('status', ['approved', 'rejected'])
                ->with(['user', 'reviewer'])
                ->latest()
                ->paginate(15);

            return view('portal.docsign.access-approval', compact('pending', 'history', 'user', 'canApprove'));
        }

        // Regular user: show their own requests
        $myRequests = UploadAccessRequest::where('portal_user_id', $user->id)
            ->with('reviewer')
            ->latest()
            ->get();

        return view('portal.docsign.access-approval', compact('myRequests', 'user', 'canApprove'));
    }

    /**
     * POST portal/docsign/access-approval/request
     * Regular user submits an upload access request.
     */
    public function store(Request $request)
    {
        $user = Auth::guard('portal')->user();

        // Already has access
        if ($user->can_upload_documents || $user->isAdministrator()) {
            return back()->with('info', 'You already have upload access.');
        }

        // Has a pending request already
        $existing = UploadAccessRequest::where('portal_user_id', $user->id)
            ->where('status', 'pending')
            ->first();

        if ($existing) {
            return back()->with('info', 'You already have a pending request. Please wait for approval.');
        }

        $request->validate([
            'message' => ['nullable', 'string', 'max:500'],
        ]);

        UploadAccessRequest::create([
            'portal_user_id' => $user->id,
            'message'        => $request->message,
            'status'         => 'pending',
        ]);

        return back()->with('success', 'Your request has been submitted. Admins will review it shortly.');
    }

    /**
     * POST portal/docsign/access-approval/{request}/approve
     */
    public function approve(Request $request, UploadAccessRequest $accessRequest)
    {
        $reviewer = Auth::guard('portal')->user();

        $request->validate([
            'note' => ['nullable', 'string', 'max:500'],
        ]);

        // Grant upload permission to the user
        $accessRequest->user->update(['can_upload_documents' => true]);

        $accessRequest->update([
            'status'        => 'approved',
            'reviewed_by'   => $reviewer->id,
            'reviewer_note' => $request->note,
            'reviewed_at'   => now(),
        ]);

        return back()->with('success', "Upload access granted to {$accessRequest->user->name}.");
    }

    /**
     * POST portal/docsign/access-approval/{request}/reject
     */
    public function reject(Request $request, UploadAccessRequest $accessRequest)
    {
        $reviewer = Auth::guard('portal')->user();

        $request->validate([
            'note' => ['required', 'string', 'max:500'],
        ]);

        $accessRequest->update([
            'status'        => 'rejected',
            'reviewed_by'   => $reviewer->id,
            'reviewer_note' => $request->note,
            'reviewed_at'   => now(),
        ]);

        return back()->with('info', "Request from {$accessRequest->user->name} has been rejected.");
    }
}
