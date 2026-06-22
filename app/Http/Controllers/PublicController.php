<?php

namespace App\Http\Controllers;

use App\Enums\ActivityStatus;
use App\Models\AcademicYear;
use App\Models\Announcement;
use App\Models\ExtracurricularActivity;
use Illuminate\View\View;

class PublicController extends Controller
{
    public function home(): View
    {
        $announcements = Announcement::query()->publiclyVisible()->ordered()->limit(3)->get();

        return view('public.home', compact('announcements'));
    }

    public function about(): View
    {
        return view('public.about');
    }

    public function extracurriculars(): View
    {
        $activeYear = AcademicYear::query()->where('is_active', true)->first();

        $activities = collect();
        if ($activeYear) {
            $activities = ExtracurricularActivity::query()
                ->where('academic_year_id', $activeYear->id)
                ->where('status', ActivityStatus::Published)
                ->where('is_visible_for_families', true)
                ->orderBy('name')
                ->get();
        }

        return view('public.extracurriculars', compact('activeYear', 'activities'));
    }

    public function announcements(): View
    {
        $announcements = Announcement::query()->publiclyVisible()->ordered()->get();

        return view('public.announcements.index', compact('announcements'));
    }

    public function announcement(Announcement $announcement): View
    {
        // Implicit binding resolves by slug; only publicly visible ones are shown.
        abort_unless(
            Announcement::query()->publiclyVisible()->whereKey($announcement->getKey())->exists(),
            404
        );

        return view('public.announcements.show', compact('announcement'));
    }

    public function contact(): View
    {
        return view('public.contact');
    }
}
