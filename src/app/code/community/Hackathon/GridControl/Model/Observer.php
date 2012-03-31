<?php
/**
 * GridControl observer
 *
 * adminhtml_block_html_before:
 * checks if grid block id is found in gridcontrol config and, if found, pass a reference to the block to the gridcontrol processor
 *
 * eav_collection_abstract_load_before:
 * checks if current blockid is set to add joints and attributes to grid collection
 */
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

    /**
     * observes eav_collection_abstract_load_before to add attributes and joins to grid collection
     *
     * @param Varien_Event_Observer $event
     * @return void
     */
    public function eavCollectionAbstractLoadBefore(Varien_Event_Observer $event)
    {
        if (Mage::registry('hackathon_gridcontrol_current_blockid')) {
            $blockId = Mage::registry('hackathon_gridcontrol_current_blockid');

            // add attributes to collection
            foreach (Mage::getSingleton('hackathon_gridcontrol/config')->getLoadAttributes($blockId) as $attribute) {
                $event->getCollection()->addAttributeToSelect($attribute);
            }

            // join attributes to collection
            foreach (Mage::getSingleton('hackathon_gridcontrol/config')->getJoinAttributes($blockId) as $attribute) {
                $attribute = explode('|', $attribute);
                // 5 parameters needed for joinAttribute()
                if (count($attribute) < 5) {
                    continue;
                }
                try {
                    $event->getCollection()->joinAttribute(
                        $attribute[0],
                        $attribute[1],
                        $attribute[2],
                        (strlen($attribute[3]) ? $attribute[3] :null),
                        $attribute[4]
                    );
                } catch (Exception $e) { /* echo $e->getMessage(); */ }
            }

            // join fields to collection
            foreach (Mage::getSingleton('hackathon_gridcontrol/config')->getJoinFields($blockId) as $field) {
                $field = explode('|', $field);
                // 3 parameters needed for join()
                if (count($field) < 3) {
                    continue;
                }
                try {
                    $event->getCollection()->join(
                        $field[0],
                        $field[1],
                        $field[2]
                    );

                    echo (string) $event->getCollection()->getSelect();
                } catch (Exception $e) { /* echo $e->getMessage(); */ }
            }

            echo (string) $event->getCollection()->getSelect();
        }
    }
}