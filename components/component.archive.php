<?php

namespace Forge\Modules\ArchiveDatabase;

use Forge\Core\Abstracts\Component;
use Forge\Core\App\App;
use Forge\Core\Classes\ContentNavigation;
use Forge\Core\Classes\Fields;
use Forge\Core\Classes\Media;
use Forge\Core\Classes\Settings;
use Forge\Modules\ArchiveDatabase\BaseConnector;
use Forge\Modules\ArchiveDatabase\Table;


class ArchiveComponent extends Component {
    public $settings = [];
    public function prefs() {
        $this->settings = [
            [
                "label" => i('Title', 'adb'),
                "hint" => 'Title, which will be displayed on top of the database.',
                'key' => 'title',
                'type' => 'text',
            ]
        ];
        return array(
            'name' => i('Image Archive', 'rodo-theme'),
            'description' => i('The Complete Archive, with filters and search.', 'rodo-theme'),
            'id' => 'adb_main',
            'image' => '',
            'level' => 'inner',
            'container' => false
        );
    }

    public function content() {
        /* table */
        $t = 'images';
        $bc = BaseConnector::instance();
        $table = new Table(json_decode($bc->call('GET', 'get/'.$t)));
        $table->setId($t);
        $table->setFields(explode(",", Settings::get('adb-grid-fields')));
        $table->switchToGrid();
        $table->hideActions();
        $table->addEditBar();
        $table = $this->barContent($table);


        return App::instance()->render(MOD_ROOT."/archive-database/templates/", "adb", [
            'title' => $this->getField('title'),
            'images' => $table->render()
        ]);
    }

    public function customBuilderContent() {
        return App::instance()->render(CORE_TEMPLATE_DIR."components/builder/", "text", array(
            'text' => i('Archive Database', 'abd')
        ));
    }

    private function barContent($table) {
        $table->addBarContentRight(
            Fields::text([
                'label' => i('Search', 'adb'),
                'key' => 'adb_search'
            ])
        );

        return $table;
    }
}
?>