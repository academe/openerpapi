PHP OpenERP API
===============

Forked from BitBucket Simbigo\OpenERP with some additions and changes
to make things easier for a project of mine.

https://bitbucket.org/simbigo/openerp-api

The changes are not massive, but include:

* PHP5.3 compatibility. The original was PHP5.4, but for no functional reason - just the array declaration format.
* Change of namespace to Academe\OpenErpApi (just so the old and new libraries can be run together if needed).

TODO
====

* composer.json for autoloading (I'm using a PSR-4 entry in the project composer.json for now, while developing).
* An interface for the client (the connection class) so a JSON-RPC alternative can be slipped in.
* Namespace the exceptions, as they are used heavily.
* Take a look at the data formats returned - how much of the deep nesting is needed?
* Add some handy wrappers for common objects and operations. These are likely to need extending for
  the specific requirements of different projects.

