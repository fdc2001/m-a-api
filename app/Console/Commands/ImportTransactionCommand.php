<?php

namespace App\Console\Commands;

use DOMDocument;
use DOMXPath;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Http;

class ImportTransactionCommand extends Command {
    protected $signature = 'import-transaction';

    protected $description = 'Command description';

    public function handle() {
        $xmlFile = file_get_contents(storage_path() . '/app/tombstones.xml');
        $xml = simplexml_load_string($xmlFile, \SimpleXMLElement::class);
        $json = json_encode($xml);
        $data = json_decode($json, true);
        $this->setProcessTitle('Importing Transactions');
        $bar = $this->output->createProgressBar(count($data['channel']['item']));
        $bar->start();
        $errors = [];
        $i=0;
        foreach ($data['channel']['item'] as $row) {
            $link = str_replace('https', 'http', $row['link']);
            $page = Http::get($link);

            if ($page->ok()) {
                unset($link);
                $link = $page->headers()['Link'][1];
                $link = explode(';', $link)[0];
                $link = str_replace('<', '', str_replace('>', '', $link));
                $link = str_replace('https', 'http', $link);
                $response = Http::get($link);

                $body = json_decode($response->body(), 1);
                $post = $body['content']['rendered'];
                $doc = new DOMDocument();
                try {
                    $doc->loadHTML($post);
                } catch (\Exception $e) {
                    #$this->info('Error on transaction ');
                    #$this->info($link);
                    $bar->advance();
                    $errors[] = [
                        'link' => $link,
                        'error' => "link invalid",
                    ];
                    continue;
                }

                if(is_null($doc->getElementsByTagName('h6')->item(0))){
                    $bar->advance();
                    $errors[] = [
                        'link' => $link,
                        'error' => "Country impossible to find",
                    ];
                    continue;
                }
                $country = $doc->getElementsByTagName('h6')->item(0)->textContent;

                if(is_null($doc->getElementsByTagName('h6')->item(2))){
                    $bar->advance();
                    $errors[] = [
                        'link' => $link,
                        'error' => "Side impossible to find",
                    ];
                    continue;
                }
                $side = $doc->getElementsByTagName('h6')->item(2)->textContent;

                $industry = "";
                foreach ($row['category'] as $cat){
                    if(key_exists('@attributes',$cat)) {
                        if ($cat['@attributes']['domain'] == "category") {
                            if (strtoupper($cat['@attributes']['nicename']) != strtoupper($side)) {
                                $industry = $cat['@attributes']['nicename'];
                            }
                        }
                    }else{
                        $bar->advance();
                        $errors[] = [
                            'link' => $link,
                            'error' => "Category not found",
                        ];
                        continue;
                    }
                }

                #Content
                $domPage = new DOMDocument('5','UTF-8');

                $domPage->loadHTML($page->body(),LIBXML_NOERROR);
                $xpath = new DOMXPath($domPage);

                $element = $xpath->query('/html/body/div[1]/div/div[2]/div/div[1]/div/div/div[1]/div/div[2]/div/div/div/div');
                if(is_null($element->item(0))){
                    $bar->advance();
                    $errors[] = [
                        'link' => $link,
                        'error' => "Content impossible to find",
                    ];
                    continue;
                }
                $content = $element->item(0)->textContent;

                $content= trim($content);

                $titleElement = $xpath->query('/html/body/div[1]/div/div[1]/div/div/h1');
                if(is_null($titleElement->item(0))){
                    $bar->advance();
                    $errors[] = [
                        'link' => $link,
                        'error' => "Title impossible to find",
                    ];
                    continue;
                }
                $title=$titleElement->item(0)->textContent;

                $dateElement = $xpath->query('/html/body/div[1]/div/div[2]/div/div[1]/div/div/div[1]/div/div[1]/div/div/div[1]/div[2]/div/div/div/div/h6/span');
                if(is_null($dateElement->item(0))){
                    $bar->advance();
                    $errors[] = [
                        'link' => $link,
                        'error' => "Date impossible to find",
                    ];
                    continue;
                }
                $date=$dateElement->item(0)->textContent;

                if($country=='USA')
                    $country='United States of America';

                $region = \App\Models\Regions::where('name', '=', $country)->first();
                if (is_null($region)) {
                    #$this->info('Region not found for ' . $country);
                    $errors[] = [
                        'link' => $link,
                        'error' => 'Region not found for ' . $country,
                    ];
                    $bar->advance();
                    continue;
                }
                $region = $region->id;


                $member = \App\Models\Members::where('region_id','=', $region)->first();
                if (is_null($member)) {
                    #$this->info('Member not found for ' . $country);
                    $errors[] = [
                        'link' => $link,
                        'error' => 'Member not found for ' . $country,
                    ];
                    $bar->advance();
                    continue;
                }
                $member = $member->id;


                $industry = explode('-',$industry)[0];
                $industryID = \App\Models\IndustrySector::whereRaw('upper(name) LIKE upper("%'.$industry.'%")')->get()->first();
                if (is_null($industryID))
                    $industryID=38;
                else
                    $industryID = $industryID->id;

                $transaction = new \App\Models\Transaction();
                $transaction->buyer_logo="#";
                $transaction->orbit_id=1;
                $transaction->type_of_transaction="NOT DEFINED";
                $transaction->industry_sector=$industryID;
                $transaction->detailed_business_desc=$content;
                $transaction->transaction_size="";
                $transaction->member_id=$member;
                $transaction->deal_manager="";
                $transaction->tombstone_title=$title;
                $transaction->transaction_excerpt=$content;
                $transaction->keyphrase="";
                $transaction->tombstone_top_image="#";
                $transaction->tombstone_bottom_image="#";
                preg_match_all("/([0-9\\/])/", $date, $dateFormatted);

                $dateCarbon = Carbon::createFromFormat('Y', join('',$dateFormatted[0]))->toDateTimeString();
                $transaction->date_transaction=$dateCarbon;
                $transaction->side=$side;
                $transaction->slug=\Illuminate\Support\Str::slug($title);
                $transaction->approved=false;
                $transaction->save();
                $transaction->orbit_id=$transaction->id;
                $transaction->save();
                #$this->info('Transaction '.$transaction->id.' imported');
                $bar->advance();
            }else{
                $bar->advance();
            }
        }
        $bar->finish();
        $this->info('Done');
        $this->info('Errors: '.count($errors));
        $this->table(['Link', 'Error'], $errors);
    }
}
