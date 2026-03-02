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
        // Create regular users (waste post creators)
        $users = User::factory(10)->regularUser()->create();

        // Create collectors
        $collectors = User::factory(5)->collector()->create();

        // Create waste posts from regular users
        $wastePosts = [];
        foreach ($users as $user) {
            $posts = WastePost::factory(random_int(1, 3))
                ->for($user)
                ->create();

            $wastePosts = array_merge($wastePosts, $posts->toArray());
        }

        // Create jobs and link collectors to waste posts
        foreach ($wastePosts as $postData) {
            if (fake()->boolean(70)) { // 70% chance a post gets a job
                $collector = fake()->randomElement($collectors);

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

        // Create some independent earnings records
        $collectors->each(function ($collector) {
            Earning::factory(random_int(0, 3))
                ->for($collector, 'collector')
                ->create();
        });
    }
}
