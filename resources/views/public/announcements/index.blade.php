@extends('public.layouts.app')

@section('title', 'Anuncios')
@section('meta_description', 'Tablón de anuncios del AMPA: novedades, actividades y comunicaciones para las familias.')

@section('content')
<section class="pub-section">
    <div class="pub-container">
        <div class="pub-section__head">
            <span class="pub-eyebrow">Tablón</span>
            <h1>Anuncios</h1>
            <p class="pub-lead">Novedades y comunicaciones del AMPA.</p>
        </div>

        @if($announcements->isEmpty())
            <div class="pub-empty">Aún no hay anuncios publicados. Vuelve pronto.</div>
        @else
            <div class="pub-grid pub-grid--2">
                @foreach($announcements as $announcement)
                    @include('public.announcements._card', ['announcement' => $announcement])
                @endforeach
            </div>
        @endif
    </div>
</section>
@endsection
