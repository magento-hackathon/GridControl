<?php
/**
 * main class, containts column actions loike add, after and remove
 *
 * @todo check for remove and add on one column entity
 * @todo find a better way to call _prepareCollection (without reflection)
 */
class Hackathon_GridControl_Model_Processor
{
    /**
     * processes the grid block, checks gridcontrol configuration for updates on this block and calls column actions
     *
     * @param Mage_Adminhtml_Block_Widget $block
     */
    public function processBlock($block)
    {
        $config = Mage::getSingleton('hackathon_gridcontrol/config')->getConfig();

        $blockConfig = $config->getNode('grids/' . $block->getId());
     
        $filterToMaps = false;
        // process columns
        foreach ($blockConfig->children() as $column) {
            // process column actions
            if($column->getName() == 'add_filter_to_map') {
                $filterToMaps = $column;
                continue;
            }
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

        // register current block, needed to extend the collection in Hackathon_GridControl_Model_Observer
        Mage::register('hackathon_gridcontrol_current_block', $block);
        // call _prepareCollection to reload the collection and apply column filters
        $this->_callProtectedMethod($block, '_prepareCollection');

        if($filterToMaps) {
            $filterToMapsArray = (array)$filterToMaps;
            $block->getCollection()->addFilterToMap($filterToMapsArray['column'], $filterToMapsArray['alias']);
        }

        // remove current block to prevent race conditions in later collection loads
        Mage::unregister('hackathon_gridcontrol_current_block');
    }

    /**
     * remove column from grid
     *
     * @param Varien_Object $params
     */
    protected function _removeAction($params)
    {
        $params->getBlock()->removeColumn($params->getColumn()->getName());
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
         * @var Hackathon_GridControl_Model_Config $config
         */
        $config = Mage::getSingleton('hackathon_gridcontrol/config');

        foreach ($params->getAction()->children() as $attribute) {
            // 4 special cases
            if ($attribute->getName() == 'index') {
                $config->addCollectionUpdate(Hackathon_GridControl_Model_Config::TYPE_ADD_ATTRIBUTE, $blockId, (string) $attribute);
            } else if ($attribute->getName() == 'joinAttribute') {
                $config->addCollectionUpdate(Hackathon_GridControl_Model_Config::TYPE_JOIN_ATTRIBUTE, $blockId, (string) $attribute);
                continue;
            } else if ($attribute->getName() == 'joinField') {
                $config->addCollectionUpdate(Hackathon_GridControl_Model_Config::TYPE_JOIN_FIELD, $blockId, (string) $attribute);
                continue;
            } else if ($attribute->getName() == 'join') {
                $config->addCollectionUpdate(Hackathon_GridControl_Model_Config::TYPE_JOIN, $blockId, array(
                    'table' => (string) $attribute['table'],
                    'condition' => (string) $attribute['condition'],
                    'field' => (string) $attribute['field'],
                    'array_cols' => array((string) $attribute['field']),
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
                if($attribute->getName() == 'filter_condition_callback') {
                    $variables = explode('::', $attribute);
                    if(is_array($variables)){
                        $columnConfig[$attribute->getName()] = array(Mage::getModel($variables[0]), (string) $variables[1]);
                    }
                }
                else {
                    // standard string attribute
                    $columnConfig[$attribute->getName()] = (string) $attribute;
                }
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
}