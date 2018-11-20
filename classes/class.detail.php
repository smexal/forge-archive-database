<?php

namespace Forge\Modules\ArchiveDatabase;

use \Forge\Core\App\App;
use \Forge\Core\Classes\Localization;
use \Forge\Core\Classes\Settings;
use \Forge\Core\Classes\Page;

class Detail {
    private $data = [];
    private $fields = [];

    public function __construct($data) {
        $this->data = json_decode($data);
        $this->data = $this->data[0];

        $this->fields = [
            'creation_date',
            'descriptor_icon',
            'soggetto',
            'rightholder',
            'support',
            'color',
            'dimensions',
            'author',
            'contributor',
            'identifier',
            'format',
            'conservation_status',
        ];
    }

    public function render() {
        $page = new Page(Settings::get('adb-buy-image-page'));
        return App::instance()->render(MOD_ROOT.'archive-database/templates/', 'detail', [
            'image' => $this->data->image_url,
            'title' => $this->getMultilingualField('title'),
            'meta' => $this->getMeta(),
            'buy_text' => i('Buy image', 'adb'),
            'buy_link' => $page->getUrl(),
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
                case 'title':
                    $value = $this->getMultilingualField('title');
                    break;
                case 'support':
                    $support = $this->getMultilingualField('support');
                    $value = $support ? $support : i('None', 'adb');
                    break;
                case 'conservation_status':
                    $value = $this->getRelationalFieldValue('conservation_status');
                    break;
                case 'format':
                    $value = $this->getRelationalFieldValue('format');
                    break;
                case 'author':
                    $value = $this->data->$field[0]->name.' '.$this->data->$field[0]->forename;
                    break;
                case 'soggetto':
                    $in = $this->getRelationalFieldValue('descriptor_in');
                    $an = $this->getRelationalFieldValue('descriptor_an');
                    $value = $in.' '.$an;
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
            if(! array_key_exists(0, $this->data->$field)) {
                return false;
            }
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