<?php

namespace Forge\Modules\ArchiveDatabase;

use \Forge\Core\Classes\Localization;
use \Forge\Core\App\App;
use \Forge\Core\Classes\Fields;

class EditForm {
    private $data = [];
    private $bc = null;
    private $lastCounter = 0;
    private $curLang = 'de';

    private $specialMatchFilter = [
        'geogra' => 'places',
        'rightholder' => 'institution',
        'support' => 'institution',
        'author' => 'people',
        'contributor' => 'people',
        'color' => 'colors',
        'format' => 'formats'
    ];

    private $singleRelations = [
        'rightholder',
        'contributor',
        'color',
        'format',
        'support',
        'author'
    ];

    public function __construct($data, $bc) {
        $this->data = $data;
        $this->bc = $bc;
        $this->curLang = Localization::getCurrentLanguage();
    }

    public function render() {
        $fields = [];
        $editFieldData = $this->data[0];
        $editFieldData = get_object_vars($editFieldData);
        foreach($editFieldData as $key => $value) {
            $fields[] = $this->getField($key, $value);
        }
        $fields[] = Fields::button(i('Save', 'adb'));

        return App::instance()->render(CORE_TEMPLATE_DIR.'assets/', 'form', [
            'method' => 'POST',
            'action' => false,
            'ajax' => true,
            'horizontal' => false,
            'ajax_target' => '',
            'content' => $fields
        ]);
    }

    private function getField($key, $value) {
        if($key == 'id') {
            return Fields::hidden(['name' => $key], $value);
        }

        if($key == 'image_url') {
            return '<img src="'.$value.'" class="the_image" />';
        }
        $isUnknownDataRequest = $this->isUnknownDataRequest($key);
        $isSingleRelation = $this->isSingleRelation($key);

        // Single Text Entries with type="text"
        if(is_string($value) && ! $isSingleRelation) {
            return Fields::text([
                'key' => $key,
                'label' => i($key, 'odb')
            ], $value);
        }
        // Single Text Entries with type="text"
        if(is_null($value) && $isUnknownDataRequest) {
            return Fields::text([
                'key' => $key,
                'label' => i($key, 'odb')
            ], '');
        }

        // Single Select Entries 
        if(!$isUnknownDataRequest && $isSingleRelation) {
            $val = 0;
            if(is_numeric($value)) {
                $val = $value;
            }
            if(is_array($value) && count($value) > 0) {
                $val = $value[0]->id;
            }
            return Fields::select([
                'key' => $key,
                'label' => i($key, 'odb'),
                'values' => $this->getSelectValues($key),
                'chosen' => true
            ], $val);
        }

        // Grouped Select Entries with multiple Selects to add & remove
        if(!$isUnknownDataRequest && !$isSingleRelation) {
            $return = '<div class="form-grouping card">';
            $return.= '<h4>'.i($key,'adb').'</h4>';
            $return.= $this->renderExistingSelects($key, $value);
            $return.= $this->addSelectButton($key);
            $return.= '</div>';

            return $return;
        }

        // Multiple Text Entries for each language as type="text"
        if(is_object($value)) {
            $return = '<div class="form-grouping card">';
            $return.= '<h4>'.i($key,'adb').'</h4>';
            $val = get_object_vars($value);
            foreach($val as $lang => $v) {
                $return.=Fields::text([
                    'key' => $key.'___'.$lang,
                    'label' => i($lang, 'odb')
                ], is_array($v) ? $v[0] : $v);
            }
            $return.= '</div>';            

            return $return;
        }

        // Something special or new happened....
        return 'undefined > '.$key.'<br />';
    }

    private function addSelectButton($key) {
        return '<a class="add-select-button" data-index="'.($this->lastCounter+1).'" data-base="'.$this->bc->urlBase.'" data-type="'.$key.'" data-nothing="'.i('Nothing selected', 'adb').'" href="javascript://"><i class="material-icons">add_box</i></a>';
    }

    private function renderExistingSelects($key, $value) {
        $count = 0;
        $return = '';
        foreach($value as $val) {
            $count++;
            $return.='<div class="add-group">';
            if(property_exists($val, 'fid')) {
                $setValue = $val->fid;
            } else {
                $setValue = 0;
            }
            $return.=Fields::select([
                'key' => $key.'___'.$count,
                'label' => '',
                'values' => $this->getSelectValues($key),
                'chosen' => true
            ], $setValue);
            $return.='<a href="javascript://" class="remove-before"><i class="material-icons">remove_circle_outline</i></a>';
            $return.= '</div>';
        }
        $this->lastCounter = $count;
        return $return;
    }

    private function isSingleRelation($key) {
        if(in_array($key, $this->singleRelations)) {
            return true;
        }
        $key = $this->specialMatchFilter($key, true);
        $singleExists = $this->canQuery($key);
        $connectorExists = $this->canQuery('con_images_'.$key);
        if($singleExists && ! $connectorExists) {
            return true;
        }
        return;
    }

    private function canQuery($key) {
        $key = $this->specialMatchFilter($key);
        $data = json_decode($this->bc->call(
            'GET',
            'get/'.$key.'?limit=1&orderBy=name'
        ));
        if(array_key_exists('unknownDataRequest', $data)) {
            return false;
        }
        return true;
    }

    private function getSelectValues($key) {
        $key = $this->specialMatchFilter($key);
        $data = json_decode($this->bc->call(
            'GET',
            'get/'.$key.'?limit=none&order=name'
        ));
        $values = [];
        $values[0] = i('Nothing selected', 'adb');
        foreach($data as $obj) {
            if(property_exists($obj, 'id') && property_exists($obj, 'name')) {
                if(property_exists($obj, 'name')) {
                    $text = '';
                    if(is_object($obj->name)) {
                        if(property_exists($obj->name, 'original')) {
                            $text.= $obj->name->{$this->curLang};
                            $text.= ' – '.i('Original: ', 'adb').' '.$obj->name->original;
                        }
                    } else {
                        $text.= $obj->name;
                    }
                }
                if(property_exists($obj, 'forename') && strlen($obj->forename) > 0){
                    $text.=", ".$obj->forename;
                }
                $values[$obj->id] = $text;
            }
        }
        return $values;
    }

    public function getSelect($key, $count, $setValue = 0) {
        $return = '';
        $return.=Fields::select([
            'key' => $key.'___'.$count,
            'label' => '',
            'values' => $this->getSelectValues($key),
            'chosen' => true
        ], $setValue);
        $return.='<a href="javascript://" class="remove-before"><i class="material-icons">remove_circle_outline</i></a>';
        $return.= '</div>';
        return $return;
    }


    private function isUnknownDataRequest($key) {
        $key = $this->specialMatchFilter($key);

        $data = json_decode($this->bc->call(
            'GET',
            'get/'.$key.'?limit=1'
        ));
        if(array_key_exists('unknownDataRequest', $data)) {
            return true;
        }
        return false;
    }

    private function specialMatchFilter($key) {
        foreach($this->specialMatchFilter as $str => $return) {
            if(strstr($key, $str)) {
                return $return;
            }
        }
        return $key;
    }

}

?>