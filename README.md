GridControl
===========

Members
-------
Ralf, Ronald, Marten, Bastian

Features
--------
- add columns
- remove columns
- change columns
- move columns
- add new attributes
- join new attributes
- update columns

Todo
-----
- role based grid layout
- configuration GUI (?)

XML Syntax
----------
`gridcontrol.xml` in module etc folder:

``````xml
<?xml version="1.0"?>
<gridcontrol>
    <grids>
        <product.grid>
            <entity_id>
                <remove/>
            </entity_id>

            <sku>
                <after>name</after>
            </sku>
        </product.grid>

        <gridname>
            <column>
                <remove/>

                <after>columnname</after>

                <add>
                    <header>Column Title</header>
                    <type>text</type>
                    <index>qty</index>
                    <!--<joinAttribute>status|catalog_product/status|entity_id||inner</joinAttribute>-->
                    <joinField>qty|cataloginventory/stock_item|qty|product_id=entity_id|{{table}}.stock_id=1|left</joinField>
                </add>
            </column>

            <existingColumn>
                <updateColumn>
                    <attributeToUpdate>newValue</attributeToUpdate>
                </updateColumn>
            </existingColumn>
            <real_order_id>
                <updateColumn>
                    <filter_index>main_table.increment_id</filter_index>
                </updateColumn>
            </real_order_id>

            <manufacturer>
                <add>
                    <header>Manufacturer</header>
                    <type>options</type>
                    <index>manufacturer</index>
                    <options>firegento_gridcontrol/utility::getDropdownAttributeLabelOptionArray(manufacturer)</options>
                </add>
            </manufacturer>

            <selecttest>
                <add>
                    <header>Status Column</header>
                    <type>options</type>
                    <options>
                        <option_a>
                            <key>1</key>
                            <value>Active</value>
                        </option_a>

                        <option_b>
                            <key>2</key>
                            <value>Inactive</value>
                        </option_b>
                    </options>
                    <index>status</index>
                </add>
                <after>column</after>
            </selecttest>
        </gridname>
    </grids>
</gridcontrol>
``````
