<p align="center">
    <img src="https://bloatless.org/img/logo.svg" width="60px" height="80px">
</p>

<h1 align="center">Bloatless Munin Node</h1>

<p align="center">Bloatless Munin Node is a simple port of munin-node and some basic plugins to PHP.</p>

## Features

This application provides a basic [munin-node](http://munin-monitoring.org/) supporting the following commands:

* list
* fetch
* config
* version

It includes the following plugins by default:

* Cpu (Cpu usage)
* Df (Disc usage)
* If_ (Network interface traffic)
* Load (Load average)
* Memory (Memory usage)
* Uptime

All plugins are ports from the original munin-node versions to PHP. Additional plugins can be added easily.

## Documentation

### Requirements

* PHP >= 7.2
* Linux operating system
* Read access to system files like `/proc/stat` or `/proc/loadavg`

### Installation

1. Clone/Download this repository to your server.
2. Run `php munin.php`  from the munin-node root folder.

### Adjusting configuration

In case you need to adjust the configuration copy the `config/config.default.php` file to
`config/config.php` and adjust it to your requirements. Then kill and restart the munin.php process.

### Additional notes

This application is not meant to be a replacement of the original [munin](http://munin-monitoring.org/). Whenever
possible you should use the original munin software which offers much more plugins and is tested on thousands of
machines. 

Nevertheless it is sometimes useful to have a munin-node implemented in PHP. For example when you have
some kind of managed sever where you can not install additional software - but can run PHP scripts. In those
cases it might be a good possibility to fetch some basic statistics from those machines.  

## License

The MIT License
