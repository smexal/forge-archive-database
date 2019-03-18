<?php

namespace Forge\Modules\ArchiveDatabase;

use \Forge\Modules\ArchiveDatabase\BaseConnector;
use \Forge\Core\Classes\Settings;

class DataUpdater {
    private $type = null;
    private $data = null;
    private $ignoredForUpdate = ['id', 'event'];

    public function __construct($type, $data) {
        $this->type = $type;
        $this->data = $data;
    }

    private function getConfig() {
        return [
            'key' => Settings::get('adb-base-api-key')
        ];
    }

    public function run() {
        if(!array_key_exists('id', $this->data)) {
            return;
        }
        $this->updateDirectValues();
        $this->updateMetaValues();
        $this->updateConnectValues();
        $this->updateTranslationValues();
    }

    private function updateTranslationValues() {
        $bc = BaseConnector::instance();
        if(array_key_exists('name___original', $this->data)) {
            foreach($this->data as $key => $value) {
                if($this->ignored($key)) {
                    continue;
                }
                if(strlen($value) == 0) {
                    continue;
                }
                $keySplit = explode("___", $key);
                $lang = $keySplit[1];
                // set new original
                if($lang == 'original') {
                    $res0 = $bc->call('GET', 'set/'.$this->type, array_merge(['id' => $this->data['id']], $this->getConfig(), ['name' => $value]));
                } elseif (strlen($lang) == 2) {
                    // http://base-robertodonetta.dev/set/conservation_status/translation?key=007007007&id=1&lang=de&value=test
                    $res = $bc->call('GET', 'set/'.$this->type.'/translation', array_merge([
                        'id' => $this->data['id'],
                        'lang' => $lang,
                        'value' => $value
                    ], $this->getConfig()));
                }
            }
        }
    }

    private function updateConnectValues() {
        // /set/images/connect?key=007007007&target=descriptor_an&id=1&values=1,2,3,4
        $updateValues = [];
        foreach($this->data as $key => $value) {
            if($this->ignored($key)) {
                continue;
            }
            if(strstr($key, '___')) {
                $key_split = explode("___", $key);
                if(! is_numeric($key_split[1])) {
                    continue;
                }
                if($value == 0) {
                    continue;
                }
                $updateValues[$key_split[0]][] = $value;
            }
        }
        foreach($updateValues as $type => $values) {
            $getParams = array_merge(
                [
                    'id' => $this->data['id'],
                    'target' => $type,
                    'values' => implode(",", $values)
                ],
                $this->getConfig()
            );
            $bc = BaseConnector::instance();
            $data = $bc->call('GET', 
                'set/'.$this->type.'/connect', 
                $getParams
            );
        }
    }

    private function updateDirectValues() {
        $toUpdate = [];
        foreach($this->data as $key => $value) {
            if($this->ignored($key)) {
                continue;
            }
            if(!is_string($value)) {
                continue;
            }
            if(strstr($key, '___')) {
                continue;
            }
            if(is_string($value) && ! $this->ignored($key)) {
                $toUpdate[$key] = $value;
            }
        }
        $bc = BaseConnector::instance();
        //var_dump('set/'.$this->type, http_build_query(array_merge(['id' => $this->data['id']], $this->getConfig(), $toUpdate)));
        $res = $bc->call('GET', 'set/'.$this->type, array_merge(['id' => $this->data['id']], $this->getConfig(), $toUpdate));
    }


    private function updateMetaValues() {
        $toUpdate = [];
        foreach($this->data as $key => $value) {
            if(strstr($key, 'name___')) {
                continue;
            }
            if($this->ignored($key)) {
                continue;
            }
            if(strstr($key, '___')) {
                $key_split = explode("___", $key);
                if(is_numeric($key_split[1])) {
                    continue;
                }
                if(strlen($value) == 0) {
                    continue;
                }
                $toUpdate[$key_split[0]][$key_split[1]] = $value;
            }
        }
        foreach($toUpdate as $field => $updateValues) {
            $bc = BaseConnector::instance();
            $data = $bc->call('GET', 
                'set/'.$this->type.'/meta', 
                array_merge(
                    ['id' => $this->data['id'], 'field' => $field], 
                    $this->getConfig(), 
                    $updateValues
                )
            );
        }
    }

    private function ignored($key) {
        return in_array($key, $this->ignoredForUpdate);
    }

}

?>