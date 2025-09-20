<ul class="list-group list-group-flush">
    @foreach($tracks as $track)
        <li class="list-group-item border-0 px-0 py-3">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <h5 class="mb-1">{{ $track['title'] }}</h5>
                    <small class="text-muted">{{ $track['composer'] }}</small>
                </div>
                <a href="{{ $track['url'] }}" class="btn btn-outline-secondary btn-sm popup-vimeo">Play <i class="fas fa-play ms-1"></i></a>
            </div>
        </li>
    @endforeach
</ul>