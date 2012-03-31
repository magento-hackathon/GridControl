<?php

class Hackathon_GridControl_Model_Processor
{
    public function processBlock($block)
    {
        $config = Mage::getSingleton('hackathon_gridcontrol/config')->getConfig();

        $blockConfig = $config->getNode('grids/' . $block->getNameInLayout());

        foreach ($blockConfig->children() as $column) {
            $columnName = $column->getName();

            foreach($column->children() as $action) {
                switch ($action->getName()) {
                    case 'remove':
                        $block->removeColumn($columnName);
                        break;

                    case 'after':
                        $block->addColumnsOrder($columnName, (string) $action);
                        break;
                }
            }
        }

        $block->sortColumnsByOrder();
    }
}