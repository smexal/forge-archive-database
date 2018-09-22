<?php

namespace Forge\Modules\ArchiveDatabase;

use \Forge\Core\App\App;
use \Forge\Core\Classes\Localization;

class Detail {
    private $data = [];
    private $fields = [];

    public function __construct($data) {
        $this->data = json_decode($data);
        $this->data = $this->data[0];

        $this->fields = [
            'identifier',
            'creation_date',
            'dimensions',
            'rightholder',
            'color',
            'contributor',
            'conservation_status',
            'descriptor_icon'
        ];
    }

    public function render() {
        return App::instance()->render(MOD_ROOT.'archive-database/templates/', 'detail', [
            'image' => $this->data->image_url,
            'title' => $this->getMultilingualField('title'),
            'meta' => $this->getMeta()
        ]);
    }

    private function getMeta() {
        $meta = [];
        foreach($this->fields as $field) {
            switch($field) {
                case 'rightholder':
                    $value = $this->data->$field[0]->name;
                    break;
                case 'color':
                    $value = $this->data->$field[0]->name;
                    break;
                case 'contributor': 
                    $value = $this->data->$field[0]->name.' '.$this->data->$field[0]->forename;
                    break;
                case 'conservation_status':
                    $value = $this->getRelationalFieldValue('conservation_status');
                    break;
                case 'descriptor_icon':
                    $value = $this->getRelationalFieldValue('descriptor_icon');
                    break;

                default:
                    $value = $this->data->$field;
            }

            $meta[] = [
                'title' => i($field, 'adb'),
                'value' => $value
            ];
        }

        return $meta;
    }

    private function getRelationalFieldValue($field, $backupLang = 'original') {
            $lang = Localization::getCurrentLanguage();
            $value = $this->data->$field[0]->name->$lang;
            if(! strlen($value)) {
                $value = $this->data->$field[0]->name->$backupLang;
            }
            
            return $value;        
    }

    // get title in language or get it in IT.
    private function getMultilingualField($field, $backupLang = 'it') {
        $lang = Localization::getCurrentLanguage();
        $value = $this->data->$field->$lang;
        if(is_array($value)) {
            $value = $value[0];
        } else {
            $value = $this->data->$field->$backupLang;
        }
        return $value;
    }

}