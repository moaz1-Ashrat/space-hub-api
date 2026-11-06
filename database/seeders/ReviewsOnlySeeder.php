<?php

namespace Database\Seeders;

use App\Models\Booking;
use App\Models\Review;
use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class ReviewsOnlySeeder extends Seeder
{
    private array $comments = ['Excellent space, highly recommended!','Great experience overall. Will book again.','The location was perfect and the staff was friendly.','Very clean and well-maintained.','Good value for money.','Amazing atmosphere, loved every minute!','Decent space but could use some improvements.','Perfect for our team meeting.','Exactly as described. Very satisfied.','Beautiful venue, will definitely return.','A bit pricey but worth it.','The equipment was top-notch.','Fantastic experience from start to finish.','Really enjoyed our time here.','Not bad, but a few issues with the AC.'];

    public function run(): void
    {
        DB::table('reviews')->truncate();

        $bookings = Booking::where('booking_status', 'completed')->get();
        $seen = [];
        $count = 0;

        foreach ($bookings as $booking) {
            $key = $booking->user_id . '-' . $booking->space_id;
            if (isset($seen[$key])) continue;
            if (rand(1, 100) > 72) continue;

            Review::create([
                'customer_id' => $booking->user_id,
                'space_id' => $booking->space_id,
                'rating' => rand(3, 5),
                'comment' => $this->comments[array_rand($this->comments)],
                'review_date' => Carbon::parse($booking->booking_date)->addDays(rand(1, 14))->toDateString(),
            ]);
            $seen[$key] = true;
            $count++;
        }

        $this->command->info("Reviews created: {$count}");
    }
}
