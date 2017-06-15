Alameda Smoke Test
==================

The Alameda Smoke Test is a command line tool that checks if your
application has major problems within your dependency injection
container. It will boot your AppKernel and will attempt to create
an instance of each and every service known to the container.

Usage
-----

Clone the repository to a directory of your choice and run it from there:

     $ git clone https://github.com/alameda-red/smoke-test.git
     $ cd smoke-test
     $ php composer.phar install
     $ php smoke-test quality:smoke-test /path/to/app/folder

For further options run:

    $ php smoke-test quality:smoke-test --help

You can build a phar file using [`box-project/box2`][1]. Use it:

     $ php smoke-test.phar quality:smoke-test /path/to/app/folder

[1]: https://github.com/box-project/box2
