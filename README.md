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


    ```

Configuration
---------------------

### Setup credentials, paths and entities
    ibrows_boxalino:
        access:
            account: "account"
            username: "username"
            password: "somepassword"
            cookie_domain: "localhost.dev"
        export:
            directory: "%kernel.root_dir%/../var/boxalino/" #Where to store the export zip and csv files
            properties_xml: "%kernel.root_dir%/config/properties.xml" #Which properties.xml file to use
            log_manager: "ibrows_boxalino.entity.export_log_manager" #Default value, implement you own to log to another system
        entities:
            product:
                class: "AppBundle\\Entity\\Product"
                entity_mapper: "ibrows_boxalino.mapper_orm.entity_mapper" #Default value, implement you own to log to create entity maps
                entity_provider: "ibrows_boxalino.provider_orm.entity_provider" #Default value, implement you own to retrieve entities
                delta_provider: "ibrows_boxalino.provider_orm.delta_provider" #Default value, implement you own to provide updated entities
                fields: [id, name, description, brand] #List of fields to export, generally the accessor on the entity 
            brand:
                class: "AppBundle\\Entity\\Brand"
                fields: [id, name]
            productCategory:
                class: "AppBundle\\Entity\\ProductCategory"
                fields: [id, name]

    