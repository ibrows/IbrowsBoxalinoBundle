Entity Configuration
====================

In order to create the csv files correctly, it is necessary to have a configuration of the entities and the fields that
should be exported.

Here is an example

``` yaml
ibrows_boxalino:
    .....
    #Entity setup
    entities:
        product:
            class: "AppBundle\\Entity\\Product" 
            
            #Default value, implement you own to log to create entity maps
            entity_mapper: "ibrows_boxalino.mapper_orm.entity_mapper" 
            
            #Default value, implement you own to retrieve entities
            entity_provider: "ibrows_boxalino.provider_orm.entity_provider" 
            
            #Default value, implement you own to provide updated entities
            delta_provider: "ibrows_boxalino.provider_orm.delta_provider" 
            
            #configuration for retrieving delta data
            delta:
                #Default value, possible values are 'fromFullData', 'timestambleFieldQuery','repositoryMethod'
                strategy: fromFullData 
                
                #If strategy is timestambleFieldQuery, or repositoryMethod, one of the following must be supplied
                strategy_options: 
                    #Field to use if timestableFieldQuery is strategy
                    timestampable_query_field: updated_at 
                    
                    #The name of the method on the Entity Repository class to retrieve delta entities, must take a \DateTime object as a parameter
                    repository_method: getDeltaEntities 
            
            #List of fields to export, generally the accessor on the entity. Field name in CSV, and then accessor
            fields: 
                id: id
                name: name
                description: description
                brand: brand
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
```

In the "fields" configuration, defined is the name of the field as displayed in the csv (the key), and the property accessor
on the right. An instance of the PropertyAccessor class is used to extract the data from the given object or array. That 
way it is possible to use any accessor that you would like.


entity_mapper:
--------------
The entity mapper is responsible for extraction information from the class about each field, and building a map of the classes 
field types, it can detect whether a fields is an object or a relation, or needs a join table (in which case an extra csv file 
would be created). It does this by reading the metadata of an object and adding a field map objects which reflect the column name,
the property path, and if necessary, if the field is actually related to a join table, and needs an extra csv file, with the
join fields defined.


A custom entity_mapper can be made, and the service container id used in the entity_mapper parameter for the entity in 
question. 

Below is an example of the translatable entity mapper (used for entities that have translatable fields). It is important 
that the service is tagged with "ibrows_boxalino.entity_mapper" and that it implements the EntityMapperInterface

``` xml
    <service class="Ibrows\BoxalinoBundle\Mapper\ORM\TranslatableEntityMapper"  id="ibrows_boxalino.mapper.orm.translatable_entity_mapper"  public="false">
        <argument id="doctrine.orm.entity_manager" type="service"/>
        <tag name="ibrows_boxalino.entity_mapper"/>
    </service>
```



entity_provider:
----------------
The entity provider is responsible for retrieving the entities for a full export (most likely from the DB) and passing 
them to the exporter.

A custom entity_provider can be made, and the service container id used in the entity_provider parameter for the entity in 
question. 

Important is that the service is tagged with "ibrows_boxalino.entity_provider".

``` xml
        <service class="Ibrows\ShopBundle\Provider\ArticleProvider" id="ibrows_shop.provider.article_provider">
            <argument id="sonata.admin.entity_manager" type="service"/>
            <tag name="ibrows_boxalino.entity_provider"/>
        </service>
```

delta_provider:
---------------
The entity provider is responsible for retrieving the entities for a delta export (most likely from the DB) and passing 
them to the exporter

A custom delta_provider can be made, and the service container id used in the delta_provider parameter for the entity in 
question. 

Important is that the service is tagged with "ibrows_boxalino.delta_provider".


``` xml
        <service class="Ibrows\ShopBundle\Provider\ArticleProvider" id="ibrows_shop.provider.article_provider">
            <argument id="sonata.admin.entity_manager" type="service"/>
            <tag name="ibrows_boxalino.delta_provider"/>
        </service>
```        
