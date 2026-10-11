<?php

namespace Tests\Feature;

use App\Models\Schedule;
use App\Models\TicketEvent;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class HomeScheduleTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        config(['database.default' => 'sqlite', 'database.connections.sqlite.database' => ':memory:']);
        DB::purge('sqlite');
        $this->travelTo(\Carbon\Carbon::parse('2026-10-11 05:00:00', 'UTC'));
        (require database_path('migrations/2024_03_31_125816_schedule.php'))->up();
        Schema::table('schedule', function (Blueprint $table) {
            $table->string('event_type')->nullable();
            $table->string('location')->nullable();
            $table->string('maps_url')->nullable();
        });
        (require database_path('migrations/2025_01_07_172706_past_events.php'))->up();
        (require database_path('migrations/2026_10_07_000001_create_ticketing_tables.php'))->up();
        (require database_path('migrations/2026_10_08_000003_add_ticket_row_status.php'))->up();
    }

    private function schedule(array $data = []): Schedule
    {
        return Schedule::create(array_merge([
            'event_name' => 'Portal concert', 'event_type' => 'Konser',
            'program_date' => '2026-10-12 19:00:00', 'program_until' => '2026-10-12 21:00:00',
            'event_detail' => 'Choir concert', 'event_image' => '', 'event_imageCaptionUrl' => '',
            'isSundayService' => false, 'rowstatus' => 1, 'createdBy' => 'Test', 'modifiedBy' => 'Test',
        ], $data));
    }

    private function ticket(array $data = []): TicketEvent
    {
        return TicketEvent::create(array_merge([
            'title' => 'Ticket concert', 'starts_at' => '2026-10-12 12:00:00',
            'location' => 'Concert Hall', 'seating_type' => 'free', 'published' => true,
        ], $data));
    }

    public function test_concerts_and_services_are_visible_without_sunday_checkbox_and_without_hidden_limits(): void
    {
        for ($i = 0; $i < 7; $i++) $this->schedule(['event_name' => 'Concert '.$i]);
        $this->schedule(['event_name' => 'Sunday service', 'event_type' => 'Pelayanan', 'isSundayService' => true,
            'program_date' => '2026-10-11 08:00:00']);
        $this->schedule(['event_name' => 'Removed schedule', 'rowstatus' => 0]);
        $this->schedule(['event_name' => 'Old schedule', 'program_date' => '2026-10-10 19:00:00']);
        $response = $this->get('/')->assertOk()->assertSee('Concert 6')->assertSee('Sunday service')
            ->assertDontSee('Removed schedule')->assertDontSee('Old schedule');
        $this->assertCount(7, $response->viewData('events'));
        $this->assertCount(8, $response->viewData('services'));
        $this->assertSame('Sunday service', $response->viewData('services')->first()->event_name);
    }

    public function test_ticketing_is_live_in_both_sections_with_local_time_media_and_links(): void
    {
        $ticket = $this->ticket(['thumbnail' => 'ticket-media/poster.png']);
        $response = $this->get('/')->assertOk()->assertSee('19:00 WIB')
            ->assertSee(route('tickets.thumbnail', $ticket), false)->assertSee(route('tickets.select', $ticket), false);
        foreach (['events', 'services'] as $section) {
            $this->assertSame('Ticket concert', $response->viewData($section)->sole()->event_name);
        }
        $ticket->update(['title' => 'Renamed concert', 'location' => 'New Hall', 'starts_at' => '2026-10-13 13:00:00']);
        $this->get('/')->assertOk()->assertSee('Renamed concert')->assertSee('New Hall')->assertSee('20:00 WIB')->assertDontSee('Ticket concert');
        $ticket->update(['published' => false]);
        $this->get('/')->assertOk()->assertDontSee('Renamed concert');
        $ticket->update(['published' => true, 'RowStatus' => 1]);
        $this->get('/')->assertOk()->assertDontSee('Renamed concert');
    }

    public function test_drafts_past_and_deleted_tickets_are_hidden_and_matching_manual_entry_is_deduplicated(): void
    {
        $this->ticket(['title' => 'Draft ticket', 'published' => false]);
        $this->ticket(['title' => 'Past ticket', 'starts_at' => '2026-10-10 12:00:00']);
        $this->ticket(['title' => 'Deleted ticket', 'RowStatus' => 1]);
        $ticket = $this->ticket(['title' => 'Portal concert']);
        $this->schedule();
        $response = $this->get('/')->assertOk()->assertDontSee('Draft ticket')->assertDontSee('Past ticket')->assertDontSee('Deleted ticket');
        foreach (['events', 'services'] as $section) {
            $this->assertSame(route('tickets.select', $ticket), $response->viewData($section)->sole()->ticket_url);
        }
    }
}
