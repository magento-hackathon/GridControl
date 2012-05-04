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
    protected $debug = false;
    
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
           Mage::getModel('hackathon_gridcontrol/processor')->processBlock($block, true);
       } 
    }
    
    /**
     * observe adminhtml_block_html_before
     *
     * @param Varien_Event_Observer $event
     * @return void
     */
    public function adminhtmlBlockExportBefore(Varien_Event_Observer $event)
    {
        $block = $event->getBlock();
        
        if (in_array($block->getId(), Mage::getSingleton('hackathon_gridcontrol/config')->getGridList())) {
           Mage::getModel('hackathon_gridcontrol/processor')->processBlock($block);
           
           // get the block
           $blockId = $block->getId();
           
           // modify collection query
           $this->__modifyCollection($block,$blockId);
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
        $columnJoinField = array();
        
        if (Mage::registry('hackathon_gridcontrol_current_block')) {
           
           // get the block
           $blockId = Mage::registry('hackathon_gridcontrol_current_block')->getId();
           $block = Mage::registry('hackathon_gridcontrol_current_block');
           
           // modify collection query
           $this->__modifyCollection($block,$blockId);
        }
    }
    
    protected function __modifyCollection($block,$blockId) {
              

            /**
             * @var Hackathon_GridControl_Model_Config $config
             */
            $config = Mage::getSingleton('hackathon_gridcontrol/config');

            // add attributes to collection
            //foreach ($config->getCollectionUpdates(Hackathon_GridControl_Model_Config::TYPE_ADD_ATTRIBUTE, $blockId) as $entry) {
                //$event->getCollection()->addAttributeToSelect($entry);
            //}

            // join attributes to collection
            foreach ($config->getCollectionUpdates(Hackathon_GridControl_Model_Config::TYPE_JOIN_ATTRIBUTE, $blockId) as $attribute) {
                $attribute = explode('|', $attribute);
                // 5 parameters needed for joinAttribute()
                if (count($attribute) < 5) {
                    continue;
                }
                try {
                    $block->getCollection()->joinAttribute(
                        $attribute[0],
                        $attribute[1],
                        $attribute[2],
                        (strlen($attribute[3]) ? $attribute[3] :null),
                        $attribute[4]
                    );
                } catch (Exception $e) { /* echo $e->getMessage(); */ }
            }

            // join fields to collection
            foreach ($config->getCollectionUpdates(Hackathon_GridControl_Model_Config::TYPE_JOIN_FIELD, $blockId) as $field) {
                $field = explode('|', $field);
                // 6 parameters needed for joinField()
                if (count($field) < 6) {
                    continue;
                }
                try {
                    $block->getCollection()->joinField(
                        $field[0],
                        $field[1],
                        $field[2],
                        $field[3],
                        $field[4],
                        $field[5]
                    );
                } catch (Exception $e) { /* echo $e->getMessage(); */ }
            }

            // joins to collection
            foreach ($config->getCollectionUpdates(Hackathon_GridControl_Model_Config::TYPE_JOIN, $blockId) as $field) {


                try {
                    // normal join
                    $block->getCollection()->join(
                        $field['table'],
                        str_replace('{{table}}', '`' . $field['table'] . '`', $field['condition']),
                        $field['field']); // FIXME use direct CONCAT
                    
                    $columnJoinField[$field['column']] = $field['field'];
                                        
                    // get where
                    if(isset($field['where'])) {
                    $field['where'] = str_replace('{{table}}', '`' . $field['table'] . '`', $field['where']);
                    $whereParts = explode('|', $field['where']);
    
                        // add attribute filter
                        if(isset($whereParts[0]) && isset($whereParts[1]) && isset($whereParts[2])) {
                            $block->getCollection()->addAttributeToFilter($whereParts[0], array($whereParts[1] => $whereParts[2]));
                        }
                    }
                    
                    // FIXME or use own renderer
                    
                    // with alias, concat
                    if(isset($field['alias'])) {
                        $block->getCollection()->addExpressionFieldToSelect(
                            $field['alias'],
                        		'CONCAT(firstname, " ",lastname, street)',
                            array());
                        $columnJoinField[$field['column']] = $field['alias'];
                    }
                   
                } catch (Exception $e) { /* echo $e->getMessage(); */ }
            }

            if(true === $this->debug) {
                // debug
                echo (string) $block->getCollection()->getSelect();
            }

            // update index from join_index (needed for joins)
            foreach ($block->getColumns() as $column) {
                if (isset($columnJoinField[$column->getId()])) {
                   $column->setIndex($columnJoinField[$column->getId()]);
                }
            }
    }
}