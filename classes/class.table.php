<?php

namespace Forge\Modules\ArchiveDatabase;

use \Forge\Core\App\App;
use \Forge\Core\Classes\Utils;
use \Forge\Core\Classes\Localization;

class Table {
    private $data = [];
    private $editAction = true;
    private $removeAction = true;
    private $editBar = false;
    private $barContentLeft = '';
    private $barContentRight = '';
    private $id = false;
    private $baseUrl = false;
    private $grid = false;
    private $actions = true;
    private $fieldsToDisplay = false;

    public function __construct($data) {
        $this->data = $data;
        $this->baseUrl = Utils::getUriComponents();
    }

    /**
     * @param $url_parts
     */
    public function setBaseUrl($url_parts) {
        if(is_array($url_parts)) {
            $this->baseUrl = $url_parts;
        }
    }

    public function setFields($fields) {
        $this->fieldsToDisplay = $fields;
    }

    public function switchToGrid() {
        $this->grid = true;
        $this->actions = false;
    }

    public function hideActions() {
        $this->actions = false;
    }

    public function setId($id) {
        $this->id = $id;
    }

    public function render() {
        $return = '';
        if($this->editBar) {
            $return.= App::instance()->render(CORE_TEMPLATE_DIR.'assets/', 'tablebar', [
                'contentleft' => $this->barContentLeft,
                'contentright' => $this->barContentRight 
            ]);
        }
        if($this->grid) {
            $return.= App::instance()->render(CORE_TEMPLATE_DIR.'assets/', 'table-grid', [
                'id' => 'infinite___'.$this->id,
                'td' => $this->getRows()
            ]);    
        } else {
            $return.= App::instance()->render(CORE_TEMPLATE_DIR.'assets/', 'table', [
                'id' => 'infinite___'.$this->id,
                'th' => $this->getTableHeadings(),
                'td' => $this->getRows()
            ]);
        }
        return $return;
    }

    public function renderRows() {
        if($this->grid) {
            return App::instance()->render(CORE_TEMPLATE_DIR.'assets/', 'table-grid-rows', [
                'td' => $this->getRows()
            ]);
        } else {
            return App::instance()->render(CORE_TEMPLATE_DIR.'assets/', 'table-rows', [
                'td' => $this->getRows()
            ]);
        }
    }

    public function addEditBar() {
        $this->editBar = true;
    }

    public function addBarContentLeft($content) {
        $this->barContentLeft.=$content;
    }

    public function addBarContentRight($content) {
        $this->barContentRight.=$content;
    }

    private function getRows() {
        $tds = [];
        if(!is_array($this->data)) {
            return [];
        }
        foreach($this->data as $key => $row) {           
            $id = 0;
            $rowVars = get_object_vars( $row );
            $td = [];
            $imageId = $rowVars['id'];
            foreach( $rowVars as $key => $value ) {
                if(is_array($this->fieldsToDisplay) && ! in_array($key, $this->fieldsToDisplay)) {
                    continue;
                }
                if(is_array($value)) {
                    $rvals = [];
                    foreach($value as $v) {
                        if(is_object($v)) {
                            $v = get_object_vars($v);
                            $together = [];
                            foreach($v as $rv) {
                                if(is_object($rv)) {
                                    if(strlen($rv->{Localization::getCurrentLanguage()}) > 0 ) {
                                        $together[] = $rv->{Localization::getCurrentLanguage()};
                                    } else {
                                        $together[] = '<small>'.i('Original: ', 'adb').'</small>'.$rv->original;
                                    }
                                    continue;
                                }
                                if(!is_object($rv) && (is_null($rv) || strlen($rv) == 0)) { 
                                    continue;
                                }
                                if(! is_numeric($rv)) {
                                    $together[] = $rv;
                                }
                            }
                            $rvals[] = implode(", ", $together);
                        }
                    }
                    $value = implode("; ", $rvals);
                }
                if(is_object($value)) {
                    $text = '';
                    $value = get_object_vars($value);
                    $tips = [];
                    foreach($value as $k => $v) {
                        if($k == Localization::getCurrentLanguage()) {
                            if(is_array($v)) {
                                $text = implode(", ", $v);
                            } else {
                                if(strlen($v) == 0) {
                                    $text = i('Missing', 'adb');
                                    if(array_key_exists('original', $value)) {
                                        $text = '<small>'.i('Original: ', 'adb').'</small>'.$value['original'];
                                    }
                                } else {
                                    $text = $v;
                                }
                            }
                        } else {
                            if(is_array($v)) {
                                $tips[] = strtoupper($k).': '.implode(", ", $v);
                            } else {
                                if(strlen($v) > 0) {
                                    if($k !== 'original') {
                                        $tips[] = strtoupper($k).': '.$v;
                                    }
                                }
                            }
                        }
                    }
                    $v = '<span class="'.(count($tips) > 0 ? 'tipster' : '').'" title="'.htmlspecialchars(implode(", ", $tips)).'">';
                    $v.= $text;
                    $v.= '</span>';
                    $value = $v;
                }
                if($key == 'image_url') {
                    $value = '<img data-id="'.$imageId.'" src='.$value.' onerror=\'this.src="http://www.independentmediators.co.uk/wp-content/uploads/2016/02/placeholder-image.jpg"\' class="adb-thumb" />';
                }
                if($key == 'id') {
                    $id = $value;
                }
                $td[] = [
                    'id' => $key,
                    'class' => false,
                    'content' => $value
                ];
            }
            $actionContent = '';
            $editUrl = false;
            if($this->editAction) {
                $editUrl = array_merge($this->baseUrl, ['detail', $id]);
                $actionContent.= Utils::iconAction("mode_edit", 'overlay', Utils::url($editUrl));
            }
            if($this->removeAction) {
                $url = array_merge($this->baseUrl, ['remove', $id]);
                $actionContent.= Utils::iconAction("remove_circle_outline", 'overlay', Utils::url($url));
            }
            if(($this->editAction || $this->removeAction) && $this->actions) {
                $td[] = [
                    'id' => $id,
                    'class' => 'actions',
                    'content' => $actionContent,
                    'rowAction' => Utils::url($editUrl)
                ];
            }
            $tds[] = $td;
        }
        return $tds;
    }

    public function disableEdit() {
        $this->editAction = false;
    }

    private function getTableHeadings() {
        if(! is_array($this->data) || count($this->data) == 0) {
            return [];
        }
        $h = $this->data[0];
        $th = [];
        foreach($h as $heading => $unused) {
            if($heading == 'image_url') {
                $heading = '';
            }
            $th[] = [
                'id' => $heading,
                'class' => '',
                'content' => i($heading, 'adb')
            ];
        }
        if($this->editAction) {
            $th[] = [
                'id' => 'edit',
                'class' => '',
                'content' => ''
            ];
        }
        return $th;
    }
}

?>