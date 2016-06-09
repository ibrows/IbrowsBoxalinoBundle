IbrowsBoxalinoBundle - Boxalino Search
======================================

The IbrowsBoxalinoBundle allows you to export your entities and chosen entity fields to boxalino, offers a service for 
search and autocomplete functions offered by boxalino, and a twig extension of the Javascrpt tracking.


Install & setup the bundle
--------------------------

1.  Fetch the source code


    ``` bash
    $ php composer.phar require ibrows/boxalino-bundle 
    ```
	
	Composer will install the bundle to your project's `ibrows/boxalino-bundle` directory.


2.  Add the bundle to your `AppKernel` class

    ``` php

    // app/AppKernerl.php
    public function registerBundles()
    {
        $bundles = array(
            // ...
            new Ibrows\BoxalinoBundle\IbrowsBoxalinoBundle(),
            // ...
        );
        // ...
    }
    
    ```

Configuration
---------------------

### Setup credentials, paths and entities
    ibrows_boxalino:
        db_driver: "orm" #Default value
        
        #used to extract translatable data from an entity
        translation_locales: [en] #Default value
        
        #boxalino credentials
        access:
            account: "account"
            username: "username"
            password: "somepassword"
        
        #Export directories and export logger            
        export:
            directory: "%kernel.cache_dir%/boxalino/" #Default value, Where to store the export zip and csv files
            properties_xml: "%kernel.root_dir%/config/properties.xml" #Which properties.xml file to use
            log_manager: "ibrows_boxalino.entity.export_log_manager" #Default value, implement you own to log to another system
        
        #Entity setup
        entities:
            product:
                class: "AppBundle\\Entity\\Product" 
                entity_mapper: "ibrows_boxalino.mapper_orm.entity_mapper" #Default value, implement you own to log to create entity maps
                entity_provider: "ibrows_boxalino.provider_orm.entity_provider" #Default value, implement you own to retrieve entities
                delta_provider: "ibrows_boxalino.provider_orm.delta_provider" #Default value, implement you own to provide updated entities
                #configuration for retrieving delta data
                delta:
                    strategy: fromFullData #Default value, possible values are 'fromFullData', 'timestambleFieldQuery','repositoryMethod'
                    strategy_options: #If strategy is timestambleFieldQuery, or repositoryMethod, one of the following must be supplied
                        timestampable_query_field: updated_at #Field to use if timestableFieldQuery is strategy
                        repository_method: getDeltaEntities #The name of the method on the Entity Repository class to retrieve delta entities, must take a \DateTime object as a parameter
                
                #List of fields to export, generally the accessor on the entity. Field name in CSV, and then accessor
                fields: 
                    id: id
                    name: name
                    description: description
                    brand: brand
                    my_array_field: "[my_array_field]" can also be in array syntax if data is array not an objetct
            brand:
                class: "AppBundle\\Entity\\Brand"
                fields:
                    id: id
                    name: name
            productCategory:
                class: "AppBundle\\Entity\\ProductCategory"
                fields:
                    id: id
                    parent: parent
                    name: name

## More information in the [index][1]

[1]: https://ibrows.codebasehq.com/projects/ibrowsch/repositories/ibrowsboxalinobundle/blob/master/Resources/doc/index.md
    