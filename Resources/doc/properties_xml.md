Properties XML
==============


The Data Intelligence XML Data profile file (properties.xml) provides the required information to:  

* Read the information out of the data sent to the Data Intelligence (typically in a archive zip file containing several CSV files)
* Generate the required properties out of the data provided (e.g.: product titles comes from file A.csv, French label is in column
    "title_fr" and English label is in column "title_en")
* Prepare all the account configuration that any sending of the data will be automatically processed and loaded in Boxalino Intelligent
    Engine
    

For a full explanation of the properties.xml file, please check the [pdf documentation from boxalino][1]
    
        
The path to this file should be configured under the settings for the ibrows_boxalino bundle. A good place to put it would 
be in the app/config folder, or wherever else you keep general site configuration.

``` yaml
ibrows_boxalino:
   ....
    export:
        properties_xml: "%kernel.root_dir%/config/properties.xml" 
   .....     
```

It is also possible to generate the configuration, either through php, defining each field, or even by reading the structure
from your entity csv export files. More information about these possibilities can be found in the ychadwick/boxalino-client-SDK-php
in the folder examples, in the files starting with "backend_data_....php"

For information regarding sending the properties.xml to boxalino please read [the available symfony style commands][2]


Special field parameters
------------------------

* MultiValued:   
By default this field parameter is always set to true, and is used by boxalino to mark fields that will be used to rank
the relevance of the entry for your search.
If you want a field to be used to sort the entries (apart from name or price) you need to set the field parameter "multiValued"
to false. Setting the parameter to false will mean that it will no longer be used for ranking, but as you wish to sort the
entries with this field, the ranking of the field is irrelevant.

```xml
    ...
    <property id="create_date" type="number">
        <transform>
            <logic source="item_vals" type="direct">
                <field column="create_date"/>
            </logic>
        </transform>
        <params>
            <fieldParameter name="multiValued" value="false"/>
        </params>
    </property>
    ...
```

* propertyIndex:   
If you want to sort out entries in an autocomplete search result set, based on a match in a specific field (as opposed to 
just a general match), you need to set the field parameter "propertyIndex" to true. This will give you a second result set
of entries that match based solely on this field. This will allow you to differentiate how the found items where matched.

```xml
    ...
    <property id="author_lastname" type="text">
        <transform>
            <logic source="item_vals" type="reference">
                <field column="author_id"/>
            </logic>
        </transform>
        <params>
            <referenceSource value="resource_author_firstname"/>
            <fieldParameter name="propertyIndex" value="true"/>
        </params>
    </property>
    ... 
```

A use case for this is demonstrated in the go-do-it.ch project where we wanted to show a list of projects that matched 
a search request plus differentiate project authors that also match the search request. 

[1]: https://ibrows.codebasehq.com/projects/ibrowsch/repositories/ibrowsboxalinobundle/raw/master/Resources/doc/files/XML_data_property_file_definition.pdf
[2]: https://ibrows.codebasehq.com/projects/ibrowsch/repositories/ibrowsboxalinobundle/blob/master/Resources/doc/commands.md


