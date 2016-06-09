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

[1]: https://ibrows.codebasehq.com/projects/ibrowsch/repositories/ibrowsboxalinobundle/raw/master/Resources/doc/files/XML_data_property_file_definition.pdf
[2]: https://ibrows.codebasehq.com/projects/ibrowsch/repositories/ibrowsboxalinobundle/blob/master/Resources/doc/commands.md


