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

        // register current block id, needed to extend the collection in Hackathon_GridControl_Model_Observer
        Mage::register('hackathon_gridcontrol_current_blockid', $block->getId());
        // call _prepareCollection to reload the collection and apply column filters
        $this->_callProtectedMethod($block, '_prepareCollection');
        // remove current blockid to prevent race conditions in later collection loads
        Mage::unregister('hackathon_gridcontrol_current_blockid');
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

        foreach ($params->getAction()->children() as $attribute) {
            // 3 special cases
            if ($attribute->getName() == 'index') {
                Mage::getSingleton('hackathon_gridcontrol/config')->addLoadAttribute($blockId, (string) $attribute);
            } else if ($attribute->getName() == 'joinAttribute') {
                Mage::getSingleton('hackathon_gridcontrol/config')->addJoinAttribute($blockId, (string) $attribute);
            } else if ($attribute->getName() == 'joinField') {
                Mage::getSingleton('hackathon_gridcontrol/config')->addJoinField($blockId, (string) $attribute);
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
     * allows to invoke protected methods
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