[![Build Status](https://travis-ci.org/mheinzerling/php-databaseutils.svg?branch=master)](https://travis-ci.org/mheinzerling/php-databaseutils) [![Code Climate](https://codeclimate.com/github/mheinzerling/php-databaseutils/badges/gpa.svg)](https://codeclimate.com/github/mheinzerling/php-databaseutils) [![Test Coverage](https://codeclimate.com/github/mheinzerling/php-databaseutils/badges/coverage.svg)](https://codeclimate.com/github/mheinzerling/php-databaseutils/coverage) [![Issue Count](https://codeclimate.com/github/mheinzerling/php-databaseutils/badges/issue_count.svg)](https://codeclimate.com/github/mheinzerling/php-databaseutils) 

#mheinzerling/databaseutils

Some useful utilities for database access and modification. 

##Composer
    "require": {
        "mheinzerling/databaseutils": "^3.0.0"
    },
    
##Changelog

### 3.0.0
* improve package structure
* overwrite TestDatabaseConnection parameter via environment variables
* rename PersistenceProvider to ConnectionProvider
* make log in LoggingPDO non-static
* PHP 7.1

### 2.0.2
* LoggingPDOStatement extends PDOStatement

### 2.0.0
* update to PHP 7

### 1.1.0
* improve parser 

### 1.0.0
* initial version 