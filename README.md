# Composer Installer for Bluz

[![Gitter](https://badges.gitter.im/Join%20Chat.svg)](https://gitter.im/bluzphp/main)

### Achievements

[![Build Status](https://travis-ci.org/bluzphp/composer-plugin.svg?branch=master)](https://travis-ci.org/bluzphp/composer-plugin)
[![Scrutinizer Code Quality](https://scrutinizer-ci.com/g/bluzphp/composer-plugin/badges/quality-score.png?b=master)](https://scrutinizer-ci.com/g/bluzphp/composer-plugin/?branch=master)

[![Latest Stable Version](https://poser.pugx.org/bluzphp/composer-plugin/v/stable)](https://packagist.org/packages/bluzphp/composer-plugin)
[![Total Downloads](https://poser.pugx.org/bluzphp/composer-plugin/downloads)](https://packagist.org/packages/bluzphp/composer-plugin)

[![License](https://poser.pugx.org/bluzphp/composer-plugin/license)](https://packagist.org/packages/bluzphp/composer-plugin)

Features
-------------------------
* The modules installer for bluz skeleton

Requirements
-------------------------
* PHP: 7 (or later)

Available modules
-------------------------
* [bluzphp/module-options](https://github.com/bluzphp/module-options)
* [bluzphp/module-categories](https://github.com/bluzphp/module-categories)
* [bluzphp/module-media](https://github.com/bluzphp/module-media)
* [bluzphp/module-auth-google](https://github.com/bluzphp/module-auth-google)
* [bluzphp/module-auth-twitter](https://github.com/bluzphp/module-auth-twitter)
* [bluzphp/module-auth-facebook](https://github.com/bluzphp/module-auth-facebook)
* [bluzphp/module-test](https://github.com/bluzphp/module-test)
* [bluzphp/module-contact-us](https://github.com/bluzphp/module-contact-us)

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
