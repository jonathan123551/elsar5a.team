<?php

namespace Tests\Feature;

use App\Models\Show;
use App\Models\ShowTime;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BookingPageTest extends TestCase
{
    use RefreshDatabase;

    public function test_public_booking_page_renders_for_available_show_time(): void
    {
        $show = Show::create([
            'title' => 'Smoke Show',
            'description' => 'Smoke test show',
            'is_active' => true,
        ]);

        $showTime = ShowTime::create([
            'show_id' => $show->id,
            'date' => '2026-06-01',
            'time' => '19:00:00',
            'ticket_price' => 100,
            'total_tickets' => 10,
            'available_tickets' => 10,
            'is_sold_out' => false,
        ]);

        $this->get(route('bookings.create', $showTime))
            ->assertOk()
            ->assertViewIs('bookings.create');
    }
}
