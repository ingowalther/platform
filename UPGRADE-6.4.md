UPGRADE FROM 6.3.x.x to 6.4
=======================

# 6.4.3.0
## Change tax-free get and set in CountryEntity
Deprecated `taxFree` and `companyTaxFree` in `Shopware/Core/System/Country/CountryEntity`, use `customerTax` and `companyTax` instead.

## If you are writing the fields directly, the tax-free of the country will be used:
### Before
```php
$countryRepository->create([
        [
            'id' => Uuid::randomHex(),
            'taxFree' => true,
            'companyTaxFree' => true,
            ...
        ]
    ],
    $context
);
```
### After 
```php
$countryRepository->create([
        [
            'id' => Uuid::randomHex(),
            'customerTax' => [
                'enabled' => true, // enabled is taxFree value in old version
                'currencyId' => $currencyId,
                'amount' => 0,
            ],
            'companyTax' => [
                'enabled' => true, // enabled is companyTaxFree value in old version
                'currencyId' => $currencyId,
                'amount' => 0,
            ],
            ...
        ]
    ],
    $context
);
```
## How to use the new getter and setter of tax-free in country:
### Before
* To get tax-free
```php
$country->getTaxFree();
$country->getCompanyTaxFree();
```
* To set tax-free
```php
$country->setTaxFree($isTaxFree);
$country->setCompanyTaxFree($isTaxFree);
```
### After
* To get tax-free
```php
$country->getCustomerTax()->getEnabled(); // enabled is taxFree value in old version
$country->getCompanyTax()->getEnabled(); // enabled is companyTaxFree value in old version
```
* To set tax-free
```php
// TaxFreeConfig::__construct(bool $enabled, string $currencyId, float $amount);
$country->setCusotmerTax(new TaxFreeConfig($isTaxFree, $currencyId, $amount));
$country->setCompanyTax(new TaxFreeConfig($isTaxFree, $currencyId, $amount));
```
## Update EntityIndexer implementation
Two new methods have been added to the abstract `Shopware\Core\Framework\DataAbstractionLayer\Indexing\EntityIndexer`.
* `getTotal` - Shall return the number of records to be processed by the indexer on a Full index.
* `getDecorated` - Shall return the decorated service (see decoration pattern adr).

These two methods are declared as `abstract` with the 6.5.0.0. Here is an example of how a possible implementation might look like:
```

    public function getTotal(): int
    {
        return $this
            ->iteratorFactory
            ->createIterator($this->repository->getDefinition(), $offset)
            ->fetchCount();
        
        // alternate    
        return $this->connection->fetchOne('SELECT COUNT(*) FROM product');
    }

    public function getDecorated(): EntityIndexer
    {
        // if you implement an own indexer
        throw new DecorationPatternException(self::class);
        
        // if you decorate a core indexer
        return $this->decorated;
    }

```
## Storefront Controller need to have Twig injected in future versions

The `twig` service will be private with upcoming Symfony 6.0. To resolve this deprecation, a new method `setTwig` was added to the `StorefrontController`.
All controllers which extends from `StorefrontController` need to call this method in the dependency injection definition file (services.xml) to set the Twig instance.
The controllers will work like before until the Symfony 6.0 update will be done, but they will create a deprecation message on each usage.
Below is an example how to add a method call for the service using the XML definition.

### Before

```xml
<service id="Shopware\Storefront\Controller\AccountPaymentController">
    <call method="setContainer">
        <argument type="service" id="service_container"/>
    </call>
</service>
```

### After

```xml
<service id="Shopware\Storefront\Controller\AccountPaymentController">
    <call method="setContainer">
        <argument type="service" id="service_container"/>
    </call>
    <call method="setTwig">
        <argument type="service" id="twig"/>
    </call>
</service>
```
## ListField strict mode
A `ListField` will now always return a non associative array if the strict mode is true. This will be the default in 6.5.0. Please ensure that the data is saved as non associative array or switch to `JsonField` instead.

