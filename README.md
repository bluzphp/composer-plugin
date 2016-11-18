# Composer Installer for Bluz

Features
-------------------------
* The modules installer for bluz skeleton

Requirements
-------------------------
* PHP: 7 (osr later)

Available modules
-------------------------
* [bluzphp/bluz-options-module](https://github.com/bluzphp/bluz-options-module)
* [bluzphp/bluz-categories-module](https://github.com/bluzphp/bluz-categories-module)
* [bluzphp/bluz-media-module](https://github.com/bluzphp/bluz-media-module)
* [bluzphp/bluz-google-module](https://github.com/bluzphp/bluz-google-module)
* [bluzphp/bluz-twitter-module](https://github.com/bluzphp/bluz-twitter-module)
* [bluzphp/bluz-test-module](https://github.com/bluzphp/bluz-test-module)

Usage
-------------------------
### Install module
To install the module run the command:
  

    $ composer require bluzphp/bluz-options-module

Then you must enter the environment


    Please, enter  your environment[dev, production, testing or another] dev



If you use no-interaction mode, you must set an environment variable
  

    $ ENV=dev composer require bluzphp/bluz-options-module -n


### Remove module
To remove the module, run the command:
    

    $ composer remove bluzphp/bluz-options-module


After you will see a confirmation message for removing the module tables

    Do you want to remove table: options[y, n] y

And set an environment variable
    

    Please, enter  your environment[dev, production, testing or another] dev

    
If you use no-interaction mode, you must set an environment variable
  

    $ ENV=dev composer remove bluzphp/bluz-options-module -n



    
