<?php
/**
 * main class, containts column actions loike add, after and remove
 *
 * @todo check for remove and add on one column entity
 * @todo find a better way to call _prepareCollection (without reflection)
 */
class FireGento_GridControl_Model_Processor
{
    /**
     * processes the grid block, checks gridcontrol configuration for updates on this block and calls column actions
     *
     * @param Mage_Adminhtml_Block_Widget $block
     */
    public function processBlock($block)
    {
        $config = Mage::getSingleton('firegento_gridcontrol/config')->getConfig();

        $blockConfig = $config->getNode('grids/' . $block->getId());

        // process columns
        foreach ($blockConfig->children() as $column) {
            // process column actions
            foreach ($column->children() as $action) {
                // create method name
                $func = '_' . $action->getName() . 'Action';
                $funcArr = array($this, $func);

                if (!is_callable($funcArr)) {
                    continue;
                }

                // call function and give a reference to the actual block, column and action
                call_user_func($funcArr, new Varien_Object(array(
                    'block' => $block,
                    'action' => $action,
                    'column' => $column,
                )));
            }
        }

        // resort columns
        $block->sortColumnsByOrder();

        // register current block, needed to extend the collection in FireGento_GridControl_Model_Observer
        Mage::register('firegento_gridcontrol_current_block', $block);
        // call _prepareCollection to reload the collection and apply column filters
        $this->_callProtectedMethod($block, '_prepareCollection');
        // remove current block to prevent race conditions in later collection loads
        Mage::unregister('firegento_gridcontrol_current_block');
    }

    /**
     * remove column from grid
     *
     * @param Varien_Object $params
     */
    protected function _removeAction($params)
    {
        if (method_exists($params->getBlock(), 'removeColumn')){
            $params->getBlock()->removeColumn($params->getColumn()->getName());
        } else {
            $this->removeGridColumn($params->getBlock(), $params->getColumn()->getName());
        }
    }

    /**
     * sort column after another one
     *
     * @param Varien_Object $params
     */
    protected function _afterAction($params)
    {
        $params->getBlock()->addColumnsOrder($params->getColumn()->getName(), (string) $params->getAction());
    }

    /**
     * add new column, additional attributes and joins are stored on the configuration singleton to add them at the event observer
     *
     * @param Varien_Object $params
     */
    protected function _addAction($params)
    {
        $columnConfig = array();
        $blockId = $params->getBlock()->getId();
        /**
         * @var FireGento_GridControl_Model_Config $config
         */
        $config = Mage::getSingleton('firegento_gridcontrol/config');

        foreach ($params->getAction()->children() as $attribute) {
            // 4 special cases
            if ($attribute->getName() == 'index') {
                $config->addCollectionUpdate(FireGento_GridControl_Model_Config::TYPE_ADD_ATTRIBUTE, $blockId, (string) $attribute);
            } else if ($attribute->getName() == 'joinAttribute') {
                $config->addCollectionUpdate(FireGento_GridControl_Model_Config::TYPE_JOIN_ATTRIBUTE, $blockId, (string) $attribute);
                continue;
            } else if ($attribute->getName() == 'joinField') {
                $config->addCollectionUpdate(FireGento_GridControl_Model_Config::TYPE_JOIN_FIELD, $blockId, (string) $attribute);
                continue;
            } else if ($attribute->getName() == 'join') {
                $config->addCollectionUpdate(FireGento_GridControl_Model_Config::TYPE_JOIN, $blockId, array(
                    'table' => (string) $attribute['table'],
                    'condition' => (string) $attribute['condition'],
                    'field' => (string) $attribute['field'],
                    'column' => $params->getColumn()->getName(),
                ));
                continue;
            } else if ($attribute->getName() == 'options') {
                if (strpos((string) $attribute, '::') !== false) {
                    list($_module, $_method) = explode('::', (string) $attribute);
                    $_module = Mage::getSingleton($_module);
                    $_call = array($_module, $_method);
                    if (is_callable($_call)) {
                        $columnConfig['options'] = call_user_func($_call);
                        continue;
                    }
                }
            }

            if (count($attribute->children())) {
                // in case of arrays as attribute values
                $optionArray = array();
                foreach ($attribute->children() as $option) {
                    $optionArray[(string) $option->key] = (string) $option->value;
                }
                $columnConfig[$attribute->getName()] = $optionArray;
            } else {
                // standard string attribute
                $columnConfig[$attribute->getName()] = (string) $attribute;
            }
        }

        // add column to grid block
        $params->getBlock()->addColumn($params->getColumn()->getName(), $columnConfig);
    }

    /**
     * allows invoking protected methods
     *
     * @param $object
     * @param string $methodName
     * @return mixed
     */
    protected function _callProtectedMethod($object, $methodName)
    {
        $reflection = new ReflectionClass($object);
        $method = $reflection->getMethod($methodName);
        $method->setAccessible(true);
        return $method->invoke($object);
    }

    /**
     * gets reflected property
     *
     * @param Mage_Adminhtml_Block_Widget_Grid $grid
     * @param string $propertyName
     * @return ReflectionProperty
     */
    private function getReflectedProperty(Mage_Adminhtml_Block_Widget_Grid $grid, $propertyName)
    {
        $reflection = new ReflectionClass($grid);
        $property = $reflection->getProperty($propertyName);
        $property->setAccessible(true);
        return $property;
    }

    /**
     * gets a value of protected property on a grid object
     *
     * @param Mage_Adminhtml_Block_Widget_Grid $grid
     * @param string $propertyName
     * @return array
     */
    private function getGridProtectedPropertyValue(Mage_Adminhtml_Block_Widget_Grid $grid, $propertyName)
    {
        $property = $this->getReflectedProperty($grid, $propertyName);
        return $property->getValue($grid);
    }

    /**
     * sets a value of protected property on a grid object
     *
     * @param Mage_Adminhtml_Block_Widget_Grid $grid
     * @param string $propertyName
     * @param mixed $propertyValue
     * @return Mage_Adminhtml_Block_Widget_Grid
     */
    private function setGridProtectedPropertyValue(
        Mage_Adminhtml_Block_Widget_Grid $grid,
        $propertyName,
        $propertyValue
    )
    {
        $property = $this->getReflectedProperty($grid, $propertyName);
        $property->setValue($grid, $propertyValue);
    }

    /**
     * removes a column from grid if grid doesn't have any method to do it (i.e. Magento 1.5)
     *
     * @param Mage_Adminhtml_Block_Widget_Grid $grid
     * @param string $columnName
     */
    protected function removeGridColumn(Mage_Adminhtml_Block_Widget_Grid $grid, $columnName)
    {
        $columnsPropertyName = '_columns';
        $lastColumnIdPropertyName = '_lastColumnId';

        $columns = $this->getGridProtectedPropertyValue($grid, $columnsPropertyName);
        $lastColumnId = $this->getGridProtectedPropertyValue($grid, $lastColumnIdPropertyName);

        if (isset($columns[$columnName])) {
            unset($columns[$columnName]);
            if ($lastColumnId == $columnName){
                $lastColumnId = key($columns);
            }
        }

        $this->setGridProtectedPropertyValue($grid, $columnsPropertyName, $columns);
        $this->setGridProtectedPropertyValue($grid, $lastColumnIdPropertyName, $lastColumnId);
    }
}