Valid `listField` before: 
```
Array
(
    [0] => baz
    [foo] => bar
    [1] => Array
        (
            [foo2] => Array
                (
                    [foo3] => bar2
                )
        )
)
```

Valid `ListField` after:
```
Array
(
    [0] => baz
    [1] => bar
    [2] => Array
        (
            [foo2] => Array
                (
                    [foo3] => bar2
                )
        )
)
```

# 6.4.2.0

## New Captcha Solution
* We deprecated the system config `core.basicInformation.activeCaptchas` with only honeypot captcha and upgraded to system config `core.basicInformation.activeCaptchasV2` with honeypot, basic captcha, Google reCaptcha v2, Google reCaptcha v3
### Setting captcha in administration basic information
* Honeypot captcha is activated by default
* Select to active more basic captcha, Google reCaptcha
* With Google reCaptcha v2 checkbox:
  Configure the correct site key and secret key for reCaptcha v2 checkbox
  Turn off option `Invisible Google reCAPTCHA v2`
* With Google reCaptcha v2 invisible:
  Configure the correct site key and secret key for reCaptcha v2 invisible
  Turn on option `Invisible Google reCAPTCHA v2`
* With Google reCaptcha v3:
  Configure the correct site key and secret key for reCaptcha v3
  Configure `Google reCAPTCHA v3 threshold score`, default by 0.5
### How to adapt the captcha solution upgrade?
* Add `Shopware\Storefront\Framework\Captcha\Annotation\Captcha` annotation to StorefrontController-Routes to apply captcha protection.
* Due to captcha forms will be displayed when activated, be aware that the captcha input might break your layout
#### Before
```php
{% sw_include '@Storefront/storefront/component/captcha/base.html.twig' with { captchas: config('core.basicInformation.activeCaptchas') } %}
```
#### After
```php
{% sw_include '@Storefront/storefront/component/captcha/base.html.twig'
    with {
        additionalClass : string,
        formId: string,
        preCheck: boolean
    }
%}
```

We have a default captchas config, so now you don't need to provide a captchas parameter to the component, if you provide the captchas parameter, they will be overridden

Options:
- `additionalClass`: (optional) default is `col-md-6`,
- `formId`: (optional) - you can add the custom `formId`,
- `preCheck`: (optional) default is `false` - if true it will call an ajax-route to pre-validate the captcha, before the form is submitted. When using a native form, instead of an ajax-form, the `precheck` should be `true`.


# 6.4.1.0

## Default messenger routing

We've removed the default routing rules, because it made it impossible to send a messages to transports without also 
sending it to the default transport.

This is now handled by `DefaultSenderLocator` which sends it to the `messenger.default_transport` only if no
routing rule has matched and no sender was found.

### Usage of parameter exclusion list

There are certain use cases where a GET parameter has a new value for every request, generating a new entry in the HTTP cache every time. An example would be the Google Adwords ClickId parameter `gclid` which contains a new id for every click that was generated by Google Adwords. This leads to a bad performance for the visitor since existing caches aren't being used. This allows the caching system to be more efficient.

