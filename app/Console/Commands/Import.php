<?php

namespace App\Console\Commands;

use GuzzleHttp\Exception\GuzzleException;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;
use Smartosc\LaraBig\LaraBig;
use Smartosc\LaraBig\Model\Store;

class Import extends Command
{
    private $laraBig;
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'import';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Command description';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct(LaraBig $laraBig)
    {
        parent::__construct();

        $this->laraBig = $laraBig;
    }

    /**
     * Import constructor.
     * @param LaraBig $laraBig
     */
//    private function  __construct(LaraBig $laraBig)
//    {
//        $this->laraBig = $laraBig;
//    }

    public function converPhoneNumber($str)
    {
        $resource =  preg_replace('/\(?\)?\s?\-?/', '', $str);
        return $resource;
    }

    public function coverData($value)
    {
        $address1 = "";
        $address2 = "";
        $address = [];
        if($value['Address1'] != "")
        {
            $address1 = [
                "first_name" => $value['First Name'],
                "last_name" => $value['Last Name'],
                "address1" => $value['Address1'],
                "city" => $value['City'],
                "state_or_province" => $value['Province'],
                "postal_code" => $value['Zip'],
                "country_code" => $value['Country Code'],
                "phone" => $this->converPhoneNumber($value['Phone'])
            ];
            array_push($address, $address1);
        }

        if($value['Address2'] != "")
        {
            $address2 = [
                "first_name" => $value['First Name'],
                "last_name" => $value['Last Name'],
                'address1' => $value['Address1'],
                "address2" => $value['Address2'],
                "city" => $value['City'],
                "state_or_province" => $value['Province'],
                "postal_code" => $value['Zip'],
                "country_code" => $value['Country Code'],
                "phone" => $this->converPhoneNumber($value['Phone'])
            ];
            array_push($address, $address2);
        }
//        if(count($address) == 0) $address = "";
        $data = [
            "email" => $value['Email'],
            "first_name" => $value['First Name'],
            "last_name" => $value['Last Name'],
            "company" => $value['Company'],
            "phone" => $this->converPhoneNumber($value['Phone']),
            "notes" => $value['Note'],
            "addresses" => $address
        ];

        return $data;
    }
    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        if (!Cache::has('csv')) {
            $path = "/var/www/File/Customers_Sample_File.csv";
            $csv = array_map('str_getcsv', file($path));
            array_walk($csv, function(&$a) use ($csv){
                $a = array_combine($csv[0], $a);
            });
            array_shift($csv);
            Cache::put('csv', $csv);
        }

        $csv = Cache::get('csv');
        $customers = array();



        $bars = $this->output->createProgressBar(count($csv));
        $bars->start();

        $lastImport = Cache::get('last_import', 0);

        foreach ($csv as $key => $customer)
        {
            $bars->advance();
            if ($bars->getProgress() <= $lastImport) continue;

            if($customer['Email'] != "" && $customer['First Name'] != "" && $customer['Last Name'] != ""){
                $dt = $this->coverData($customer);
                array_push($customers, $dt);
            }
            if(count($customers) == 1){
                try{
                    //$this->output->writeln('Start import batch');
                    var_dump($customers);
                    $result = $this->laraBig->setStore(Store::first())->customer->create($customers);
                    //$this->output->success('Import batch complete');
                    $customers = [];
                }catch (GuzzleException $ex) {
                    $response = $ex->getResponse();
                    $jsonBody = (string) $response->getBody();
                    dd(json_decode($jsonBody));
                }

            }
            $lastImport = $bars->getProgress();
            Cache::put('last_import', $lastImport);
        }
        return 1;
    }
}
