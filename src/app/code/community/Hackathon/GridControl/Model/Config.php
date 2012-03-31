<?php

class Hackathon_GridControl_Model_Config extends Varien_Object
{
    protected $_config = null;
    protected $_gridList = array();
    protected $_loadAttributes = array();
    protected $_joinAttributes = array();
    protected $_joinFields = array();

    protected function _loadConfig()
    {
        $gridcontrolConfig = new Varien_Simplexml_Config;
        $gridcontrolConfig->loadString('<?xml version="1.0"?><gridcontrol></gridcontrol>');
        $gridcontrolConfig = Mage::getConfig()->loadModulesConfiguration('gridcontrol.xml');
        $this->_config = $gridcontrolConfig;

        foreach ($this->_config->getNode('grids')->children() as $grid) {
            $this->_gridList[] = $grid->getName();
        }
    }

    public function getConfig()
    {
        if (is_null($this->_config)) {
            $this->_loadConfig();
        }

        return $this->_config;
    }

    public function getGridList()
    {
        if (is_null($this->_config)) {
            $this->_loadConfig();
        }

        return $this->_gridList;
    }

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

    public function getLoadAttributes($blockId)
    {
        if (!isset($this->_loadAttributes[$blockId])) {
            $this->_loadAttributes[$blockId] = array();
        }

        return $this->_loadAttributes[$blockId];
    }

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

    public function getJoinAttributes($blockId)
    {
        if (!isset($this->_joinAttributes[$blockId])) {
            $this->_joinAttributes[$blockId] = array();
        }

        return $this->_joinAttributes[$blockId];
    }

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

    public function getJoinFields($blockId)
    {
        if (!isset($this->_joinFields[$blockId])) {
            $this->_joinFields[$blockId] = array();
        }

        return $this->_joinFields[$blockId];
    }
}