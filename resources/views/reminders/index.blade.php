<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Tempo — Weekly reminders</title>
    <link rel="stylesheet" href="{{ route('assets.css') }}">
    <style>.enable-button{border:0;border-radius:9px;padding:11px 14px;background:#f1eef9;color:#665e81;font:600 12px 'Segoe UI',sans-serif;cursor:pointer}.enable-button span{margin-right:5px}.enable-button.enabled{background:#e7f7ef;color:#287a58}.computer-clock{text-align:right;padding-right:3px}.computer-clock strong,.computer-clock small{display:block}.computer-clock strong{font-size:15px;font-variant-numeric:tabular-nums}.computer-clock small{font-size:9px;color:#8b8799;margin-top:2px;max-width:170px}.melody-row{display:grid;grid-template-columns:1fr auto;gap:8px;align-items:end}.preview-button{height:40px;padding:0 13px;border:1px solid #d9d4e6;border-radius:8px;background:#f6f3fb;color:#6652ad;font:600 11px 'Segoe UI',sans-serif;cursor:pointer}.local-time-note{display:block;margin-top:5px;color:#938e9f;font-size:9px;font-weight:400}.schedule-options{display:grid;grid-template-columns:repeat(3,1fr);gap:7px;margin-top:9px}.schedule-option{position:relative;margin:0}.schedule-option input{position:absolute;opacity:0}.schedule-option span{display:block;padding:10px 6px;text-align:center;border:1px solid #ddd9e6;border-radius:8px;color:#716c80;font-size:11px;cursor:pointer}.schedule-option input:checked+span{border-color:#7357d9;background:#efebfb;color:#5d43c0;font-weight:700}.custom-days{display:none;grid-template-columns:repeat(7,1fr);gap:6px;margin-top:9px}.custom-days.visible{display:grid}.day-choice{position:relative;margin:0}.day-choice input{position:absolute;opacity:0}.day-choice span{display:grid;place-items:center;height:34px;border:1px solid #ddd9e6;border-radius:8px;color:#777183;font-size:10px;cursor:pointer}.day-choice input:checked+span{background:#7357d9;border-color:#7357d9;color:#fff}.schedule-hint{display:block;margin-top:7px;color:#938e9f;font-size:9px}@media(max-width:700px){.computer-clock{display:none}}</style>
    <style>.status-dot.paused{background:#aaa5b5;box-shadow:0 0 0 4px #454252}</style>
</head>
<body>
<div class="app-shell">
    <aside class="sidebar">
        <a class="brand" href="/" aria-label="Tempo home"><span class="brand-mark">T</span><span>tempo</span></a>
        <nav>
            <a class="nav-item active" href="#"><span>◷</span> Reminders</a>
            <a class="nav-item" href="#schedule"><span>⌗</span> Schedule</a>
        </nav>
        <div class="sidebar-bottom">
            <div class="status-card"><span class="status-dot"></span><div><strong>Alarm service</strong><small>Running in this browser</small></div></div>
            <p>Keep this page open for alarms.<br>Windows notifications may appear even when minimized.</p>
        </div>
    </aside>

    <main>
        <header class="topbar">
            <div><p class="eyebrow">WEEKLY RHYTHM</p><h1>Your reminders</h1></div>
            <div class="header-actions"><div class="computer-clock"><strong id="computerTime">--:--:--</strong><small id="computerZone">Computer time</small></div><button class="enable-button enabled" id="alarmToggleButton" title="Turn off reminder sounds and notifications"><span>✓</span> Disable alarms</button><button class="primary-button" data-open-modal><span>＋</span> New reminder</button></div>
        </header>

        <section class="content">
            @if(session('success'))<div class="toast-success">✓ {{ session('success') }}</div>@endif
            @if($errors->any())<div class="error-box">{{ $errors->first() }}</div>@endif

            <div class="hero">
                <div><p class="hero-label">COMING UP NEXT</p><h2 id="nextTitle">Your week is clear</h2><p id="nextMeta">Create a reminder to get started.</p></div>
                <div class="hero-time"><span id="nextCountdown">—</span><small>until alarm</small></div>
                <div class="orb orb-one"></div><div class="orb orb-two"></div>
            </div>

            <div class="section-heading" id="schedule"><div><h2>Weekly schedule</h2><p>{{ $reminders->where('is_active', true)->count() }} active {{ Str::plural('reminder', $reminders->where('is_active', true)->count()) }}</p></div><div class="view-toggle"><button class="selected">List</button><button>Week</button></div></div>

            <div class="reminder-list" id="reminderList">
                @forelse($reminders as $reminder)
                    @php($days = ['Sunday','Monday','Tuesday','Wednesday','Thursday','Friday','Saturday'])
                    @php($selectedDays = $reminder->days ?: [$reminder->day_of_week])
                    @php($dayLabel = $selectedDays === [0,1,2,3,4,5,6] ? 'Every day' : ($selectedDays === [1,2,3,4,5] ? 'Working days' : collect($selectedDays)->map(fn($day) => substr($days[$day], 0, 3))->implode(', ')))
                    @php($badgeLabel = count($selectedDays) === 1 ? strtoupper(substr($days[$selectedDays[0]], 0, 3)) : (count($selectedDays) === 7 ? 'DAILY' : count($selectedDays).' DAYS'))
                    <article class="reminder-card {{ $reminder->is_active ? '' : 'muted' }}" data-reminder='@json($reminder)' data-id="{{ $reminder->id }}">
                        <div class="color-bar color-{{ $reminder->color }}"></div>
                        <div class="day-badge"><strong>{{ $badgeLabel }}</strong><span>{{ date('H:i', strtotime($reminder->time)) }}</span></div>
                        <div class="reminder-info"><h3>{{ $reminder->title }}</h3><p>{{ $reminder->notes ?: 'No notes added' }}</p></div>
                        <div class="weekly-pill">↻ {{ $dayLabel }}</div>
                        <label class="switch" title="Toggle reminder"><input type="checkbox" data-toggle {{ $reminder->is_active ? 'checked' : '' }}><span></span></label>
                        <button class="more-button" data-menu-button>•••</button>
                        <div class="card-menu"><button data-edit>Edit</button><form method="POST" action="{{ route('reminders.destroy', $reminder) }}">@csrf @method('DELETE')<button class="danger">Delete</button></form></div>
                    </article>
                @empty
                    <div class="empty-state"><div class="empty-icon">◷</div><h3>No reminders yet</h3><p>Build a calmer week, one gentle nudge at a time.</p><button class="secondary-button" data-open-modal>Create your first reminder</button></div>
                @endforelse
            </div>
        </section>
    </main>
</div>

<dialog id="reminderModal">
    <form method="POST" id="reminderForm" action="{{ route('reminders.store') }}">
        @csrf <div id="methodField"></div>
        <div class="modal-header"><div><p class="eyebrow">WEEKLY ALARM</p><h2 id="modalTitle">New reminder</h2></div><button type="button" class="close-button" data-close-modal>×</button></div>
        <label>Reminder title<input name="title" required maxlength="120" placeholder="e.g. Submit weekly report"></label>
        <label>Notes <span>Optional</span><textarea name="notes" maxlength="1000" placeholder="Add a little context…"></textarea></label>
        <fieldset><legend>Repeat on</legend><div class="schedule-options"><label class="schedule-option"><input type="radio" name="schedule_type" value="everyday"><span>Every day</span></label><label class="schedule-option"><input type="radio" name="schedule_type" value="working" checked><span>Working days</span></label><label class="schedule-option"><input type="radio" name="schedule_type" value="custom"><span>Custom days</span></label></div><div class="custom-days" id="customDays">@foreach(['Sun','Mon','Tue','Wed','Thu','Fri','Sat'] as $index => $day)<label class="day-choice"><input type="checkbox" name="days[]" value="{{ $index }}" {{ $index >= 1 && $index <= 5 ? 'checked' : '' }}><span>{{ $day }}</span></label>@endforeach</div><small class="schedule-hint" id="scheduleHint">Monday through Friday</small></fieldset>
        <div class="form-row"><label>Computer time<input name="time" type="time" required value="09:00"><small class="local-time-note">Uses this computer's local time</small></label><div></div></div>
        <label>Alarm melody<div class="melody-row"><select name="melody" required><option value="chime">Bright chime</option><option value="gentle">Gentle morning</option><option value="digital">Digital pulse</option><option value="classic">Classic alarm</option></select><button type="button" class="preview-button" id="previewMelody">▶ Preview</button></div></label>
        <fieldset><legend>Color</legend><div class="colors">@foreach(['violet','blue','amber','rose','emerald'] as $color)<label class="color-choice color-{{ $color }}"><input type="radio" name="color" value="{{ $color }}" {{ $color === 'violet' ? 'checked' : '' }}><span></span></label>@endforeach</div></fieldset>
        <div class="modal-note">🔔 The alarm repeats every week. Keep Tempo open or minimized on your Windows computer.</div>
        <div class="modal-actions"><button type="button" class="text-button" data-close-modal>Cancel</button><button class="primary-button" type="submit">Save reminder</button></div>
    </form>
</dialog>

<dialog id="alarmModal" class="alarm-dialog">
    <div class="alarm-content"><div class="alarm-rings"><span></span><span></span><div>◷</div></div><p class="eyebrow">REMINDER</p><h2 id="alarmTitle">Time’s up</h2><p id="alarmNotes"></p><div class="alarm-actions"><button class="secondary-button" id="snoozeButton">Snooze 10 min</button><button class="primary-button" id="dismissButton">Dismiss</button></div></div>
</dialog>

<script>window.reminders = @json($reminders);</script>
<script src="{{ route('assets.js') }}"></script>
</body>
</html>
