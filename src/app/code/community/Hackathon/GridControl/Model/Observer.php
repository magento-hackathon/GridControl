<?php

class Hackathon_GridControl_Model_Observer
{
    /**
     * observe adminhtml_block_html_before
     *
     * @param Varien_Event_Observer $event
     * @return void
     */
    public function adminhtmlBlockHtmlBefore(Varien_Event_Observer $event)
    {
        $block = $event->getBlock();

        if (in_array($block->getNameInLayout(), Mage::getSingleton('hackathon_gridcontrol/config')->getGridList())) {
            Mage::getModel('hackathon_gridcontrol/processor')->processBlock($block);
        }
    }
}