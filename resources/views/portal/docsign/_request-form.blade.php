{{-- resources/views/portal/docsign/_request-form.blade.php --}}
<div class="card">
    <div class="card-header"><span class="card-title">Request Upload Access</span></div>
    <div class="card-body">
        <form method="POST" action="{{ route('portal.docsign.access-approval.request') }}">
            @csrf
            <div class="form-group" style="margin-bottom:1.25rem;">
                <label class="form-label">Message <span style="font-weight:400;text-transform:none;letter-spacing:0;color:var(--text-muted);">(optional)</span></label>
                <textarea name="message" class="form-control" rows="4"
                    placeholder="Briefly explain why you need upload access — e.g. your role, what documents you'll be uploading…">{{ old('message') }}</textarea>
                @error('message') <div class="form-error">{{ $message }}</div> @enderror
            </div>
            <button type="submit" class="btn btn-primary" style="width:100%;">
                Send Request →
            </button>
        </form>
    </div>
</div>
