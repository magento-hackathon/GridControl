<?php
/**
 * loads gridcontrol.xml configuration and stores collection updates
 */
class Hackathon_GridControl_Model_Config extends Varien_Object
{
    /**
     * @var null|Varien_Simplexml_Config $_config
     */
    protected $_config = null;
    protected $_gridList = array();
    protected $_collectionUpdates = array();

    const TYPE_ADD_ATTRIBUTE = 'addAttribute';
    const TYPE_JOIN_FIELD = 'joinField';
    const TYPE_JOIN_ATTRIBUTE = 'joinAttribute';
    const TYPE_JOIN = 'join';

    /**
     * stores collection updates
     *
     * @param string $type
     * @param string $block
     * @param string $value
     * @return Hackathon_GridControl_Model_Config
     */
    public function addCollectionUpdate($type, $block, $value)
    {
        if (!isset($this->_collectionUpdates[$type])) {
            $this->_collectionUpdates[$type] = array();
        }

        if (!isset($this->_collectionUpdates[$type][$block])) {
            $this->_collectionUpdates[$type][$block] = array();
        }

        if (!in_array($value, $this->_collectionUpdates[$type][$block])) {
            $this->_collectionUpdates[$type][$block][] = $value;
        }


        return $this;
    }


    /**
     * Added by WebShopApps
     * Note: this currently only works for upto 2 new columns
     * @param $type
     * @param $block
     * @return $this
     */
    public function setSharedFields($type, $block) {
        $firstTimeRound = true;
        $previousKey = null;
        foreach ($this->_collectionUpdates[$type][$block] as $key=>$collUpdate) {

            if ($firstTimeRound) {
                $previousKey = $key;
                $firstTimeRound = false;
                continue;
            }

            if ($this->_collectionUpdates[$type][$block][$previousKey]['table']==$collUpdate['table']) {
                // same table, lets join the columns on both
                $currColArr = $this->_collectionUpdates[$type][$block][$previousKey]['array_cols'];
                $currColArr[] = $collUpdate['field'];
                $this->_collectionUpdates[$type][$block][$previousKey]['array_cols']=$currColArr;
                $collUpdate['array_cols']=$currColArr;
            }
            $previousKey = $key;
        }
        return $this;
    }
    /**
     * returns collection updates
     *
     * @param string $type
     * @param string $block
     * @return array
     */
    public function getCollectionUpdates($type, $block)
    {
        if (!isset($this->_collectionUpdates[$type])) {
            return array();
        }

        if (!isset($this->_collectionUpdates[$type][$block])) {
            return array();
        }

        return $this->_collectionUpdates[$type][$block];
    }

    /**
     * load gridcontrol.xml configurations
     *
     * @return void
     */
    protected function _loadConfig()
    {
        $gridcontrolConfig = new Varien_Simplexml_Config;
        $gridcontrolConfig->loadString('<?xml version="1.0"?><gridcontrol></gridcontrol>');
        $gridcontrolConfig = Mage::getConfig()->loadModulesConfiguration('gridcontrol.xml');
        $this->_config = $gridcontrolConfig;

        // collect affected grid id's
        foreach ($this->_config->getNode('grids')->children() as $grid) {
            $this->_gridList[] = $grid->getName();
        }
    }

    /**
     * load config if needed and return config
     *
     * @return Varien_Simplexml_Config
     */
    public function getConfig()
    {
        if (is_null($this->_config)) {
            $this->_loadConfig();
        }

        return $this->_config;
    }

    /**
     * load config if needed and return grid id's
     *
     * @return array
     */
    public function getGridList()
    {
        if (is_null($this->_config)) {
            $this->_loadConfig();
        }

        return $this->_gridList;
    }
}