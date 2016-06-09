Commands
========

There are two basic commands availble over the app/console, one to export and publish the properties xml file, and one 
to generate the entity csv files and push them to Boxalino.

Export properties
-----------------

Before you can use any of the Boxalino search capabilities, you need to export the properties.xml file which maps the 
csv files and the attributes to a flat data structure. For more about properties see [properties.xml][1]

The command to export it is as follows:

```
app/console ibrows:boxalino:export-properties --publish --properties-xml=/app/config/properties.xml
```


The publish flag tells Boxalino to publish any changes, the --properties-xml parameter allows you to override the location
of the properties.xml file as configured in the ibrows_boxalino config.

The command will return if something has changed, and if yes a copy of before and after xml. This is true whether you run
the job with or without the publish flag.

Export entities
---------------

There are two type of export, "full" and "delta". 

The "full" (default) export will replace your entire index - providing there are more than 12 entries, otherwise it is ignored.

The "delta" export will only update or add new entries to the index.

**You can not delete entries, you must do a full export**

```
app/console ibrows:boxalino:export-entities --sync=full --push-live
```

-- or --  


```
app/console ibrows:boxalino:export-entities --sync=full --d
```

--sync: This parameter defines which type of export to make, omitting it will result in a full sync

--dry-run (-d): Will **only** generate the csv files and nothing more, event if the push-live flag is used. good for testing

--push-live: This will ensure that the data is pushed to the live index (published), without this the data is pushed but 
stays in the dev till it is manually published ([see manual publishing][2]

For more information about entity export configuration see [entitiy configuration][3]



[1]: https://ibrows.codebasehq.com/projects/ibrowsch/repositories/ibrowsboxalinobundle/blob/master/Resources/doc/properties_xml.md
[2]: https://ibrows.codebasehq.com/projects/ibrowsch/repositories/ibrowsboxalinobundle/blob/master/Resources/doc/manual_publish.md
[3]: https://ibrows.codebasehq.com/projects/ibrowsch/repositories/ibrowsboxalinobundle/blob/master/Resources/doc/entity_configuration.md

