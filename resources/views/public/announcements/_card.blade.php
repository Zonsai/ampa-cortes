<a href="{{ route('public.announcements.show', $announcement) }}" class="pub-ann">
    <div class="pub-ann__meta">
        @if($announcement->is_pinned)
            <span class="pub-pin">📌 Destacado</span>
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
