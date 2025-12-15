<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Show;
use App\Models\ShowTime;

class ShowSeeder extends Seeder
{
    public function run(): void
    {
        $show = Show::create([
            'title' => 'مسرحية الصرخة',
            'description' => 'عرض مسرحي تجريبي لفريق الصرخة.',
            'poster_path' => null,
            'is_active' => true,
        ]);

        // نضيف شوية مواعيد
        ShowTime::create([
            'show_id' => $show->id,
            'date' => now()->addDays(3)->toDateString(),
            'time' => '19:00:00',
            'total_tickets' => 50,
            'available_tickets' => 50,
            'is_sold_out' => false,
        ]);

        ShowTime::create([
            'show_id' => $show->id,
            'date' => now()->addDays(4)->toDateString(),
            'time' => '21:00:00',
            'total_tickets' => 50,
            'available_tickets' => 50,
            'is_sold_out' => false,
        ]);
    }
}
