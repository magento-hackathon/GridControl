GridControl
===========

Ralf, Ronald, Martin, Bastian

Info:
http://www.webguys.de/magento/turchen-23-pimp-my-produktgrid/

Goals
-----

- remove blocks (removeColumn())
- add blocks (addColumn())
- move blocks (addColumnsOrder())

- extend collection (join/addAttribute)
- modify renderer

- role based grid layout



someday... admin grid configurator

XML Syntax
----------
gridcontrol.xml in module etc folder:

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
                <action (remove|move)>
                    parameters
                </action>
            </column>
        </gridname>
    </grids>
</gridcontrol>
