<?php

namespace Database\Seeders;

use App\Models\CollectionJob;
use App\Models\Earning;
use App\Models\User;
use App\Models\WastePost;
use Illuminate\Database\Seeder;

class DataSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Create admin users (always active)
        $admins = User::factory(2)->admin()->create();

        // Create donor users (always active)
        $donors = User::factory(12)->donor()->create();

        // Create collector users (always pending)
        $collectors = User::factory(5)->collector()->create();

        // Create some active collectors
        $activeCollectors = User::factory(3)->collector()->active()->create();

        // Create waste posts from donor users
        $wastePosts = [];
        foreach ($donors as $user) {
            $posts = WastePost::factory(random_int(1, 3))
                ->for($user)
                ->create();

            $wastePosts = array_merge($wastePosts, $posts->toArray());
        }

        // Create jobs and link collectors to waste posts
        foreach ($wastePosts as $postData) {
            if (fake()->boolean(70)) { // 70% chance a post gets a job
                $allCollectors = $collectors->merge($activeCollectors);
                $collector = fake()->randomElement($allCollectors->all());

                $job = CollectionJob::factory()
                    ->for(WastePost::find($postData['id']))
                    ->for($collector, 'collector')
                    ->create();

                // Create earnings for completed jobs
                if ($job->status === 'completed') {
                    Earning::factory()
                        ->for($collector, 'collector')
                        ->for($job, 'job')
                        ->create();
                }
            }
        }

        // Create some independent earnings records for collectors
        $allCollectors = $collectors->merge($activeCollectors);
        $allCollectors->each(function ($collector) {
            Earning::factory(random_int(0, 3))
                ->for($collector, 'collector')
                ->create();
        });
    }
}
