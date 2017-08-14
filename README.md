# AceTool

**AceProject for the CLI**

![screenshot](http://www.millsoft.de/bilder/acetool.png)

### Status
This project is just fresh out of the oven and it is still in development. Use it on your own risk. You CAN'T control your whole AceProject account yet. What you can do is the following:

 - Login / Logout
 - List Projects
 - List Tasks by a Project
 - Find Tasks by a Search String
 - Find Starred Tasks
 - Start and Stop the clock for a given task
 - See all running clocks (including all of your coworkers)


## Getting Started

These instructions will get you a copy of the project up and running on your local machine for development and testing purposes. See deployment for notes on how to deploy the project on a live system.

### Prerequisites

To use it you need to install php first. I tested the project with php 7 on linux but it also should work on at least php 5.6 and also on windows.



### Installing


Install this project on your machine by downloading the ZIP or use git to clone it.  Then use comoser to install all needed dependencies by executing following command in the project directory:

    composer install


### Running

To run the app simply execute the ace.php with php:

    php ace.php
If you cann the script like that you will see a help page and all available commands / parameters. To get help for a given command add --help to the command, for example:

    php ace.php task --help

To use the tool you first need to login to your account. To do this, call the script with following parameters:

    php ace.php account:login username password subdomain

I recommend you to make your own bat or sh script and make it global so it works globally.

## Built With

* [AceProject API](https://github.com/millsoft/aceproject) - The aceproject Web API
* [Symfony CLI Component](https://github.com/symfony/console) - For creating awesome CLI Tools


## Versioning

I use [SemVer](http://semver.org/) for versioning. For the versions available, see the [tags on this repository](https://github.com/your/project/tags). 

## Authors

* **Michael Milawski** - *Initial work* - [Millsoft](http://www.millsoft.de)



## License

This project is licensed under the MIT License.

## Acknowledgments

* I am not affiliated with AceProject in any way and the AceProject devs are not affiliated with this project here.
