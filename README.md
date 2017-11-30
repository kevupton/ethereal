# **Ethereal** #

*Laravel extension package.*

Extends the core laravel framework, providing easier, faster development experience.

Check out the wiki to view examples and full documentation.

## [Wiki](https://github.com/kevupton/ethereal/wiki)

----------


## **Installation:** ##
#### Download
Use composer to download the package into your project.

     composer require kevupton/ethereal

#### Setup
Then add the `Kevupton\Ethereal\Providers\EtherealServiceProvider` to your `app.php` config file under `providers`

```php
    'providers' => [
    
        /*
         * Laravel Framework Service Providers...
         * Place at the end of the array
         */
    
        Kevupton\Ethereal\Providers\EtherealServiceProvider::class,
    
    ],
```


#### Basic Usage

Just extend the `Kevupton\Ethereal\Models\Ethereal` class instead of Laravel Model class, for each of your models you want Ethereal functionality. 

```php
    <?php namespace My\Namespace\Location;

    use Kevupton\Ethereal\Models\Ethereal;

    class Example extends Ethereal { }
```

------------

Check out the wiki for the complete documentation on how to use. 

[Wiki](https://github.com/kevupton/ethereal/wiki)

------------

*Author: Kevin Upton*


