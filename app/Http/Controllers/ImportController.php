<?php

namespace App\Http\Controllers;

use GuzzleHttp\Exception\GuzzleException;
use Illuminate\Http\Request;
use Smartosc\LaraBig\LaraBig;
use Smartosc\LaraBig\Model\Store;

class ImportController extends Controller
{
    private $laraBig;

    public function __construct(LaraBig $laraBig)
    {
        $this->laraBig = $laraBig;
    }

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
        if(count($address) == 0) $address = "";
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

    public function import()
    {
        $path = "/var/www/File/Customers_Sample_File.csv";
        $csv = array_map('str_getcsv', file($path));
        array_walk($csv, function(&$a) use ($csv){
            $a = array_combine($csv[0], $a);
        });
        array_shift($csv);
        $customers = array();
        foreach ($csv as $key => $customer)
        {
            if($customer['Email'] != "" && $customer['First Name'] != "" && $customer['Last Name'] != ""){
                $dt = $this->coverData($customer);
                array_push($customers, $dt);
            }
            if(count($customers) == 10){
                    print_r("<pre>");
                    print_r($customers);
//                    $this->laraBig->setStore(Store::first())->customer->create($customers);
                    $customers = [];
            }
        }
        die;
//        dd(array_chunk($customers, 10, true));
        return 1;
    }

    public function bigc(){
        $path = "/var/www/File/importFile.csv";
//        $file = fopen($path, "r");
//        $data = str_getcsv($path);
        $csv = array_map('str_getcsv', file($path));
        array_walk($csv, function(&$a) use ($csv){
            $a = array_combine($csv[0], $a);
        });
        $data = array_shift($csv);
        dd($csv);
    }
}
