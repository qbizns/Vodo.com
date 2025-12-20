<?php

namespace HelloWorld\Database\Seeders;

use HelloWorld\Models\Greeting;
use Illuminate\Database\Seeder;

class GreetingSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $greetings = [
            [
                'message' => 'Hello, World!',
                'author' => 'System',
            ],
            [
                'message' => 'Welcome to the Hello World plugin!',
                'author' => 'Vodo',
            ],
            [
                'message' => 'Bonjour le monde!',
                'author' => 'French Greeter',
            ],
            [
                'message' => 'Hola Mundo!',
                'author' => 'Spanish Greeter',
            ],
            [
                'message' => 'Hallo Welt!',
                'author' => 'German Greeter',
            ],
            [
                'message' => 'Ciao Mondo!',
                'author' => 'Italian Greeter',
            ],
            [
                'message' => 'Olá Mundo!',
                'author' => 'Portuguese Greeter',
            ],
            [
                'message' => 'Привет мир!',
                'author' => 'Russian Greeter',
            ],
            [
                'message' => 'مرحبا بالعالم!',
                'author' => 'Arabic Greeter',
            ],
            [
                'message' => '你好世界！',
                'author' => 'Chinese Greeter',
            ],
        ];

        foreach ($greetings as $greeting) {
            Greeting::firstOrCreate(
                ['message' => $greeting['message']],
                $greeting
            );
        }
    }
}
