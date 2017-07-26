<?php

namespace Forge\Modules\ArchiveDatabase;

use \Forge\Core\Traits\Singleton;
use \Forge\Core\Classes\Settings;

class BaseConnector {
    use Singleton;

    private $sortArray = [
        'image_url',
        'title',
        'creation_date',
        'contributor',
        'people',
        'descriptor_icon',
        'descriptor_an',
        'descriptor_in',
        'geographical_descriptor',
        'rightholder',
        'support',
        'format',
        'quantity',
        'color',
        'dimensions',
        'conservation_status',
        'author',
        'author_copy',
        'professions',
        'identifier',
        'medium',
        'quantity_copies',
        'author_copies',
        'comment'
    ];

    public $urlBase = '';

    protected function __construct() {
        $this->urlBase = Settings::get('adb-base-url');
    }

    public function call($method, $url, $data = false) {
        $curl = curl_init();

        switch ($method) {
            case "POST":
                curl_setopt($curl, CURLOPT_POST, 1);

                if ($data)
                    curl_setopt($curl, CURLOPT_POSTFIELDS, $data);
                break;
            case "PUT":
                curl_setopt($curl, CURLOPT_PUT, 1);
                break;
            default:
                if ($data)
                    $url = sprintf("%s?%s", $url, http_build_query($data));
        }

        // Optional Authentication:
        //curl_setopt($curl, CURLOPT_HTTPAUTH, CURLAUTH_BASIC);
        //curl_setopt($curl, CURLOPT_USERPWD, "username:password");

        curl_setopt($curl, CURLOPT_URL, $this->urlBase.$url);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);

        $result = curl_exec($curl);

        curl_close($curl);

        $result = json_decode($result);
        if(is_object($result)) {
            $result = get_object_vars($result);
            $result = $this->sortArrayByArray($result, $this->sortArray);
        } else {
            if(is_array($result)) {
                foreach($result as $k => $v) {
                    if(! is_object($v))
                        continue;
                    $toArray = get_object_vars($v);
                    $result[$k] = $this->sortArrayByArray($toArray, $this->sortArray);
                }
            }
        }
        $result = json_encode($result);
        

        return $result;
    }

    private function sortArrayByArray($array, $orderArray) {
        $ordered = array();
        foreach ($orderArray as $key) {
            if (array_key_exists($key, $array)) {
                $ordered[$key] = $array[$key];
                unset($array[$key]);
            }
        }
        return $ordered + $array;
    }

}

?>