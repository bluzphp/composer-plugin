# Composer Installer for Bluz

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
* [bluzphp/module-google](https://github.com/bluzphp/module-google)
* [bluzphp/module-twitter](https://github.com/bluzphp/module-twitter)
* [bluzphp/module-facebook](https://github.com/bluzphp/module-facebook)
* [bluzphp/module-test](https://github.com/bluzphp/module-test)

Usage
-------------------------
### Install module
To install the module run the command:
  

    $ composer require bluzphp/module-options

Then you must enter the environment


    Please, enter  your environment[dev, production, testing or another] dev



If you use no-interaction mode, you must set an environment variable
  

    $ ENV=dev composer require bluzphp/module-options -n


### Remove module
To remove the module, run the command:
    

    $ composer remove bluzphp/module-options


After you will see a confirmation message for removing the module tables

    Do you want to remove table: options[y, n] y

And set an environment variable
    

    Please, enter  your environment[dev, production, testing or another] dev

    
If you use no-interaction mode, you must set an environment variable
  

    $ ENV=dev composer remove bluzphp/module-options -n



    
