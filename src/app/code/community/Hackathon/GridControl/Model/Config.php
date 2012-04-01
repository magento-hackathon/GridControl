<?php

class Hackathon_GridControl_Model_Config extends Varien_Object
{
    /**
     * @var null|Varien_Simplexml_Config
     */
    protected $_config = null;
    protected $_gridList = array();
    protected $_loadAttributes = array();
    protected $_joinAttributes = array();
    protected $_joinFields = array();
    protected $_joins = array();

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

    /**
     * add load_attribute id
     *
     * @param string $blockId
     * @param string $attribute
     * @return Hackathon_GridControl_Model_Config
     */
    public function addLoadAttribute($blockId, $attribute)
    {
        if (!isset($this->_loadAttributes[$blockId])) {
            $this->_loadAttributes[$blockId] = array();
        }

        if (!in_array($attribute, $this->_loadAttributes)) {
            $this->_loadAttributes[$blockId][] = $attribute;
        }

        return $this;
    }

    /**
     * return attributes to load
     *
     * @param string $blockId
     * @return array
     */
    public function getLoadAttributes($blockId)
    {
        if (!isset($this->_loadAttributes[$blockId])) {
            return array();
        }

        return $this->_loadAttributes[$blockId];
    }

    /**
     * add join attribute
     *
     * @param string $blockId
     * @param string $attribute
     * @return Hackathon_GridControl_Model_Config
     */
    public function addJoinAttribute($blockId, $attribute)
    {
        if (!isset($this->_joinAttributes[$blockId])) {
            $this->_joinAttributes[$blockId] = array();
        }

        if (!in_array($attribute, $this->_joinAttributes)) {
            $this->_joinAttributes[$blockId][] = $attribute;
        }

        return $this;
    }

    /**
     * return join attributes
     *
     * @param string $blockId
     * @return array
     */
    public function getJoinAttributes($blockId)
    {
        if (!isset($this->_joinAttributes[$blockId])) {
            return array();
        }

        return $this->_joinAttributes[$blockId];
    }

    /**
     * add join field
     *
     * @param string $blockId
     * @param string $attribute
     * @return Hackathon_GridControl_Model_Config
     */
    public function addJoinField($blockId, $attribute)
    {
        if (!isset($this->_joinFields[$blockId])) {
            $this->_joinFields[$blockId] = array();
        }

        if (!in_array($attribute, $this->_joinFields)) {
            $this->_joinFields[$blockId][] = $attribute;
        }

        return $this;
    }

    /**
     * return join fields
     *
     * @param string $blockId
     * @return array
     */
    public function getJoinFields($blockId)
    {
        if (!isset($this->_joinFields[$blockId])) {
            return array();
        }

        return $this->_joinFields[$blockId];
    }

    /**
     * add simple join
     *
     * @param string $blockId
     * @param string $attribute
     * @return Hackathon_GridControl_Model_Config
     */
    public function addJoin($blockId, $join)
    {
        if (!isset($this->_joins[$blockId])) {
            $this->_joins[$blockId] = array();
        }

        if (!in_array($join, $this->_joins)) {
            $this->_joins[$blockId][] = $join;
        }

        return $this;
    }

    /**
     * return joins
     *
     * @param string $blockId
     * @return array
     */
    public function getJoins($blockId)
    {
        if (!isset($this->_joins[$blockId])) {
            return array();
        }

        return $this->_joins[$blockId];
    }
}