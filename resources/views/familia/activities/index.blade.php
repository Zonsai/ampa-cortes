@extends('familia.layouts.app')

@section('title', 'Extraescolares')

@section('content')

<h1 class="text-2xl font-bold text-gray-900 mb-2">Actividades extraescolares</h1>

@if(! $activeYear)
    <div class="mt-4 rounded-lg bg-amber-50 border border-amber-200 px-4 py-4 text-amber-800 text-sm">
        <strong>No hay curso académico activo.</strong>
        El AMPA aún no ha configurado el curso activo. Vuelve a consultar más adelante o contacta con el AMPA.
    </div>
@else
    <p class="text-gray-500 text-sm mb-6">Curso {{ $activeYear->name }}</p>

    @if($activities->isEmpty())
        <div class="bg-white rounded-xl border border-gray-200 px-6 py-10 text-center text-gray-500">
            No hay actividades disponibles en este momento.
        </div>
    @else
        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
            @foreach($activities as $activity)
            <a href="{{ route('familia.activities.show', $activity) }}"
               class="block bg-white rounded-xl border border-gray-200 p-4 hover:border-indigo-300 hover:shadow-sm transition-all group">
                <div class="flex items-start justify-between gap-2">
                    <div>
                        <div class="font-semibold text-gray-900 group-hover:text-indigo-700">
                            {{ $activity->name }}
                        </div>
                        @if($activity->description)
                            <div class="text-sm text-gray-500 mt-1 line-clamp-2">
                                {{ $activity->description }}
                            </div>
                        @endif
                    </div>
                    @if($activity->activity_groups_count > 0)
                        <span class="shrink-0 text-xs bg-indigo-50 text-indigo-700 px-2 py-0.5 rounded-full">
                            {{ $activity->activity_groups_count }}
                            {{ $activity->activity_groups_count === 1 ? 'grupo' : 'grupos' }}
                        </span>
                    @endif
                </div>
                @if($activity->requires_ampa_membership)
                    <div class="mt-2 text-xs text-amber-700">
                        Requiere ser socio/a del AMPA
                    </div>
                @endif
            </a>
            @endforeach
        </div>
    @endif
@endif

@endsection
