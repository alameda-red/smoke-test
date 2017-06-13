Alameda Smoke Test
==================

Te Alameda Smoke Test is a command line tool that checks if your
application has major problems within your dependency injection
container. It will boot your AppKernel and will attempt to create
an instance of each and every service known to the container.

Usage
-----

Clone the repository to a directory of your choice and run it from there:

     $ php smoke-test.php quality:smoke-test /path/to/app/folder

For further options run:

    $ php smoke-test.php quality:smoke-test --help
