const csrf = document.querySelector('meta[name="csrf-token"]')?.content;
const modal = document.querySelector('#reminderModal');
const form = document.querySelector('#reminderForm');
const alarmModal = document.querySelector('#alarmModal');
const days = ['Sunday', 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday'];
let activeAlarm = null;
let audioContext = null;
let alarmInterval = null;
let alarmsEnabled = localStorage.getItem('tempo-alarms-enabled') !== 'false';
const firedKeys = new Set();

function reminderDays(reminder) {
    const selected = Array.isArray(reminder.days) && reminder.days.length ? reminder.days : [reminder.day_of_week];
    return selected.map(Number).sort((a, b) => a - b);
}

function updateScheduleHint() {
    const selected = [...form.querySelectorAll('[name="days[]"]:checked')].map(input => Number(input.value));
    const labels = selected.map(day => days[day].slice(0, 3));
    document.querySelector('#scheduleHint').textContent = selected.length ? labels.join(', ') : 'Select at least one day';
}

function applySchedule(type) {
    const checkboxes = [...form.querySelectorAll('[name="days[]"]')];
    if (type === 'everyday') checkboxes.forEach(input => input.checked = true);
    if (type === 'working') checkboxes.forEach(input => input.checked = Number(input.value) >= 1 && Number(input.value) <= 5);
    document.querySelector('#customDays').classList.toggle('visible', type === 'custom');
    updateScheduleHint();
}

function setScheduleFromDays(selectedDays) {
    const normalized = selectedDays.map(Number).sort((a, b) => a - b);
    const type = normalized.join(',') === '0,1,2,3,4,5,6' ? 'everyday' : (normalized.join(',') === '1,2,3,4,5' ? 'working' : 'custom');
    form.querySelector(`[name="schedule_type"][value="${type}"]`).checked = true;
    form.querySelectorAll('[name="days[]"]').forEach(input => input.checked = normalized.includes(Number(input.value)));
    document.querySelector('#customDays').classList.toggle('visible', type === 'custom');
    updateScheduleHint();
}

function resetForm() {
    form.reset();
    form.action = '/reminders';
    document.querySelector('#methodField').innerHTML = '';
    document.querySelector('#modalTitle').textContent = 'New reminder';
    form.querySelector('[name="time"]').value = '09:00';
    form.querySelector('[name="melody"]').value = 'chime';
    form.querySelector('[name="color"][value="violet"]').checked = true;
    form.querySelector('[name="schedule_type"][value="working"]').checked = true;
    applySchedule('working');
}

document.querySelectorAll('[data-open-modal]').forEach(button => button.addEventListener('click', () => {
    resetForm();
    modal.showModal();
}));
document.querySelectorAll('[data-close-modal]').forEach(button => button.addEventListener('click', () => modal.close()));

document.querySelectorAll('[data-menu-button]').forEach(button => button.addEventListener('click', event => {
    event.stopPropagation();
    const menu = button.nextElementSibling;
    document.querySelectorAll('.card-menu').forEach(item => item !== menu && item.classList.remove('open'));
    menu.classList.toggle('open');
}));
document.addEventListener('click', () => document.querySelectorAll('.card-menu').forEach(menu => menu.classList.remove('open')));

document.querySelectorAll('[data-edit]').forEach(button => button.addEventListener('click', () => {
    const reminder = JSON.parse(button.closest('.reminder-card').dataset.reminder);
    resetForm();
    form.action = `/reminders/${reminder.id}`;
    document.querySelector('#methodField').innerHTML = '<input type="hidden" name="_method" value="PUT">';
    document.querySelector('#modalTitle').textContent = 'Edit reminder';
    ['title', 'notes', 'color', 'melody'].forEach(name => {
        const input = form.querySelector(`[name="${name}"]`);
        if (input.type === 'radio') form.querySelector(`[name="${name}"][value="${reminder[name]}"]`).checked = true;
        else input.value = reminder[name] ?? '';
    });
    setScheduleFromDays(reminderDays(reminder));
    form.querySelector('[name="time"]').value = reminder.time.slice(0, 5);
    modal.showModal();
}));

document.querySelectorAll('[data-toggle]').forEach(toggle => toggle.addEventListener('change', async () => {
    const card = toggle.closest('.reminder-card');
    const response = await fetch(`/reminders/${card.dataset.id}/toggle`, {method: 'PATCH', headers: {'X-CSRF-TOKEN': csrf, Accept: 'application/json'}});
    if (!response.ok) { toggle.checked = !toggle.checked; return; }
    const reminder = window.reminders.find(item => item.id === Number(card.dataset.id));
    reminder.is_active = toggle.checked;
    card.classList.toggle('muted', !toggle.checked);
    updateNext();
}));

function nextOccurrence(reminder, now = new Date()) {
    if (!reminder.is_active) return null;
    if (reminder.snoozed_until) {
        const snooze = new Date(reminder.snoozed_until);
        if (snooze > now) return snooze;
    }
    const [hour, minute] = reminder.time.split(':').map(Number);
    const selectedDays = reminderDays(reminder);
    for (let offset = 0; offset <= 7; offset++) {
        const target = new Date(now);
        target.setDate(now.getDate() + offset);
        target.setHours(hour, minute, 0, 0);
        if (selectedDays.includes(target.getDay()) && target > now) return target;
    }
    return null;
}

function updateNext() {
    const now = new Date();
    const upcoming = window.reminders.filter(r => r.is_active).map(r => ({reminder: r, date: nextOccurrence(r, now)})).sort((a, b) => a.date - b.date)[0];
    if (!upcoming) return;
    const milliseconds = Math.max(0, upcoming.date - now);
    const daysAway = Math.floor(milliseconds / 86400000);
    const hours = Math.floor((milliseconds % 86400000) / 3600000);
    const minutes = Math.floor((milliseconds % 3600000) / 60000);
    document.querySelector('#nextTitle').textContent = upcoming.reminder.title;
    document.querySelector('#nextMeta').textContent = `${days[upcoming.date.getDay()]} at ${upcoming.date.toLocaleTimeString([], {hour: '2-digit', minute: '2-digit'})}`;
    const seconds = Math.floor((milliseconds % 60000) / 1000);
    document.querySelector('#nextCountdown').textContent = `${daysAway}d ${hours}h ${minutes}m ${seconds}s`;
}

function playTone(frequency, offset, duration, type = 'sine', volume = .22) {
    audioContext ??= new (window.AudioContext || window.webkitAudioContext)();
    const oscillator = audioContext.createOscillator();
    const gain = audioContext.createGain();
    const start = audioContext.currentTime + offset;
    oscillator.type = type; oscillator.frequency.value = frequency;
    gain.gain.setValueAtTime(.0001, start);
    gain.gain.exponentialRampToValueAtTime(volume, start + .025);
    gain.gain.exponentialRampToValueAtTime(.0001, start + duration);
    oscillator.connect(gain); gain.connect(audioContext.destination);
    oscillator.start(start); oscillator.stop(start + duration + .03);
}

function playMelody(name = 'chime') {
    const melodies = {
        chime: [[659, 0, .42, 'sine'], [784, .38, .42, 'sine'], [988, .76, .75, 'sine']],
        gentle: [[523, 0, .6, 'sine'], [659, .5, .6, 'sine'], [784, 1, .8, 'sine']],
        digital: [[880, 0, .16, 'square', .12], [880, .25, .16, 'square', .12], [1047, .5, .28, 'square', .12]],
        classic: [[740, 0, .3, 'triangle'], [988, .34, .3, 'triangle'], [740, .68, .3, 'triangle'], [988, 1.02, .3, 'triangle']],
    };
    (melodies[name] || melodies.chime).forEach(tone => playTone(...tone));
}

function ring(reminder) {
    if (!alarmsEnabled) return;
    activeAlarm = reminder;
    document.querySelector('#alarmTitle').textContent = reminder.title;
    document.querySelector('#alarmNotes').textContent = reminder.notes || 'Your scheduled reminder is due.';
    alarmModal.showModal();
    playMelody(reminder.melody);
    alarmInterval = setInterval(() => playMelody(reminder.melody), 3000);
    if (Notification.permission === 'granted') new Notification(`Tempo: ${reminder.title}`, {body: reminder.notes || 'Your weekly reminder is due.', tag: `tempo-${reminder.id}`});
}

function checkAlarms() {
    if (!alarmsEnabled) return;
    const now = new Date();
    window.reminders.filter(r => r.is_active).forEach(reminder => {
        const snooze = reminder.snoozed_until ? new Date(reminder.snoozed_until) : null;
        const isSnoozeDue = snooze && Math.abs(now - snooze) < 60000;
        const [hour, minute] = reminder.time.split(':').map(Number);
        const isWeeklyDue = reminderDays(reminder).includes(now.getDay()) && hour === now.getHours() && minute === now.getMinutes() && (!snooze || snooze <= now);
        const key = `${reminder.id}-${now.getFullYear()}-${now.getMonth()}-${now.getDate()}-${now.getHours()}-${now.getMinutes()}`;
        if ((isSnoozeDue || isWeeklyDue) && !firedKeys.has(key)) { firedKeys.add(key); ring(reminder); }
    });
}

function stopAlarm() { clearInterval(alarmInterval); alarmInterval = null; alarmModal.close(); }
document.querySelector('#dismissButton')?.addEventListener('click', () => { if (activeAlarm) activeAlarm.snoozed_until = null; stopAlarm(); updateNext(); });
document.querySelector('#snoozeButton')?.addEventListener('click', async () => {
    const response = await fetch(`/reminders/${activeAlarm.id}/snooze`, {method: 'PATCH', headers: {'X-CSRF-TOKEN': csrf, 'Content-Type': 'application/json', Accept: 'application/json'}, body: JSON.stringify({minutes: 10})});
    if (response.ok) activeAlarm.snoozed_until = (await response.json()).snoozed_until;
    stopAlarm(); updateNext();
});

function updateAlarmToggle() {
    const button = document.querySelector('#alarmToggleButton');
    button.classList.toggle('enabled', alarmsEnabled);
    button.innerHTML = alarmsEnabled ? '<span>✓</span> Disable alarms' : '<span>♢</span> Enable alarms';
    button.title = alarmsEnabled ? 'Turn off reminder sounds and notifications' : 'Turn reminder sounds and notifications back on';
    const status = document.querySelector('.status-card small');
    if (status) status.textContent = alarmsEnabled ? 'Running in this browser' : 'Alarms are paused';
    document.querySelector('.status-dot')?.classList.toggle('paused', !alarmsEnabled);
}

document.querySelector('#alarmToggleButton')?.addEventListener('click', async () => {
    alarmsEnabled = !alarmsEnabled;
    localStorage.setItem('tempo-alarms-enabled', String(alarmsEnabled));
    updateAlarmToggle();
    if (alarmsEnabled) {
        audioContext ??= new (window.AudioContext || window.webkitAudioContext)();
        await audioContext.resume();
        if ('Notification' in window && Notification.permission === 'default') await Notification.requestPermission();
        checkAlarms();
    } else if (alarmModal.open) {
        stopAlarm();
    }
});

document.addEventListener('pointerdown', async () => {
    if (!alarmsEnabled) return;
    audioContext ??= new (window.AudioContext || window.webkitAudioContext)();
    if (audioContext.state === 'suspended') await audioContext.resume();
}, {once: true});

document.querySelector('#previewMelody')?.addEventListener('click', async () => {
    audioContext ??= new (window.AudioContext || window.webkitAudioContext)();
    await audioContext.resume();
    playMelody(form.querySelector('[name="melody"]').value);
});

form.querySelectorAll('[name="schedule_type"]').forEach(input => input.addEventListener('change', () => applySchedule(input.value)));
form.querySelectorAll('[name="days[]"]').forEach(input => input.addEventListener('change', () => {
    form.querySelector('[name="schedule_type"][value="custom"]').checked = true;
    document.querySelector('#customDays').classList.add('visible');
    updateScheduleHint();
}));

function updateComputerClock() {
    const now = new Date();
    document.querySelector('#computerTime').textContent = now.toLocaleTimeString([], {hour: '2-digit', minute: '2-digit', second: '2-digit'});
    const zone = Intl.DateTimeFormat().resolvedOptions().timeZone || 'Local timezone';
    const offset = -now.getTimezoneOffset();
    const sign = offset >= 0 ? '+' : '-';
    const hours = String(Math.floor(Math.abs(offset) / 60)).padStart(2, '0');
    const minutes = String(Math.abs(offset) % 60).padStart(2, '0');
    document.querySelector('#computerZone').textContent = `${zone} (UTC${sign}${hours}:${minutes})`;
}

updateAlarmToggle();
updateComputerClock();
updateNext(); checkAlarms();
setInterval(() => { updateComputerClock(); updateNext(); checkAlarms(); }, 1000);
