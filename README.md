### Purpose
The default M2 sample data does not contain a multi-site solution, this introduces some data to work with
when developing tests. Ideally we would also create other sample data modules which would introduce categories,
products, etc. to the websites here. It would be bare minimum to increase speed of installation and running. 

### Structure
* web_computer - default, USD
    * grp_desktop
        * store_desktop_en - English, lbs
        * store_desktop_ru - Russian, kgs
        * store_desktop_de - German, kgs, default
        * store_desktop_dis - English, lbs, disabled
    * grp_laptop - default
        * store_laptop_en - English, lbs, default
        * store_laptop_ru - Russian, kgs
        * store_laptop_de - German, kgs
        * store_laptop_dis - English, lbs, disabled
* web_phone - RUB
    * grp_tablet - default
        * store_tablet_en - English, lbs
        * store_tablet_ru - Russian, kgs default
        * store_tablet_de - German, kgs
        * store_tablet_dis - English, lbs, disabled
    * grp_accessory - disabled
        * store_accessory_en - English, lbs, default, disabled
        * store_accessory_ru - Russian, kgs, disabled
        * store_accessory_de - German, kgs, disabled

### Installation
Can be installed manually by including the package via composer:

```$xslt
composer require shopgate/magento2-website-sample-data
```

If you have any other magento 2 plugin that suggests this sample data, e.g. [shopgate/connect-integration-magento2] 
the sample data will be installed via regular magento command `bin/magento sampledata:deploy`

### Tests
Run tests the same way you do normally. You may also adjust your integration phpunit.xml to include the tests in 
the run.
```$xslt
 /var/www/html/vendor/phpunit/phpunit/phpunit --configuration /var/www/html/dev/tests/integration/phpunit.xml /var/www/html/vendor/shopgate/magento2-website-sample-data/Test/Integration
```

### Uninstall
The best way to remove the module fully is manually:
```$xslt
bin/magento module:uninstall Shopgate_WebsiteSampleData --remove-data
```

You could also use the standard removal, but unlike the previous solution there is no hook that will remove the
 websites, groups, stores and configs from the database. Just the module.
```$xslt
bin/magento sampledata:remove
bin/magento setup:upgrade
```

### Todo's
We'll need to populate these entities with proper data to do tests on.

* Create categories
* Create products

[shopgate/connect-integration-magento2]: https://github.com/shopgate/connect-integration-magento2
