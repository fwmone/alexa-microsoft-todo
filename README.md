# Alexa to Microsoft To Do connection

This small PHP script enables the connection from Alexa to Microsoft To Do, when being integrated as an endpoint into your skill (https://developer.amazon.com/alexa/console/ask). My skill is available at https://www.amazon.de/dp/B0CTHRHFQ2/ (To Do tasklist) and https://www.amazon.de/dp/B0D93Z2BZV/ (To Do Shopping Cart list).

## Requirements

A webserver, PHP 7+, composer (optional) and MySQL / MariaDB for the list database.

## Installation

1. After cloning the repository, you should have all the required script files. 
1a. Doing a composer update is optional as I provide all the required packages. 
2. Create the database as of DATABASE.sql
3. Copy _config.inc.bak to _config.inc.php and add the required configuration variables.
4. Add https://YOURSERVER/endpoint-aufgabenliste.php as endpoint for your to do list skill and https://YOURSERVER/endpoint-einkaufsliste.php for your shopping cart skill. You're free to use only one of them.

## Usage

The skill supports the AddToListIntent and the RemoveFromListIntent. They must be added as intents to your skill and need to have {item} as AMAZON.SearchQuery slot. {item} will be the task name.

## Contributing

Pull requests are welcome. For major changes, please open an issue first
to discuss what you would like to change.

Please make sure to update tests as appropriate.

## License

[MIT](https://choosealicense.com/licenses/mit/)