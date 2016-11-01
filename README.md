# Composer Installer for Bluz

Features
-------------------------
* The modules installer for bluz skeleton

Requirements
-------------------------
* PHP: 5.4 (or later)

Available modules
-------------------------
* bluz-options-module
* bluz-categories-module
* bluz-media-module
* bluz-google-module
* bluz-facebook-module
* bluz-twitter-module
* bluz-test-module

Usage
-------------------------
### Install module
For install the module, you must run the command:
  
    ```
    $ composer require bluzphp/bluz-options-module
    ```
Then you must enter the environment

    ```
    Please, enter  your environment[dev, production, testing or another] dev
    ```


If you use no-interaction mode, you must set an environment variable
  
    ```
    $ ENV=dev composer require bluzphp/bluz-options-module -n
    ```

### Remove module
For remove the module, you must run the command:
    
    ```
    $ composer remove bluzphp/bluz-options-module
    ```

After you will see a confirmation message for removing the module tables
    ```
    Do you want remove table: options[y, n] y
    ```
And enter set an environment variable
    
    ```
    Please, enter  your environment[dev, production, testing or another] dev
    ```
    
If you use no-interaction mode, you must set an environment variable
  
    ```
    $ ENV=dev composer remove bluzphp/bluz-options-module -n
    ```


    
