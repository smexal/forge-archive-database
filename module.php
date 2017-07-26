<?php

namespace Forge\Modules\ArchiveDatabase;

use \Forge\Loader;
use \Forge\Core\Abstracts\Module as AbstractModule;
use \Forge\Core\App\App;
use \Forge\Core\App\Auth;
use \Forge\Core\App\ModifyHandler;
use \Forge\Core\Classes\Fields;
use \Forge\Core\Classes\Settings;
use \Forge\Core\Classes\Localization;
use \Forge\Core\Classes\Utils;

use \Forge\Modules\ArchiveDatabase\BaseConnector;
use \Forge\Modules\ArchiveDatabase\Table;
use \Forge\Modules\ArchiveDatabase\EditForm;
use \Forge\Modules\ArchiveDatabase\DataUpdater;

use \Forge\Core\Traits\ApiAdapter;

class Module extends AbstractModule {
    private $settings = null;
    private $baseView = false;
    private $detailId = false;
    private $apiMainListener = 'archive-database';

    use ApiAdapter;

    public function setup() {
        $this->settings = Settings::instance();
        $this->version = '0.0.1';
        $this->id = "archive-database";
        $this->name = i('Archive Database', 'adb');
        $this->description = i('Management and Content Elements for an Archive Database.', 'adb');
        $this->image = $this->url().'assets/images/module-image.png';
    }

    public function start() {
        Auth::registerPermissions("manage.archive-database");

        Loader::instance()->addStyle("modules/archive-database/assets/css/backend.less");
        Loader::instance()->addScript("modules/archive-database/assets/scripts/adb.js");

        App::instance()->tm->theme->addScript($this->url()."assets/scripts/masonry.js", true);
        App::instance()->tm->theme->addScript($this->url()."assets/scripts/imagesloaded.js", true);
        App::instance()->tm->theme->addScript($this->url()."assets/scripts/adb.js", true);

        App::instance()->tm->theme->addStyle(MOD_ROOT."archive-database/assets/css/frontend.less");
        App::instance()->tm->theme->addStyle(MOD_ROOT."archive-database/assets/css/loader.less");

        ModifyHandler::instance()->add('modify_manage_navigation', [$this, 'modifyManageNavigation']);

        $this->settingsViews = [
            [
                'callable' => 'genericDataView',
                'title' => i("Images", 'adb'),
                'url' => 'images'
            ],
            [
                'callable' => 'genericDataView',
                'title' => i("People", 'adb'),
                'url' => 'people'
            ],
            [
                'callable' => 'genericDataView',
                'title' => i("Places", 'adb'),
                'url' => 'places'
            ],
            [
                'callable' => 'genericDataView',
                'title' => i("Colors", 'adb'),
                'url' => 'colors'
            ],
            [
                'callable' => 'genericDataView',
                'title' => i("Conservation Status", 'adb'),
                'url' => 'conservation_status'
            ],
            [
                'callable' => 'genericDataView',
                'title' => i("Desc. Animated", 'adb'),
                'url' => 'descriptor_an'
            ],
            [
                'callable' => 'genericDataView',
                'title' => i("Desc. Not Animated", 'adb'),
                'url' => 'descriptor_in'
            ],
            [
                'callable' => 'genericDataView',
                'title' => i("Desc. Iconographic", 'adb'),
                'url' => 'descriptor_icon'
            ],
            [
                'callable' => 'genericDataView',
                'title' => i("Formats", 'adb'),
                'url' => 'formats'
            ],
            [
                'callable' => 'genericDataView',
                'title' => i("Professions", 'adb'),
                'url' => 'profession'
            ],
            [
                'callable' => 'genericDataView',
                'title' => i("Titles", 'adb'),
                'url' => 'person_title'
            ],
            [
                'callable' => 'genericDataView',
                'title' => i("Institutions", 'adb'),
                'url' => 'institution'
            ]
        ];

        $this->registerSettings();
    }

    public function infiniteLoading($query, $data) {
        $bc = BaseConnector::instance();
        $q = '?limit='.$_GET['limit'].'&offset='.$_GET['offset'];
        if($query[0] == 'images') {
            $q.='&hide='.Settings::get('adb-base-hide-fields-in-table');
        }
        if(\array_key_exists('search', $_GET)) {
            $q.= "&search=".\urlencode($_GET['search']);
        }
        $queryUrl = 'get/'.$query[0].$q;
        $table = new Table(json_decode($bc->call('GET', $queryUrl)));
        $table->setBaseUrl(['manage', 'module-settings', 'archive-database', $query[0]]);
        if(array_key_exists('grid', $_GET) && $_GET['grid'] == 'true') {
            $table->switchToGrid();
            $table->setFields(explode(",", Settings::get('adb-grid-fields')));
        }
        return json_encode([
            'newRows' => $table->renderRows()
        ], JSON_HEX_QUOT | JSON_HEX_TAG);
    }

    public function getSelect($query, $data) {
        $bc = BaseConnector::instance();
        $editForm = new EditForm([], $bc);
        return json_encode([
            'select' => $editForm->getSelect($_GET['type'], $_GET['count'])
        ]);
    }

