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
        (require database_path('migrations/2026_10_11_000002_add_ticket_event_maps_url.php'))->up();
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

    private function scheduleManager(): void
    {
        $user = new \App\Models\PortalUser(['name' => 'Manager', 'is_active' => true]);
        $user->id = 1;
        $user->setRelation('roles', collect([new \App\Models\Role(['name' => 'administrator'])]));
        $this->actingAs($user, 'portal');
        \Illuminate\Support\Facades\Storage::fake('local');
    }

    private function scheduleInput(array $data = []): array
    {
        return array_merge(['event_name' => 'New concert', 'event_type' => 'Konser',
            'program_date' => '2026-10-12 19:00', 'program_until' => '2026-10-12 21:00'], $data);
    }

    public function test_concert_requires_valid_image_but_service_does_not(): void
    {
        $this->scheduleManager();
        $this->post(route('portal.schedule.store'), $this->scheduleInput())->assertSessionHasErrors('event_image');
        foreach ([
            \Illuminate\Http\UploadedFile::fake()->create('poster.svg', 10, 'image/svg+xml'),
            \Illuminate\Http\UploadedFile::fake()->image('large.jpg')->size(4097),
        ] as $file) {
            $this->post(route('portal.schedule.store'), $this->scheduleInput(['event_image' => $file]))
                ->assertSessionHasErrors('event_image');
        }
        $this->assertSame(0, Schedule::count());
        $this->assertSame([], \Illuminate\Support\Facades\Storage::disk('local')->allFiles());
        $this->post(route('portal.schedule.store'), $this->scheduleInput(['event_type' => 'Pelayanan']))
            ->assertSessionHasNoErrors()->assertRedirect(route('portal.schedule.index'));
        $this->assertSame('', Schedule::firstOrFail()->event_image);
    }

    public function test_uploaded_image_is_displayed_and_retained_or_replaced_on_edit(): void
    {
        $this->scheduleManager();
        $this->post(route('portal.schedule.store'), $this->scheduleInput([
            'event_image' => \Illuminate\Http\UploadedFile::fake()->image('poster.jpg', 600, 900),
        ]))->assertSessionHasNoErrors();
        $schedule = Schedule::firstOrFail();
        $original = $schedule->event_image;
        \Illuminate\Support\Facades\Storage::disk('local')->assertExists($original);
        $this->get('/')->assertOk()->assertSee($schedule->image_url, false);
        $this->get($schedule->image_url)->assertOk()->assertHeader('Content-Type', 'image/jpeg');
        $this->put(route('portal.schedule.update', $schedule), $this->scheduleInput())->assertSessionHasNoErrors();
        $this->assertSame($original, $schedule->fresh()->event_image);
        $this->put(route('portal.schedule.update', $schedule), $this->scheduleInput([
            'event_image' => \Illuminate\Http\UploadedFile::fake()->image('replacement.png'),
        ]))->assertSessionHasNoErrors();
        $this->assertNotSame($original, $schedule->fresh()->event_image);
        $schedule->refresh();
        \Illuminate\Support\Facades\Storage::disk('local')->assertExists($schedule->event_image);
        $schedule->update(['rowstatus' => 0]);
        $this->get($schedule->image_url)->assertNotFound();
    }

    public function test_existing_concert_without_image_requires_upload_on_edit_and_legacy_image_is_preserved(): void
    {
        $this->scheduleManager();
        $schedule = $this->schedule();
        $this->put(route('portal.schedule.update', $schedule), $this->scheduleInput())->assertSessionHasErrors('event_image');
        $schedule->update(['event_image' => 'ticc.jpg']);
        $this->put(route('portal.schedule.update', $schedule), $this->scheduleInput())->assertSessionHasNoErrors();
        $this->assertSame(asset('assets/images/ticc.jpg'), $schedule->fresh()->image_url);
        $schedule->update(['event_image' => 'assets/images/ticc.jpg']);
        $this->assertSame(asset('assets/images/ticc.jpg'), $schedule->image_url);
    }

    public function test_schedule_form_supports_upload_and_existing_image_preview(): void
    {
        view()->share('errors', new \Illuminate\Support\ViewErrorBag());
        $this->view('portal.schedule._form', ['schedule' => null])
            ->assertSee('enctype="multipart/form-data"', false)
            ->assertSee('name="event_image"', false)->assertSee('Wajib untuk Konser');
        $schedule = $this->schedule(['event_image' => 'ticc.jpg']);
        $this->view('portal.schedule._form', ['schedule' => $schedule])
            ->assertSee($schedule->image_url, false)->assertSee('Gambar kegiatan saat ini');
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
        $mapsUrl = 'https://www.google.com/maps/place/?q=place_id:concert-hall';
        $ticket = $this->ticket(['thumbnail' => 'ticket-media/poster.png', 'maps_url' => $mapsUrl]);
        $response = $this->get('/')->assertOk()->assertSee('19:00 WIB')
            ->assertSee(route('tickets.thumbnail', $ticket), false)->assertSee(route('tickets.select', $ticket), false);
        foreach (['events', 'services'] as $section) {
            $this->assertSame('Ticket concert', $response->viewData($section)->sole()->event_name);
            $this->assertSame($mapsUrl, $response->viewData($section)->sole()->maps_url);
        }
        $dom = new \DOMDocument();
        @$dom->loadHTML($response->getContent());
        $xpath = new \DOMXPath($dom);
        $links = $xpath->query('//section[@id="regular-service"]//a[@href="'.$mapsUrl.'"]');
        $this->assertSame(1, $links->length);
        $this->assertSame('Concert Hall', trim($links->item(0)->textContent));
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
