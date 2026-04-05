<?php

namespace Database\Seeders;

use App\Models\Subject;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class SubjectSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $subjects = [
            [
                'name' => 'Matematika',
            ],
            [
                'name' => 'Fisika',
            ],
            [
                'name' => 'Kimia',
            ],
            [
                'name' => 'Biologi',
            ],
            [
                'name' => 'Bahasa Indonesia',
            ],
            [
                'name' => 'Bahasa Inggris',
            ],
            [
                'name' => 'Bahasa Sunda',
            ],
            [
                'name' => 'Pendidikan Agama Islam',
            ],
            [
                'name' => 'Pendidikan Agama Kristen',
            ],
            [
                'name' => 'Pendidikan Agama Hindu',
            ],
            [
                'name' => 'Pendidikan Agama Buddha',
            ],
            [
                'name' => 'Pendidikan Agama Konghucu',
            ],
            [
                'name' => 'Pendidikan Pancasila dan Kewarganegaraan',
            ],
            [
                'name' => 'Pendidikan Jasmani, Olahraga dan Kesehatan',
            ],
            [
                'name' => 'Seni Budaya',
            ],
            [
                'name' => 'Informatika',
            ],
            [
                'name' => 'Ekonomi',
            ],
            [
                'name' => 'Geografi',
            ],
            [
                'name' => 'Sosiologi',
            ],
            [
                'name' => 'Sejarah',
            ],
        ];
        foreach ($subjects as $subject) {
            Subject::create($subject);
        }
    }
}
