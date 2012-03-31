<?php

class Hackathon_GridControl_Model_Processor
{
    /**
     *
     * @todo check for remove and add on one column entity
     * @param $block
     */
    public function processBlock($block)
    {
        $config = Mage::getSingleton('hackathon_gridcontrol/config')->getConfig();

        $blockConfig = $config->getNode('grids/' . $block->getId());

        foreach ($blockConfig->children() as $column) {
            foreach ($column->children() as $action) {
                $func = '_' . $action->getName() . 'Action';

                $func = array($this, $func);

                if (!is_callable($func)) {
                    continue;
                }

                call_user_func($func, new Varien_Object(array(
                    'block' => $block,
                    'action' => $action,
                    'column' => $column,
                )));
            }
        }

        $block->sortColumnsByOrder();

        Mage::register('hackathon_gridcontrol_current_blockid', $block->getId());
        $this->_callProtectedMethod($block, '_prepareCollection');
        Mage::unregister('hackathon_gridcontrol_current_blockid');
    }

    protected function _removeAction($params)
    {
        $params->getBlock()->removeColumn($params->getColumn()->getName());
    }

    protected function _afterAction($params)
    {
        $params->getBlock()->addColumnsOrder($params->getColumn()->getName(), (string) $params->getAction());
    }

    protected function _addAction($params)
    {
        $arr = array();

        foreach ($params->getAction()->children() as $option) {
            if ($option->getName() == 'index') {
                Mage::getSingleton('hackathon_gridcontrol/config')->addLoadAttribute((string) $option);
            } else if ($option->getName() == 'joinAttribute') {
                Mage::getSingleton('hackathon_gridcontrol/config')->addJoinAttribute((string) $option);
            } else if ($option->getName() == 'joinField') {
                Mage::getSingleton('hackathon_gridcontrol/config')->addJoinField((string) $option);
            }

            if (count($option->children())) {
                $optionarray = array();
                foreach ($option->children() as $optionvalue) {
                    $optionarray[(string) $optionvalue->key] = (string) $optionvalue->value;
                }
                $arr[$option->getName()] = $optionarray;
            } else {
                $arr[$option->getName()] = (string) $option;
            }
        }

        $params->getBlock()->addColumn($params->getColumn()->getName(), $arr);
    }

    protected function _callProtectedMethod($object, $methodName)
    {
        $reflection = new ReflectionClass($object);
        $method = $reflection->getMethod($methodName);
        $method->setAccessible(true);
        return $method->invoke($object);
    }
}