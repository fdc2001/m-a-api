<?php

namespace App\Console\Commands;

use App\Models\Configs;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Str;

class WordpressKeyCommand extends Command {
    protected $signature = 'wordpress:key';

    protected $description = 'Generate Wordpress Key';

    public function handle() {
        $this->info('Generating Wordpress Key...');
        $key = Str::random(10);
        Configs::where('field', 'wordpress_key')->updateOrCreate(['field' => 'wordpress_key'], ['value' => Crypt::encryptString($key), 'field' => 'wordpress_key']);
        $this->info('Wordpress Key generated successfully.');
        $this->info('Wordpress Key: ' . $key);
        exit(0);
    }
}
