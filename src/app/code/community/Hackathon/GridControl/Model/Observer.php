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

        if (in_array($block->getId(), Mage::getSingleton('hackathon_gridcontrol/config')->getGridList())) {
            Mage::getModel('hackathon_gridcontrol/processor')->processBlock($block);
        }
    }

    public function eavCollectionAbstractLoadBefore(Varien_Event_Observer $event)
    {
        if (Mage::registry('hackathon_gridcontrol_current_blockid')) {
            foreach (Mage::getSingleton('hackathon_gridcontrol/config')->getLoadAttributes() as $attribute) {
                $event->getCollection()->addAttributeToSelect($attribute);
            }

            foreach (Mage::getSingleton('hackathon_gridcontrol/config')->getJoinAttributes() as $attribute) {
                $attribute = explode('|', $attribute);
                try {
                    $event->getCollection()->joinAttribute($attribute[0], $attribute[1], $attribute[2], (strlen($attribute[3]) ? $attribute[3] :null), $attribute[4]);
                } catch (Exception $e) { }
            }

            foreach (Mage::getSingleton('hackathon_gridcontrol/config')->getJoinFields() as $field) {
                $field = explode('|', $field);
                try {
                    $event->getCollection()->joinField(
                        $field[0],
                        $field[1],
                        $field[2],
                        $field[3],
                        $field[4],
                        $field[5]
                    );
                } catch (Exception $e) { }
            }
        }
    }
}