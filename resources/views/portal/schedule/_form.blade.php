{{-- resources/views/portal/schedule/_form.blade.php --}}
@php
    $isEdit = !is_null($schedule);
    $action = $isEdit ? route('portal.schedule.update', $schedule) : route('portal.schedule.store');

    $dateStart    = old('program_date_display',  $schedule?->program_date?->format('d/m/Y H:i')  ?? '');
    $dateEnd      = old('program_until_display', $schedule?->program_until?->format('d/m/Y H:i') ?? '');
    $dateStartIso = old('program_date',          $schedule?->program_date?->format('Y-m-d H:i')  ?? '');
    $dateEndIso   = old('program_until',         $schedule?->program_until?->format('Y-m-d H:i') ?? '');
@endphp

{{-- ── Flatpickr ── --}}
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/flatpickr/4.6.13/flatpickr.min.css">
<script src="https://cdnjs.cloudflare.com/ajax/libs/flatpickr/4.6.13/flatpickr.min.js"></script>

<style>
/* Flatpickr dark theme */
.flatpickr-calendar{background:#1a1a1f!important;border:1px solid #2a2a32!important;box-shadow:0 8px 32px rgba(0,0,0,.5)!important;font-family:'Inter',sans-serif!important}
.flatpickr-months,.flatpickr-weekdays{background:#131316!important}
.flatpickr-day{color:#e8e8ec!important}
.flatpickr-day:hover{background:rgba(200,169,110,.15)!important;border-color:transparent!important}
.flatpickr-day.selected,.flatpickr-day.selected:hover{background:#c8a96e!important;border-color:#c8a96e!important;color:#09090b!important}
.flatpickr-day.today{border-color:rgba(200,169,110,.4)!important}
.flatpickr-months .flatpickr-month,.flatpickr-current-month,.flatpickr-monthDropdown-months,.cur-month,.cur-year{color:#e8e8ec!important}
.flatpickr-prev-month svg,.flatpickr-next-month svg{fill:#e8e8ec!important}
.flatpickr-time input,.flatpickr-time .flatpickr-time-separator,.flatpickr-time .numInputWrapper{color:#e8e8ec!important}
.flatpickr-time input:hover,.flatpickr-time input:focus{background:rgba(200,169,110,.1)!important}
span.flatpickr-weekday{color:#6b6b7b!important;background:transparent!important}
.numInputWrapper span{color:#e8e8ec!important}

/* Location autocomplete */
.pac-container {
    background: #1a1a1f;
    border: 1px solid #2a2a32;
    border-radius: 8px;
    box-shadow: 0 8px 24px rgba(0,0,0,.5);
    font-family: 'Inter', sans-serif;
    margin-top: 3px;
}
.pac-item {
    padding: .55rem 1rem;
    color: #e8e8ec;
    border-top: 1px solid #1e1e24;
    cursor: pointer;
    font-size: .84rem;
    line-height: 1.45;
}
.pac-item:first-child { border-top: none; }
.pac-item:hover, .pac-item-selected { background: rgba(200,169,110,.1) !important; }
.pac-item-query { color: #e8e8ec; font-weight: 600; font-size: .84rem; }
.pac-matched { color: #c8a96e; }
.pac-icon { display: none; }  /* hide default Google pin icon */

/* Maps preview bar */
.maps-preview {
    display: none;
    align-items: center;
    gap: .5rem;
    margin-top: .45rem;
    font-size: .78rem;
    color: #c8a96e;
    padding: .4rem .75rem;
    background: rgba(200,169,110,.07);
    border-radius: 6px;
    border: 1px solid rgba(200,169,110,.2);
}
.maps-preview a { color: #c8a96e; word-break: break-all; text-decoration: none; }
.maps-preview a:hover { text-decoration: underline; }
</style>

<form method="POST" action="{{ $action }}" id="scheduleForm">
    @csrf
    @if($isEdit) @method('PUT') @endif

    {{-- Hidden ISO date fields --}}
    <input type="hidden" name="program_date"  id="program_date_iso"  value="{{ $dateStartIso }}">
    <input type="hidden" name="program_until" id="program_until_iso" value="{{ $dateEndIso }}">
    {{-- Hidden maps URL --}}
    <input type="hidden" name="maps_url" id="maps_url_hidden" value="{{ old('maps_url', $schedule?->maps_url) }}">

    <div class="card">
        <div class="card-header"><span class="card-title">Schedule Details</span></div>
        <div class="card-body" style="display:flex;flex-direction:column;gap:1.1rem;">

            {{-- Nama + Tipe --}}
            <div style="display:grid;grid-template-columns:1fr 1fr;gap:1rem;">
                <div class="form-group">
                    <label class="form-label">Nama Kegiatan *</label>
                    <input type="text" name="event_name"
                           value="{{ old('event_name', $schedule?->event_name) }}"
                           class="form-control {{ $errors->has('event_name') ? 'is-invalid' : '' }}"
                           placeholder="e.g. Kebaktian Minggu Pagi">
                    @error('event_name') <div class="form-error">{{ $message }}</div> @enderror
                </div>
                <div class="form-group">
                    <label class="form-label">Tipe Kegiatan *</label>
                    <select name="event_type" class="form-control {{ $errors->has('event_type') ? 'is-invalid' : '' }}">
                        <option value="Pelayanan" {{ old('event_type', $schedule?->event_type) === 'Pelayanan' ? 'selected' : '' }}>Pelayanan</option>
                        <option value="Konser"    {{ old('event_type', $schedule?->event_type) === 'Konser'    ? 'selected' : '' }}>Konser</option>
                    </select>
                    @error('event_type') <div class="form-error">{{ $message }}</div> @enderror
                </div>
            </div>

            {{-- Tanggal Mulai + Selesai --}}
            <div style="display:grid;grid-template-columns:1fr 1fr;gap:1rem;">
                <div class="form-group">
                    <label class="form-label">Tanggal Mulai *</label>
                    <input type="text" id="picker_start"
                           value="{{ $dateStart }}"
                           class="form-control {{ $errors->has('program_date') ? 'is-invalid' : '' }}"
                           placeholder="dd/mm/yyyy HH:MM"
                           autocomplete="off" readonly>
                    @error('program_date') <div class="form-error">{{ $message }}</div> @enderror
                </div>
                <div class="form-group">
                    <label class="form-label">Tanggal Selesai *</label>
                    <input type="text" id="picker_end"
                           value="{{ $dateEnd }}"
                           class="form-control {{ $errors->has('program_until') ? 'is-invalid' : '' }}"
                           placeholder="dd/mm/yyyy HH:MM"
                           autocomplete="off" readonly>
                    @error('program_until') <div class="form-error">{{ $message }}</div> @enderror
                </div>
            </div>

            {{-- Lokasi with Google Places Autocomplete --}}
            <div class="form-group">
                <label class="form-label">Lokasi</label>
                <input type="text"
                       id="location_autocomplete"
                       name="location"
                       value="{{ old('location', $schedule?->location) }}"
                       class="form-control"
                       placeholder="Type venue name or address…"
                       autocomplete="off">
                <div class="maps-preview" id="mapsPreview">
                    <svg width="14" height="14" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" style="flex-shrink:0">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M15 10.5a3 3 0 11-6 0 3 3 0 016 0z"/>
                        <path stroke-linecap="round" stroke-linejoin="round" d="M19.5 10.5c0 7.142-7.5 11.25-7.5 11.25S4.5 17.642 4.5 10.5a7.5 7.5 0 1115 0z"/>
                    </svg>
                    <span>Maps: <a id="mapsPreviewLink" href="#" target="_blank"></a></span>
                    <button type="button" onclick="clearMaps()"
                        style="background:none;border:none;cursor:pointer;color:#6b6b7b;font-size:.75rem;margin-left:auto;padding:0;">
                        ✕ Clear
                    </button>
                </div>
                <div style="font-size:.75rem;color:#6b6b7b;margin-top:.35rem;">
                    Powered by Google Places — select a suggestion to auto-fill the Maps link.
                </div>
            </div>

            {{-- Detail --}}
            <div class="form-group">
                <label class="form-label">Detail / Catatan</label>
                <textarea name="event_detail" class="form-control" rows="3"
                          placeholder="Additional notes…">{{ old('event_detail', $schedule?->event_detail) }}</textarea>
            </div>

            {{-- Kebaktian Minggu --}}
            <label style="display:flex;align-items:center;gap:.65rem;padding:.65rem .9rem;border:1px solid var(--border);border-radius:var(--radius-sm);cursor:pointer;">
                <input type="checkbox" name="isSundayService" value="1" style="accent-color:var(--accent);"
                       {{ old('isSundayService', $schedule?->isSundayService) ? 'checked' : '' }}>
                <div>
                    <div style="font-weight:600;font-size:.875rem;">Kebaktian Minggu</div>
                    <div style="font-size:.75rem;color:var(--text-muted);">Mark this as a Sunday service</div>
                </div>
            </label>

        </div>
    </div>

    <div style="display:flex;gap:.75rem;margin-top:1.5rem;justify-content:flex-end;">
        <a href="{{ route('portal.schedule.index') }}" class="btn btn-secondary">Cancel</a>
        <button type="submit" class="btn btn-primary">{{ $isEdit ? 'Save Changes' : 'Create Schedule' }} →</button>
    </div>
</form>

{{-- ── Google Maps Places API ── --}}
{{--
    IMPORTANT: Replace YOUR_GOOGLE_MAPS_API_KEY below with your actual key.
    Enable these APIs in Google Cloud Console:
      • Maps JavaScript API
      • Places API
    Restrict key to your domain for security.
--}}
<script>
// Your API key — move this to .env and output via a Blade variable for production
const GMAPS_API_KEY = '{{ config("services.google_maps.key", env("GOOGLE_MAPS_API_KEY", "YOUR_GOOGLE_MAPS_API_KEY")) }}';
</script>
<script
    src="https://maps.googleapis.com/maps/api/js?key={{ config('services.google_maps.key', env('GOOGLE_MAPS_API_KEY', 'YOUR_GOOGLE_MAPS_API_KEY')) }}&libraries=places&callback=initPlacesAutocomplete"
    async defer>
</script>

<script>
// ── Flatpickr ────────────────────────────────────────────────────────────────
const pickerConfig = {
    enableTime: true,
    dateFormat: 'd/m/Y H:i',
    time_24hr:  true,
    allowInput: false,
    locale: {
        firstDayOfWeek: 1,
        weekdays: {
            shorthand: ['Min','Sen','Sel','Rab','Kam','Jum','Sab'],
            longhand:  ['Minggu','Senin','Selasa','Rabu','Kamis','Jumat','Sabtu'],
        },
        months: {
            shorthand: ['Jan','Feb','Mar','Apr','Mei','Jun','Jul','Agu','Sep','Okt','Nov','Des'],
            longhand:  ['Januari','Februari','Maret','April','Mei','Juni','Juli','Agustus','September','Oktober','November','Desember'],
        },
    },
};

flatpickr('#picker_start', {
    ...pickerConfig,
    onChange(dates) {
        if (dates.length) document.getElementById('program_date_iso').value = toIso(dates[0]);
    },
    onReady(_, __, fp) {
        const v = document.getElementById('program_date_iso').value;
        if (v) fp.setDate(v, false, 'Y-m-d H:i');
    },
});

flatpickr('#picker_end', {
    ...pickerConfig,
    onChange(dates) {
        if (dates.length) document.getElementById('program_until_iso').value = toIso(dates[0]);
    },
    onReady(_, __, fp) {
        const v = document.getElementById('program_until_iso').value;
        if (v) fp.setDate(v, false, 'Y-m-d H:i');
    },
});

function toIso(d) {
    const p = n => String(n).padStart(2, '0');
    return `${d.getFullYear()}-${p(d.getMonth()+1)}-${p(d.getDate())} ${p(d.getHours())}:${p(d.getMinutes())}`;
}

// ── Google Places Autocomplete ────────────────────────────────────────────────
function initPlacesAutocomplete() {
    const input = document.getElementById('location_autocomplete');

    const autocomplete = new google.maps.places.Autocomplete(input, {
        // Bias results toward Indonesia
        componentRestrictions: { country: 'id' },
        fields: ['name', 'formatted_address', 'geometry', 'place_id', 'url'],
    });

    // When user picks a suggestion
    autocomplete.addListener('place_changed', function () {
        const place = autocomplete.getPlace();

        if (!place.geometry) {
            // User pressed Enter without selecting — build a search URL from typed text
            const query = input.value;
            const searchUrl = 'https://www.google.com/maps/search/?api=1&query=' + encodeURIComponent(query);
            document.getElementById('maps_url_hidden').value = searchUrl;
            showMapsPreview(searchUrl);
            return;
        }

        // Use the place's own Google Maps URL if available, otherwise build one
        const mapsUrl = place.url
            || `https://www.google.com/maps/place/?q=place_id:${place.place_id}`;

        // Fill location name with the place name (not full address)
        input.value = place.name || place.formatted_address;

        document.getElementById('maps_url_hidden').value = mapsUrl;
        showMapsPreview(mapsUrl);
    });
}

function showMapsPreview(url) {
    const preview = document.getElementById('mapsPreview');
    const link    = document.getElementById('mapsPreviewLink');
    link.href        = url;
    link.textContent = url.length > 60 ? url.substring(0, 60) + '…' : url;
    preview.style.display = 'flex';
}

function clearMaps() {
    document.getElementById('maps_url_hidden').value     = '';
    document.getElementById('mapsPreview').style.display = 'none';
}

// Pre-fill preview if editing an existing record
window.addEventListener('DOMContentLoaded', function () {
    const existing = document.getElementById('maps_url_hidden').value;
    if (existing) showMapsPreview(existing);
});
</script>