Storefront configuration provides a list of known parameters that fall in this category. You can overwrite or extend this list in your [bundle configuration](https://developer.shopware.com/docs/v/v6.4.0/guides/hosting/infrastructure/filesystem#configuration).   
```
storefront:
    http_cache:
        ignored_url_parameters:
            - 'pk_campaign' # Piwik
            - 'piwik_campaign'
            - 'pk_kwd'
            - 'piwik_kwd'
            - 'pk_keyword'
            - 'mtm_campaign' # Matomo
            - 'matomo_campaign'
            - 'mtm_cid'
            - 'matomo_cid'
            - 'mtm_kwd'
            - 'matomo_kwd'
            - 'mtm_keyword'
            - 'matomo_keyword'
            - 'mtm_source'
            - 'matomo_source'
            - 'mtm_medium'
            - 'matomo_medium'
            - 'mtm_content'
            - 'matomo_content'
            - 'mtm_group'
            - 'matomo_group'
            - 'mtm_placement'
            - 'matomo_placement'
            - 'pixelId' # Yahoo
            - 'kwid'
            - 'kw'
            - 'chl'
            - 'dv'
            - 'nk'
            - 'pa'
            - 'camid'
            - 'adgid'
            - 'utm_term' # Google
            - 'utm_source'
            - 'utm_medium'
            - 'utm_campaign'
            - 'utm_content'
            - 'cx'
            - 'ie'
            - 'cof'
            - 'siteurl'
            - '_ga'
            - 'adgroupid'
            - 'campaignid'
            - 'adid'
            - 'gclsrc' # Google DoubleClick
            - 'gclid'
            - 'fbclid' # Facebook
            - 'fb_action_ids'
            - 'fb_action_types'
            - 'fb_source'
            - 'mc_cid' # Mailchimp
            - 'mc_eid'
            - '_bta_tid' # Bronto
            - '_bta_c'
            - 'trk_contact' # Listrak
            - 'trk_msg'
            - 'trk_module'
            - 'trk_sid'
            - 'gdfms'  # GodataFeed
            - 'gdftrk'
            - 'gdffi'
            - '_ke'  # Klaviyo
            - 'redirect_log_mongo_id' # Klaviyo
            - 'redirect_mongo_id'
            - 'sb_referer_host'
            - 'mkwid' # Marin
            - 'pcrid'
            - 'ef_id' # Adobe Advertising Cloud
            - 's_kwcid' # Adobe Analytics
            - 'msclkid' # Microsoft Advertising
            - 'dm_i' # dotdigital
            - 'epik' # Pinterest
            - 'pp'
```

# 6.4.0.0

## Breaking changes
For a complete list of breaking changes please refer to the [bc changelog](/changelog/release-6-4-0-0/2021-03-18-6.4-breaking-changes.md) changelog file.

---

## Minimum PHP version increased to 7.4
The minimum required PHP version for Shopware 6.4.0.0 is now PHP 7.4.
Please make sure, that your system has at least this PHP version activated.

We've also added support for PHP 8.0. While Shopware is de-facto ready for PHP 8.0,
some dependencies do not support PHP 8.0 in their `composer.json` in theory.
Until these dependencies add official PHP 8.0 support in their `composer.json`, 
we decided to set the `config.platform.php` of the development root composer.json to `7.4.0`.
This is to prevent composer failing to update dependencies, because of PHP version constraints.

---

## Sodium is now a requirement
The PHP extension `sodium` is now a requirement for Shopware 6.4.0.0.

---

## Composer 2
With Shopware 6.4 we are now requiring the `composer-runtime-api` with version 2.0.
These means that Shopware is now only installable with Composer 2.
Installation with Composer 1 is no longer possible and supported.

---

## Symfony 5
Symfony was upgraded to 5.2.x. It is now locked to the minor 5.2 version.

---

## API versioning change
Corresponding to the semantic versioning strategy of Shopware, we changed the API versioning to match the major versions of Shopware. 
As the API stays backward compatible for the life cycle of the whole major version, there is no need for a separate versioning. 
Therefore we also removed the unnecessary version from the URL pattern.

```
- /[store-]api/v{version}/...
+ /[store-]api/...
```

### Upgrade flow for external API services
With Shopware **6.3.5.0** we already made the new URL pattern available as an additional alias. 
This enables you to test your application with the new URL pattern within the 6.3 major cycle, before updating to the 6.4 version.

### Detecting the current used Shopware / API
Of course, it is still important for an external service to know, which version of Shopware is used. 
Therefore we added a new information endpoint, which provides this information. 
The new endpoint is also available with Shopware **6.3.5.0**, so you can switch to this pattern in the 6.3 major cycle.

```http request
GET /api/_info/version
```

### API expectations
To have the version within the URL pattern offered the advantage of telling Shopware which version requirement you expect with the request. 
To still fulfill this need, we extended the possibilities even further. 
You can send additional expectations via headers within your request, which is not only limited to the version.

```http request
GET /api/test
sw-expect-packages: shopware/core:~6.4,swag/paypal:*
```

This example expects that the Shopware version is at least 6.4, and the PayPal extension is installed in any version. 
If the conditions are not met, the backend will respond with a 417 HTTP error.

### Since flag on entities / fields
During the life cycle of a major version, there still might be non-breaking changes to the API. 
To make this information available, every new field will have a `since` flag, which indicates, when the new field was added to the API. 
All new fields will be included in the response. 
You can still remove unwanted fields from the response by using the `includes` property to [reduce the output](https://shopware.gitbook.io/docs/guides/integrations-api/general-concepts/search-criteria#includes-apialias).

The information is shown in the Swagger documentation, in the description of the route in the schema if the request / response.

```http request
/api/_info/swagger.html
```

Also deprecated fields, which will be removed with the next major version, are marked in the schema with a `deprecated` flag. 
This enables you to react to upcoming changes as early as possible.

---

## Currency filter
The default of the currency filter in the administration changed.
It will now display by default 2 fraction digits and up to 20, if available.

### Before
* value is 15.123456 -> output is 15.12
* value is 15.12345678913245 -> output is 15.12

### After
* value is 15.123456 -> output is 15.123456
* value is 15.12345678913245 -> output is 15.12345678913245
  In case you've been creating empty feature sets using the current faulty behaviour, please make sure to at least include
  a name for any new feature set from now on.
  System and plugin configurations made with `config.xml` can now have less than 4
  characters as configuration key but are not allowed anymore to start with a
  number.

---

## References to assets inside themes

We've deprecated `$asset-path` because it was generated at compilation time and contained the `APP_URL` by default,
which should only be relevant to administration. Instead, `$app-css-relative-asset-path` is to be used, which is an
url that is relative to the `app.css` that points to the asset folder.

As a side effect, the fonts are now loaded from the theme folder instead of the bundle asset folder. This should work out of the box,
because all assets of the theme are also copied into the theme folder.

---

## Removed plugin manager
The plugin manager in the administration is removed with all of its components and replaced by the `sw-extension` module.
The controller for the plugin manager with all of its routes is removed and replaced by the `ExtensionStoreActionsController`.

---

## New ApiAware flag
The new `ApiAware` flag replaces the current `ReadProtected` flag for entity definitions.
See [NEXT-13371 - Added api aware flag](/changelog/release-6-3-5-1/2021-01-25-added-api-aware-flag.md)

---

## OAuth2 upgrade
The `league/oauth2-server` and `lcobucci/jwt` dependencies were upgraded to their next respective major versions.
This comes with a break in our current oauth2 core implementation.

See [the commit on GitHub](https://github.com/shopware/platform/commit/656c82d5232c87b75e1d6b42bd6493d674807791) for details.

---

## Changed the loading of storefront SCSS files in extensions
Previously all Storefront relevant SCSS files (`*.scss`) of an extension have automatically been loaded and compiled by shopware when placed inside the directory `src/Resources/app/storefront/src/scss`.
Because all SCSS files have been loaded automatically it could have let to inconsistent results when dealing with custom SCSS variables in separate files for example.

This behaviour has been changed and now only a single entry file will be used by extensions which is the `YourPlugin/src/Resources/app/storefront/src/scss/base.scss` or `YourApp/Resources/app/storefront/src/scss/base.scss`.

### Before

All the SCSS files in this example directory have been loaded automatically:

```
└── scss
    ├── custom-component.scss
    ├── footer.scss
    ├── header.scss
    ├── product-detail.scss
    └── variables.scss
```

### After

Now you need a `base.scss` and need to load all other files from there using the `@import` rule:

```
└── scss
    ├── base.scss <-- This is now mandatory and loads all other files
    ├── custom-component.scss
    ├── footer.scss
    ├── header.scss
    ├── product-detail.scss
    └── variables.scss
```

The `base.scss` for the previous example directory would look like this in order to load all SCSS properly:

```scss
// Content of the base.scss
@import 'variables';
@import 'header';
@import 'product-detail';
@import 'custom-component';
```

---

## Swift_Mailer exchanged with Symfony/Mailer
The current default mailer `Swift_Mailer` was exchanged with `Symfony\Mailer`. 
If you have configured the Mailer using the environment variable `MAILER_URL`, you have to change this to the new syntax.
Refer to the [Symfony Documentation](https://symfony.com/doc/current/mailer.html#using-built-in-transports) for the updated syntax.

---

## context.salesChannel.countries removed
Previously, the sales channel object in the context contained all countries assigned to the sales channel. This data has now been removed. 
The access via `$context->getSalesChannel()->getCountries()` therefore no longer returns the previous result.
To load the countries of a sales channel, the class `\Shopware\Core\System\Country\SalesChannel\CountryRoute` should be used.

---

## Separated plugin download and update
If you're using the `api.custom.store.download` route, be aware that its behaviour was changed.
The route will no longer trigger a plugin update.
In case you'd like to trigger a plugin update, you'll need to dispatch another request to the
`api.action.plugin.update` route.

---

## Removed deprecated database columns
* Removed the column `currency`.`decimal_precision`. The rounding is now controlled by the `CashRoundingConfig` in the `SalesChannelContext`
* Removed the column `product`.`purchase_price`. Replaced by `product`.`purchase_prices`
* Removed the column `customer_wishlist_product`. This column was never used, and the feature still requires a feature flag.

---

## Migration system upgrade guide

We've grouped the migrations into major versions. By default, all non-destructive migrations are executed up to the current major. 
In contrast, all destructive migrations are executed up to a "safe" point. This can be configured with the mode.

There are three possible values for the mode:
1. `--version-selection-mode=safe`: Execute only "safe" destructive changes. This means only migrations from the penultimate major are executed. 
   So with the update to 6.5.0.0 all destructive changes in 6.3 or lower are executed.
2. `--version-selection-mode=blue-green`: Execute as early as possible, but still blue-green compatible. 
   This means with the update to 6.4.1.0 from 6.4.0.0 all destructive changes in 6.3 or lower are executed.
3. `--version-selection-mode=all`: Execute all destructive changes up to the current major.

To allow this selection, we've moved all migrations from `\Shopware\Core\Framework\Migration\MigrationSource.core` 
into `\Shopware\Core\Framework\Migration\MigrationSource.core.V6_3`. `core` is now empty by default. You can still extend it. 
The execution order is now like this:
1. `core.V6_3`
2. `core.V6_4`
3. newer majors...
4. `core`

This means all new migrations need to be created in the matching major folder. Currently, this is `src/Core/Migration/V6_3`, 
it will be `src/Core/Migration/V6_4` soon. To keep the backwards compatibility, Migrations still need to be defined in `src/Core/Migration`. 
To accomplish that, just create it in the versioned folder and create a class in the old folder that simply extends the other class without changing anything.  

The method `\Shopware\Core\Framework\Migration\MigrationCollectionLoader::collectAllForVersion` will return a collection with all "safe" `MigrationSource`s including `core`.

**bin/console database:migrate --all core**

Should do the sames as before. Alternatively, you can run it for each major:
- `bin/console database:migrate --all V6_3`
- `bin/console database:migrate --all V6_4`
- etc

**bin/console database:migrate-destructive --all core**

This behavior changed! By default, this will only executes "safe" destructive changes. It used to execute all destructive migrations.

To get the old behavior, run:

`bin/console database:migrate-destructive --all --version-selection-mode=all core`

To run the destructive migrations as early as possible but still blue-green, run:

`bin/console database:migrate-destructive --all --version-selection-mode=blue-green core`

**Changes to auto updater**

We've changed the updater to only execute safe destructive migrations. It used to execute **ALL** destructive changes.

### Creating core migrations

To allow implementing this feature with a feature flag, we've to create a legacy migration in `src/Core/Migraiton`, 
which simply extends from the real migration in `src/Core/Migration/$MAJOR`. All migrations have been changed in that way.
The `bin/console database:create-migration` command automatically creates a legacy migration.
Due to the way that `img`'s `object-fit` works, it is not possible to mimic the 'Auto' setting of the block background. This means that elements that currently have 'Auto' set as their background mode will look different.

---

## CMS entities version aware

This change update the primary key of `cms_page`, `cms_slot`, `cms_block` and `cms_section` and the corresponding translation tables. If your plugin incorporates foreign keys to these tables you will need to update your migrations and dal entity definitions.

Please use `bin/console dal:validate` to see if you have to adjust your plugins anywhere.

### Update

If your plugin is already installed the shopware core migration will take care of adjusting the foreign key.
A new column `{TABLE_NAME}_version_id` is created, and the constraint widened. 
You will just have to add a version reference field in your definitions.

For a `cms_page` relation this would make these lines mandatory in your field definition like this:

```php
use Shopware\Core\Content\Cms\CmsPageDefinition;
use Shopware\Core\Framework\DataAbstractionLayer\Field\ReferenceVersionField;

new ReferenceVersionField(CmsPageDefinition::class);
```

### Install

If your plugin is newly installed you should add a combined foreign key to your `CREATE TABLE` statement.

```sql
CREATE TABLE _TABLE_ IF NOT EXISTS
    `cms_page_id` binary(16) DEFAULT NULL, # the existing column
    `cms_page_version_id` binary(16) NOT NULL, # from now on mandatory
    # [...]
    KEY `_NAME_` (`cms_page_id`,`cms_page_version_id`),
    CONSTRAINT `_NAME_` FOREIGN KEY (`cms_page_id`, `cms_page_version_id`) REFERENCES `cms_page` (`id`, `version_id`) ON DELETE CASCADE ON UPDATE CASCADE # notice the two column on two column key
);
```

---

### Deployment notice
Due to the migration changing the product table as well, the update process might be slower than usual.

---

## System-Config
Removed default for `detail.showReviews`, use `core.listing.showReview` instead.

---

## Guzzle major version upgrade
We upgraded the guzzle dependency to a new major version v7. Please refer to the [guzzle upgrade guide](https://github.com/guzzle/guzzle/blob/master/UPGRADING.md#60-to-70) to make sure your plugins are compatible.

---

## Elasticsearch Refactoring
To improve the performance and reliability of Elasticsearch, we have decided to refactor Elasticsearch in the first iteration in the Storefront only for Product listing and Product searches.
This allows us to create a optimized Elasticsearch index with only required fields selected by an single sql to make the indexing fast as possible.
This also means for extensions, they need to extend the indexing to make their fields searchable.

Here is an simple decoration to add a new random field named `myNewField` to the index.
For adding more information from the Database you should execute a single query with all document ids (`array_column($documents, 'id'')`) and map the values

```xml
<service id="MyDecorator" decorates="Shopware\Elasticsearch\Product\ElasticsearchProductDefinition">
    <argument type="service" id="MyDecorator.inner"/>
    <argument type="service" id="\Doctrine\DBAL\Connection"/>
</service>
```

```php
<?php

use Shopware\Core\Defaults;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\EntityDefinition;
use Shopware\Core\Framework\Uuid\Uuid;
use Shopware\Elasticsearch\Framework\AbstractElasticsearchDefinition;
use Shopware\Elasticsearch\Framework\Indexing\EntityMapper;
use Doctrine\DBAL\Connection;

class MyDecorator extends AbstractElasticsearchDefinition
{
    private AbstractElasticsearchDefinition $productDefinition;
    private Connection $connection;

    public function __construct(AbstractElasticsearchDefinition $productDefinition, Connection $connection)
    {
        $this->productDefinition = $productDefinition;
        $this->connection = $connection;
    }

    public function getEntityDefinition(): EntityDefinition
    {
        return $this->productDefinition->getEntityDefinition();
    }

    public function getMapping(Context $context): array
    {
        $mapping = $this->productDefinition->getMapping($context);

        $mapping['properties']['myNewField'] = EntityMapper::INT_FIELD;

        // Adding nested field with id
        $mapping['properties']['myManyToManyAssociation'] = [
            'type' => 'nested',
            'properties' => [
                'id' => EntityMapper::KEYWORD_FIELD,
            ],
        ];

        return $mapping;
    }

    public function extendDocuments(array $documents, Context $context): array
    {
        $documents = $this->productDefinition->extendDocuments($documents, $context);
        $productIds = array_column($documents, 'id');

        $query = <<<'SQL'
SELECT LOWER(HEX(mytable.product_id)) as id, GROUP_CONCAT(LOWER(HEX(mytable.myFkField)) SEPARATOR "|") as relationIds
FROM mytable
WHERE
    mytable.product_id IN(:ids) AND
    mytable.product_version_id = :liveVersion
SQL;


        $associationData = $this->connection->fetchAllKeyValue(
            $query,
            [
                'ids' => Uuid::fromHexToBytesList($productIds),
                'liveVersion' => Defaults::LIVE_VERSION
            ],
            [
                'ids' => Connection::PARAM_STR_ARRAY
            ]
        );

        foreach ($documents as &$document) {
            // Normal field directly on the product
            $document['myNewField'] = random_int(PHP_INT_MIN, PHP_INT_MAX);

            // Nested object with an id field
            $document['myManyToManyAssociation'] = array_map(function (string $id) {
                return ['id' => $id];
            }, array_filter(explode('|', $associationData[$document['id']] ?? '')));
        }

        return $documents;
    }
}
```

When searching products make sure you add elasticsearch aware to your criteria to use Elasticsearch in background.

```php
$criteria = new \Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria();
$context = \Shopware\Core\Framework\Context::createDefaultContext();
// Enables elasticsearch for this search
$context->addState(\Shopware\Core\Framework\Context::STATE_ELASTICSEARCH_AWARE);

$repository->search($criteria, $context);
```

---

## LineItems rules behaviour changed
The rules for line items are now considering also nested line items.
Before the change, only the first level of line items was taken into account.
Check your rules, if they still take effect as intended.

---

## TreeUpdater scaling

We've replaced `\Shopware\Core\Framework\DataAbstractionLayer\Indexing\TreeUpdater::update` with `\Shopware\Core\Framework\DataAbstractionLayer\Indexing\TreeUpdater::batchUpdate`,
because `update` scaled badly with the tree depth. The new method takes an array instead of a single id.

---

## EntityWriteGatewayInterface

We've added the new method `prefetchExistences` to the interface `\Shopware\Core\Framework\DataAbstractionLayer\Write\EntityWriteGatewayInterface`.
The method is optional, and a valid implementation is to not prefetch anything. The method was added to allow fetching the existence of more than one primary key at once.

---

## FieldSerializerInterface::normalize

We've added the new method `normalize` to the interface `\Shopware\Core\Framework\DataAbstractionLayer\FieldSerializer\FieldSerializerInterface`.
A valid implementation is to just return `$data`. The `AbstractFieldSerializer` does that already.
The method should normalize the `$data` if it makes sense. For example, the core serializers do the following in the normalize step:
- generate missing ids (`IdField`)
- resolve foreign keys (`FkField` and `Association`)
- normalize structure for example for translations (there are multiple ways to define them)
- collect primary keys in `PrimaryKeyBag`

---

## Events

All events that are dispatched in a sales channel context now implement `ShopwareSalesChannelEvent`. The return type `getContext` may have changed from `SalesChannelContext`
to `Context`. To get the sales channel context, use `getSalesChannelContext`.

---

## Cheapest price implementation
We've added a new implementation to calculate product prices. `product.cheapestPrice` replaces `product.listingPrices`.
Update your queries accordingly.

---

## BlacklistRuleField / WhitelistRuleField dropped
The `BlacklistRuleField` and `WhitelistRuleField` implementations were dropped.
Create an own many-to-many association to achieve this functionality

---

## DAL cache removed
The DAL cache was removed. 
Therefore, calling `Shopware\Core\Framework\Context::disableCache` has no more effect.

---

## ExecuteQuery throws execption on write operations
The following SQL operations will throw an exception, if called with `Doctrine\DBAL\Connection\::executeQuery`:
`UPDATE`, `ALTER`, `BACKUP`, `CREATE`, `DELETE`, `DROP`, `EXEC`, `INSERT`, `TRUNCATE`

---

## AntiJoinFilter removed
We've enhanced the DAL to automatically detect a pattern following the `AntiJoinFilter`. 
Therefore, it was removed.

---

## ProductPriceDefinitionBuilder replaced
We've replaced the `ProductPriceDefinitionBuilder` by the `ProductPriceCalculator`

---

## Routing changes
Removed the parameter `swagShopId` from `StorefrontRenderEvent`, Use `appShopId` instead.

---

## Payment / Shipping method selection modal removed
The modal to select payment or shipping methods was removed entirely.
Instead, the payment and shipping methods will be shown instantly up to a default maximum of `5` methods.
All other methods will be hidden inside a JavaScript controlled collapse.

The changes especially apply to the `confirm checkout` and `edit order` pages.

We refactored most of the payment and shipping method storefront templates and split the content up into multiple templates to raise the usability.

---

## Datepicker component
According to document of flatpickr (https://flatpickr.js.org/formatting/), ISO Date format is now supported for the datepicker component.
With `datetime-local` dateType, the datepicker will display the user's browser time.
The value will be converted to UTC value.

### Before
* Both dateType `datetime` and `datetime-local` use UTC timezone `(GMT+00:00)`.
* If user selects date `2021-03-22` and time `12:30`, the output is `2021-03-22T12:30:000+00:00`.

### After
* With dateType `datetime`, user selects date `2021-03-22` and time `12:30`, the output is `2021-03-22T12:30:000+00:00`.
* With dateType `datetime-local`, user selects date `2021-03-22` and time `12:30` and timezone of user is `GMT+07:00`, the output is `2021-03-22T05:30.00.000Z`.

---

## Removed deprecated SCSS color variables
We removed the following deprecated color / gradient variables:

```scss
$color-biscay
$color-cadet-blue
$color-crimson
$color-contrast
$color-deep-cove
$color-emerald
$color-gray
$color-gutenberg
$color-kashmir
$color-iron
$color-light-gray
$color-link-water
$color-pumpkin-spice
$color-purple
$color-shopware-blue
$color-steam-cloud

$color-gradient-dark-gray-start
$color-gradient-dark-gray-end
$gradient-dark-gray
```
---

## NPM package copy-webpack-plugin update
This package has now version `6.4.1`, take a look at the [github changelog](https://github.com/webpack-contrib/copy-webpack-plugin/releases/tag/v6.0.0) for breaking changes.

---

## NPM package node-sass replacement
Removed `node-sass` package because it is deprecated. Added the `sass` package as replacement. For more information take a look [deprecation page](https://sass-lang.com/blog/libsass-is-deprecated).

---

## Twig system config /theme access
The `shopware.config` variable was removed. To access a system config value inside twig, use `config('my_config_key')`.
The `shopware.theme` variable was removed. To access the theme config value inside twig, use `theme_config('my_config_key')`.

---

## Changed product streams from blacklist to allowed list
The properties in the product stream are now defined with a allowed list. This can cause some missing fields which were available in earlier releases. If you need them also in feature releases then you can add them to the allowed list with an plugin.

Just use the method `addToEntityAllowList` or `addToGeneralAllowList` from the productStreamConditionService. 

Example:
```js
Shopware.Service('productStreamConditionService').addToEntityAllowList('product', 'yourProperty');
```
