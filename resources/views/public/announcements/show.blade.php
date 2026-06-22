@extends('public.layouts.app')

@section('title', $announcement->title)
@section('meta_description', $announcement->summary ?? \Illuminate\Support\Str::limit(strip_tags($announcement->content), 150))

@section('content')
<section class="pub-section">
    <div class="pub-container">
        <article class="pub-article">
            <a href="{{ route('public.announcements.index') }}" class="pub-back">← Volver a anuncios</a>

            <div class="pub-ann__meta">
                @if($announcement->is_pinned)
                    <span class="pub-pin">📌 Destacado</span>
                @endif
                <span class="pub-ann__date">
                    {{ optional($announcement->published_at)->translatedFormat('d \d\e F \d\e Y') ?? '' }}
                </span>
            </div>

            <h1>{{ $announcement->title }}</h1>

            @if($announcement->summary)
                <p class="pub-lead">{{ $announcement->summary }}</p>
            @endif

            <div class="pub-article__content">{{ $announcement->content }}</div>
        </article>
    </div>
</section>
@endsection
