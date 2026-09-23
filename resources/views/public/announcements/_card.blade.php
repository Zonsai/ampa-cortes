<a href="{{ route('public.announcements.show', $announcement) }}" class="pub-ann">
    <div class="pub-ann__meta">
        @if($announcement->is_pinned)
            <span class="pub-pin">
                <svg viewBox="0 0 20 20" fill="none" xmlns="http://www.w3.org/2000/svg" aria-hidden="true"><path d="M10 2.5v3M6 12.5h8l-1.3-4.2a2 2 0 0 0-1.9-1.4h-1.6a2 2 0 0 0-1.9 1.4L6 12.5z" stroke="currentColor" stroke-width="1.4" stroke-linejoin="round"/><path d="M10 12.5v5" stroke="currentColor" stroke-width="1.4" stroke-linecap="round"/></svg>
                Destacado
            </span>
        @endif
        <span class="pub-ann__date">
            {{ optional($announcement->published_at)->translatedFormat('d \d\e F \d\e Y') ?? '' }}
        </span>
    </div>
    <h3 class="pub-ann__title">{{ $announcement->title }}</h3>
    @if($announcement->summary)
        <p class="pub-ann__summary">{{ \Illuminate\Support\Str::limit($announcement->summary, 140) }}</p>
    @endif
</a>
