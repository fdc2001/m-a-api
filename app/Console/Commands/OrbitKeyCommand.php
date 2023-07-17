<?php

namespace App\Console\Commands;

use App\Models\Configs;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Str;

class OrbitKeyCommand extends Command {
    protected $signature = 'orbit:key';

    protected $description = 'Command description';

    public function handle() {
        $this->info('Generating Orbit Key...');
        $key = Str::random(20);
        Configs::where('field', 'orbit_key')->updateOrCreate(['field' => 'orbit_key'], ['value' => Crypt::encryptString($key), 'field' => 'orbit_key']);
        $this->info('Orbit Key generated successfully.');
        $this->info('Orbit Key: ' . $key);
        exit(0);
    }
}
