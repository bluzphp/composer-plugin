# Composer Installer for Bluz

### Achievements

[![PHP >= 7.1+](https://img.shields.io/packagist/php-v/bluzphp/composer-plugin.svg?style=flat)](https://php.net/)

[![Latest Stable Version](https://img.shields.io/packagist/v/bluzphp/composer-plugin.svg?label=version&style=flat)](https://packagist.org/packages/bluzphp/composer-plugin)

[![Build Status](https://img.shields.io/travis/bluzphp/composer-plugin/master.svg?style=flat)](https://travis-ci.org/bluzphp/composer-plugin)

[![Scrutinizer Code Quality](https://img.shields.io/scrutinizer/g/bluzphp/composer-plugin.svg?style=flat)](https://scrutinizer-ci.com/g/bluzphp/composer-plugin/)

[![Total Downloads](https://img.shields.io/packagist/dt/bluzphp/composer-plugin.svg?style=flat)](https://packagist.org/packages/bluzphp/composer-plugin)

[![License](https://img.shields.io/packagist/l/bluzphp/composer-plugin.svg?style=flat)](https://packagist.org/packages/bluzphp/composer-plugin)

Features
-------------------------
* The modules installer for bluz skeleton

Requirements
-------------------------
* PHP: 7.1 (or later)

Available modules
-------------------------
* [bluzphp/module-auth-google](https://github.com/bluzphp/module-auth-google)
* [bluzphp/module-auth-twitter](https://github.com/bluzphp/module-auth-twitter)
* [bluzphp/module-auth-facebook](https://github.com/bluzphp/module-auth-facebook)
* [bluzphp/module-categories](https://github.com/bluzphp/module-categories)
* [bluzphp/module-contact-us](https://github.com/bluzphp/module-contact-us)
* [bluzphp/module-media](https://github.com/bluzphp/module-media)
* [bluzphp/module-options](https://github.com/bluzphp/module-options)
* [bluzphp/module-phones](https://github.com/bluzphp/module-phones) — under development
* [bluzphp/module-profile](https://github.com/bluzphp/module-profile)
* [bluzphp/module-push](https://github.com/bluzphp/module-push) — under development
* [bluzphp/module-test](https://github.com/bluzphp/module-test)
* [bluzphp/module-wallet](https://github.com/bluzphp/module-wallet) — under development

Usage
-------------------------
### Install module
To install the module run the command:
  
```bash
php ./vendor/bin/bluzman module:install options
```

You can enter the environment

```bash
php ./vendor/bin/bluzman module:install options --env production
```

### Remove module
To remove the module, run the command:
    
```bash
php ./vendor/bin/bluzman module:remove options
```

## License

The project is developed by [NIX Solutions][1] PHP team and distributed under [MIT LICENSE][2]

[1]: http://nixsolutions.com
[2]: https://raw.github.com/bluzphp/composer-plugin/master/LICENSE.md
