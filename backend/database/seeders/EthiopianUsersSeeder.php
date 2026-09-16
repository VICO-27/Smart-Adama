<?php

namespace Database\Seeders;

use App\Models\Chapter;
use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class EthiopianUsersSeeder extends Seeder
{
    public function run(): void
    {
        $firstNames = [
            'Abebe', 'Bekele', 'Dawit', 'Ephrem', 'Fikre', 'Girma', 'Hailu', 'Israel', 'Jember', 'Kaleb',
            'Lidetu', 'Mulugeta', 'Nigussie', 'Oumer', 'Paulos', 'Qasim', 'Robel', 'Solomon', 'Tadesse', 'Yonas',
            'Zelalem', 'Ayantu', 'Birtukan', 'Chaltu', 'Desta', 'Emebet', 'Fasika', 'Genet', 'Hirut', 'Kalkidan',
            'Lemlem', 'Makda', 'Netsanet', 'Rahel', 'Selam', 'Tigist', 'Wubit', 'Yordanos', 'Zinash', 'Gadaa',
            'Tolosa', 'Ibsa', 'Caalaa', 'Bonsa', 'Gutama', 'Kuma', 'Lensa', 'Urge', 'Ifa', 'Hawii',
        ];

        $lastNames = [
            'Alemu', 'Balcha', 'Chala', 'Demissie', 'Endale', 'Feleke', 'Gessesse', 'Haile', 'Kebede', 'Lema',
            'Mekonnen', 'Nida', 'Oljira', 'Petros', 'Regassa', 'Samuel', 'Tesfaye', 'Urgessa', 'Wakjira', 'Yilma',
            'Zewde', 'Assefa', 'Bogale', 'Derese', 'Fikadu', 'Gudina', 'Hundessa', 'Jiru', 'Kifle', 'Tulu',
        ];

        $users = [];
        $progress = [];
        $chapters = Chapter::pluck('id')->toArray();
        $totalChapters = count($chapters) ?: 10;

        $now = Carbon::now();
        $statuses = ['Active', 'Active', 'Active', 'Active', 'Active', 'Inactive', 'Inactive', 'Suspended']; // Weighting
        $password = Hash::make('password');

        for ($i = 0; $i < 305; $i++) {
            $f = $firstNames[array_rand($firstNames)];
            $l = $lastNames[array_rand($lastNames)];
            $name = "$f $l";
            // Randomly unique email
            $email = strtolower($f.'.'.$l.'_'.Str::random(6).'@example.com');

            $status = $statuses[array_rand($statuses)];
            $userId = Str::uuid()->toString();

            $users[] = [
                'id' => $userId,
                'name' => $name,
                'email' => $email,
                'password' => $password,
                'role' => 'learner',
                'status' => $status,
                'avatar_url' => 'https://ui-avatars.com/api/?name='.urlencode($name).'&background=random',
                'created_at' => $now->clone()->subDays(mt_rand(1, 100)),
                'updated_at' => $now,
            ];

            // Create progress for a random number of chapters
            if (! empty($chapters)) {
                $completedCount = mt_rand(0, $totalChapters);
                $chaptersCopy = $chapters;
                shuffle($chaptersCopy);

                for ($j = 0; $j < $totalChapters; $j++) {
                    $cId = $chaptersCopy[$j];

                    if ($j < $completedCount) {
                        $pStatus = 'COMPLETED';
                        $isCompleted = true;
                        $readingProgress = 100;
                    } elseif ($j === $completedCount) {
                        $pStatus = 'IN_PROGRESS';
                        $isCompleted = false;
                        $readingProgress = mt_rand(10, 90);
                    } else {
                        break; // Not started, no record
                    }

                    $progress[] = [
                        'id' => Str::uuid()->toString(),
                        'user_id' => $userId,
                        'chapter_id' => $cId,
                        'is_completed' => $isCompleted,
                        'status' => $pStatus,
                        'reading_progress' => $readingProgress,
                        'created_at' => $now->clone()->subDays(mt_rand(1, 30)),
                        'updated_at' => $now,
                    ];
                }
            }
        }

        // Insert in chunks
        foreach (array_chunk($users, 100) as $chunk) {
            DB::table('users')->insert($chunk);
        }

        foreach (array_chunk($progress, 500) as $chunk) {
            DB::table('user_progress')->insert($chunk);
        }
    }
}
