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
            @php
                $activityEnrollments = $familyEnrollmentsByActivity->get($activity->id, collect());
                $prices = $activity->activityGroups
                    ->pluck('price_member')
                    ->filter(fn ($p) => $p !== null && (float) $p > 0)
                    ->sort()
                    ->values();
                $minPrice = $prices->first();
                $maxPrice = $prices->last();
            @endphp
            <div class="flex flex-col bg-white rounded-xl border border-gray-200 overflow-hidden hover:border-indigo-300 hover:shadow-sm transition-all">
                <div class="flex-1 p-5">
                    <div class="flex items-start justify-between gap-2 mb-2">
                        <h2 class="font-semibold text-gray-900">{{ $activity->name }}</h2>
                        @if($activity->activity_groups_count > 0)
                            <span class="shrink-0 text-xs bg-indigo-50 text-indigo-700 px-2 py-0.5 rounded-full">{{ $activity->activity_groups_count }} {{ $activity->activity_groups_count === 1 ? 'grupo' : 'grupos' }}</span>
                        @endif
                    </div>

                    @if($activity->short_description)
                        <p class="text-sm text-gray-500 mb-3 line-clamp-2">{{ $activity->short_description }}</p>
                    @endif

                    <div class="flex flex-wrap items-center gap-2 mb-3">
                        @if($activity->requires_ampa_membership)
                            <span class="inline-flex items-center text-xs bg-amber-50 text-amber-700 border border-amber-200 px-2 py-0.5 rounded-full">
                                Solo socios/as AMPA
                            </span>
                        @endif

                        @if($minPrice !== null)
                            <span class="text-xs text-gray-500">
                                @if((float) $minPrice === (float) $maxPrice)
                                    {{ number_format($minPrice, 2, ',', '.') }} €/mes (socio)
                                @else
                                    {{ number_format($minPrice, 2, ',', '.') }}–{{ number_format($maxPrice, 2, ',', '.') }} €/mes (socio)
                                @endif
                            </span>
                        @endif
                    </div>

                    @if($activityEnrollments->isNotEmpty())
                        <div class="space-y-1">
                            @foreach($activityEnrollments as $enr)
                                <div class="flex items-center gap-2 text-xs">
                                    <span class="text-gray-600">{{ $enr->student->first_name }}:</span>
                                    <span class="font-medium
                                        @if(in_array($enr->status->value, ['enrolled', 'paid'])) text-green-700
                                        @elseif($enr->status->value === 'pending_payment') text-blue-700
                                        @elseif($enr->status->value === 'waitlist') text-amber-700
                                        @else text-gray-500
                                        @endif">
                                        {{ $enr->status->label() }}
                                    </span>
                                </div>
                            @endforeach
                        </div>
                    @endif
                </div>

                <div class="px-5 pb-5">
                    <a href="{{ route('familia.activities.show', $activity) }}"
                       class="block w-full text-center bg-indigo-600 hover:bg-indigo-700 text-white text-sm font-medium px-4 py-2 rounded-lg transition-colors">
                        Ver grupos y apuntarse →
                    </a>
                </div>
            </div>
            @endforeach
        </div>
    @endif
@endif

@endsection
