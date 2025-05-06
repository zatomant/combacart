<?php

namespace Comba\Bundle\Modx;

use DocumentParser;

/**
 * Треба переписати цей клас без використання MODxAPI\modResource
 * тому, що:
 * - тягне дуже багато залежностей
 * - в 3+ інші таблиці
 * - треба пакет DocLister і відсутній MODxAPI
 *
 * Наразі помістив модифікований modResource та візок його залежностей у /lib/ex/
 *
 */

class ModxResource
{
    private DocumentParser $_modx;

    private $modxGet;
    private $modxSet;

    public function __construct(?DocumentParser $modx = null)
    {
        $this->setModx($modx);

        if (class_exists('DocumentManager')) {
            $this->modxSet = new \EvolutionCMS\DocumentManager\Services\DocumentManager();
            $this->modxGet = new \EvolutionCMS\Models\SiteContent();
        } else {
            $this->modxGet = $this->modxSet = new \MODxAPI\modResource($modx);
        }
    }

    public function clearCache(string $type = 'full'): ModxResource
    {
        $this->getModx()->clearCache($type);
        return $this;
    }

    public function getModx(): DocumentParser
    {
        return $this->_modx;
    }

    public function setModx(DocumentParser $modx): ModxResource
    {
        $this->_modx = $modx;
        return $this;
    }

    /**
     * @param $args
     */
    public function openForEdit(int $id)
    {
        // 1 id
        if ($this->modxGet instanceof \MODxAPI\modResource){
            $this->modxGet->edit($id);
        }
    }

    /**
     * @param $args
     * @return mixed
     */
    public function getTV($args)
    {
        // 1 value
        if ($this->modxGet instanceof \MODxAPI\modResource){
            return $this->modxGet->get($args['key']);
        }
        // 2 array
        if ($this->modxGet instanceof \EvolutionCMS\Models\SiteContent) {
            $result = \EvolutionCMS\Models\SiteContent::where('id', $args['id'])->active()->get();
            $result = \EvolutionCMS\Models\SiteContent::tvList($result, [$args['key']]);
            foreach ($result as $item) {
                if (!empty($item['tvs'][$args['key']])) {
                    return $item['tvs'][$args['key']];
                }
            }
        }
    }

    /**
     * @param $args
     */
    public function set($args)
    {
        // 1 modx $key, $value
        if ($this->modxSet instanceof \MODxAPI\modResource){
            $this->modxSet->set($args['key'], $args['value']);
        }
        // 2 array $userData, bool $events = true, bool $cache = true
        if ($this->modxSet instanceof \EvolutionCMS\DocumentManager\Services\DocumentManager) {
            $this->modxSet->edit(['id' => $args['id'], $args['key'] => $args['value']], false);
        }
    }

    /**
     * @param $args
     */
    public function save()
    {
        // 1 $fire_events = false, $clearCache = false
        if ($this->modxSet instanceof \MODxAPI\modResource){
            $this->modxSet->save();
        }
        // 2 none
    }

}
