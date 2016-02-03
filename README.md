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
            export_directory: "%kernel.root_dir%/../var/boxalino/"
        entities:
            product:
                class: "AppBundle\\Entity\\Product"
                fields: [id, name, description, brand]
            brand:
                class: "AppBundle\\Entity\\Brand"
                fields: [id, name, products]
            productCategory:
                class: "AppBundle\\Entity\\ProductCategory"
                fields: [id, name, products]

    