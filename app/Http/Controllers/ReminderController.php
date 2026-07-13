<?php

namespace App\Http\Controllers;

use App\Models\Reminder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ReminderController extends Controller
{
    public function index(): View
    {
        return view('reminders.index', [
            'reminders' => Reminder::query()->orderBy('day_of_week')->orderBy('time')->get(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        Reminder::create($this->validated($request));

        return back()->with('success', 'Reminder created.');
    }

    public function update(Request $request, Reminder $reminder): RedirectResponse
    {
        $reminder->update($this->validated($request));

        return back()->with('success', 'Reminder updated.');
    }

    public function destroy(Reminder $reminder): RedirectResponse
    {
        $reminder->delete();

        return back()->with('success', 'Reminder deleted.');
    }

    public function toggle(Reminder $reminder): JsonResponse
    {
        $reminder->update(['is_active' => ! $reminder->is_active]);

        return response()->json(['is_active' => $reminder->is_active]);
    }

    public function snooze(Request $request, Reminder $reminder): JsonResponse
    {
        $data = $request->validate(['minutes' => ['required', 'integer', 'in:5,10,15,30']]);
        $reminder->update(['snoozed_until' => now()->addMinutes($data['minutes'])]);

        return response()->json(['snoozed_until' => $reminder->snoozed_until?->toIso8601String()]);
    }

    private function validated(Request $request): array
    {
        $data = $request->validate([
            'title' => ['required', 'string', 'max:120'],
            'notes' => ['nullable', 'string', 'max:1000'],
            'days' => ['required', 'array', 'min:1', 'max:7'],
            'days.*' => ['required', 'integer', 'between:0,6', 'distinct'],
            'time' => ['required', 'date_format:H:i'],
            'color' => ['required', 'in:violet,blue,amber,rose,emerald'],
            'melody' => ['required', 'in:chime,gentle,digital,classic'],
        ]);

        $data['days'] = array_values(array_unique(array_map('intval', $data['days'])));
        sort($data['days']);
        $data['day_of_week'] = $data['days'][0];

        return $data;
    }
}