    public function genericDataView() {
        $bc = BaseConnector::instance();
        $parts = Utils::getUriComponents();

        $this->baseView = $parts[3];

        if(array_key_exists('event', $_POST) && $_POST['event'] == 'onUpdateModuleSettings') {
            $this->update($this->baseView, $_POST);
        }

        if($parts[count($parts)-1] == 'cancel') {
            App::instance()->redirect(Utils::getUrl(['manage', 'module-settings', 'archive-database', $this->baseView]));
        }

        if($parts[count($parts)-2] == 'confirm') {
            $this->detailId = $parts[count($parts)-1];
            if(is_numeric($this->detailId)) {
                $this->deleteItem();
                App::instance()->redirect(Utils::getUrl(['manage', 'module-settings', 'archive-database', $this->baseView]));
            }
        }

        if($parts[count($parts)-2] == 'detail' 
            && (is_numeric($parts[count($parts)-1]) || $parts[count($parts)-1] == 'new')
        ) {
            $this->detailId = $parts[count($parts)-1];
            ModifyHandler::instance()->add('modify_module_settings_render_args', [$this, 'modifyDetailViewArgs']);
            return $this->editMask($bc);
        } else if($parts[count($parts)-2] == 'remove' && is_numeric($parts[count($parts)-1])) {
            return $this->deleteView();
        } else {
            $q = '?limit=20';
            if($this->baseView == 'images') {
                $q.='&hide='.Settings::get('adb-base-hide-fields-in-table');
            }

            $table = new Table(json_decode($bc->call('GET', 'get/'.$this->baseView.'/filter'.$q)));
            $table->setId($this->baseView);
            $table->addEditBar();
            $table = $this->barContent($table);
            return $table->render();
        }
    }

    private function deleteItem() {
        $bc = BaseConnector::instance();
        $newId = json_decode($bc->call(
            'GET',
            'delete/'.$this->baseView.'/?key='.Settings::get('adb-base-api-key').'&id='.$this->detailId
        ));
    }

    private function deleteView() {
        $this->detailId = Utils::getUriComponents();
        $this->detailId = $this->detailId[count($this->detailId)-1];

        ModifyHandler::instance()->add('modify_module_settings_template_directory', function($args) {
            return CORE_TEMPLATE_DIR."assets/";
        });

        ModifyHandler::instance()->add('modify_module_settings_template_name', function($args) {
            return CORE_TEMPLATE_DIR."confirm/";
        });

        ModifyHandler::instance()->add('modify_module_settings_render_args', function($args) {
            return [
                'title' => i('Confirm deletion', 'adb'),
                'message' => '',
                'yes' => [
                    'url' => Utils::url(['manage', 'module-settings', 'archive-database', $this->baseView, 'confirm', $this->detailId]),
                    'title' => i('Yes, delete', 'adb')
                ],
                'no' => [
                    'url' => Utils::url(['manage', 'module-settings', 'archive-database', $this->baseView, 'cancel']),
                    'title' => i('Cancel', 'adb')
                ]
            ];
        });

        return '';
    }

    private function barContent($table) {

        $table->addBarContentRight(
            Utils::overlayButton(
                Utils::url(['manage', 'module-settings', 'archive-database', $this->baseView, 'detail', 'new']),
                i('Create', 'adb'),
                '',
                'btn-xs btn-discreet'
            )
        );

        $table->addBarContentLeft(
            Fields::text([
                'label' => i('Search', 'adb'),
                'key' => 'adb_search'
            ])
        );

        return $table;
    }

    private function update($type, $data) {
        $updater = new DataUpdater($type, $data);
        $updater->run();
        App::instance()->redirect(Utils::url(['manage', 'module-settings', 'archive-database', $type]));
    }

    private function editMask($bc) {
        if($this->detailId == 'new') {
            // create a new entry first.
            $newId = json_decode($bc->call(
                'GET',
                'create/'.$this->baseView.'/?key='.Settings::get('adb-base-api-key')
            ));
            $this->detailId = $newId->done;
        }
        $data = json_decode(
            $bc->call(
                'GET',
                'get/'.$this->baseView.'/filter/?type=EQUALS&field=id&value='.$this->detailId.'&displayAll'
            )
        );
        $f = new EditForm($data, $bc);
        return $f->render();
    }

    public function modifyDetailViewArgs($args) {
        $args['subnavigation'] = false;
        $args['title'] = sprintf(i('Edit %1$s', 'adb'), i($this->detailId));
        return $args;
    }

    public function modifyDeleteViewArgs($args) {

    }

    public function modifyManageNavigation($navigation) {
        if(Auth::allowed('manage.archive-database')) {
            $navigation->add(
                'adb',
                i('Archive Database', 'adb'),
                Utils::getUrl(['manage', 'module-settings', 'archive-database']),
                'leftPanel',
                'folder_special'
            );
        }
        $navigation->reorder('leftPanel', 'adb', 1);
        return $navigation;
    }

    private function registerSettings() {
        $this->settings->registerField(
            Fields::text(array(
            'key' => 'adb-base-url',
            'label' => i('Database URL', 'adb'),
            'hint' => i('The URL which is to request for the data.', 'adb')
        ), Settings::get('adb-base-url')), 'adb-base-url', 'left', 'archive-database');

        $this->settings->registerField(
            Fields::text(array(
            'key' => 'adb-base-api-key',
            'label' => i('Database API Key', 'adb'),
            'hint' => i('This is a security key, which is used for authentification.', 'adb')
        ), Settings::get('adb-base-api-key')), 'adb-base-api-key', 'left', 'archive-database');

        $this->settings->registerField(
            Fields::text(array(
            'key' => 'adb-base-hide-fields-in-table',
            'label' => i('Disable Fields for Images Overview.', 'adb'),
            'hint' => i('Given fields will not be displayed. Multiple fields split by `,`.', 'adb')
        ), Settings::get('adb-base-hide-fields-in-table')), 'adb-base-hide-fields-in-table', 'left', 'archive-database');

        $this->settings->registerField(
            Fields::text(array(
            'key' => 'adb-grid-fields',
            'label' => i('Fields, to display in the grid.', 'adb'),
            'hint' => i('All fields, which will be displayed in the grid. Multiple fields split by `,`.', 'adb')
        ), Settings::get('adb-grid-fields')), 'adb-grid-fields', 'left', 'archive-database');
    }

}

?>