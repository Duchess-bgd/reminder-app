<?php

namespace Tests\Feature;

use App\Models\Reminder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ReminderTest extends TestCase
{
    use RefreshDatabase;

    public function test_a_multiple_day_reminder_can_be_created(): void
    {
        $this->post('/reminders', [
            'title' => 'Weekly report', 'notes' => 'Send it', 'days' => [1, 3, 5],
            'time' => '16:30', 'color' => 'violet', 'melody' => 'gentle',
        ])->assertRedirect('/');

        $reminder = Reminder::where('title', 'Weekly report')->firstOrFail();
        $this->assertSame([1, 3, 5], $reminder->days);
        $this->assertSame(1, $reminder->day_of_week);
        $this->assertSame('gentle', $reminder->melody);
    }

    public function test_every_day_can_be_saved(): void
    {
        $this->post('/reminders', [
            'title' => 'Daily check', 'days' => [0, 1, 2, 3, 4, 5, 6],
            'time' => '08:00', 'color' => 'emerald', 'melody' => 'chime',
        ])->assertRedirect('/');

        $this->assertSame([0, 1, 2, 3, 4, 5, 6], Reminder::where('title', 'Daily check')->firstOrFail()->days);
    }

    public function test_a_reminder_can_be_toggled_and_snoozed(): void
    {
        $reminder = Reminder::create(['title' => 'Standup', 'day_of_week' => 1, 'days' => [1, 2, 3, 4, 5], 'time' => '09:00', 'color' => 'blue', 'melody' => 'digital']);
        $this->patchJson("/reminders/{$reminder->id}/toggle")->assertOk()->assertJson(['is_active' => false]);
        $this->patchJson("/reminders/{$reminder->id}/snooze", ['minutes' => 10])->assertOk();
        $this->assertNotNull($reminder->fresh()->snoozed_until);
    }
}
