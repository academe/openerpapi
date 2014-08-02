PHP OpenERP API
===============

Forked from BitBucket Simbigo\OpenERP with some additions and changes
to make things easier for a project of mine.

https://bitbucket.org/simbigo/openerp-api

Initial changes were:

* PHP5.3 compatibility. The original was PHP5.4, but for no functional reason - just the array declaration format.
* Change of namespace to Academe\OpenErpApi (just so the old and new libraries can be run together if needed).

Now the Pimple DIC is being used to manage DI within this package. It makes it easier to use - you
throw some parameters in, and the DIC handles instantiation and dependancies.

Examples
========

The DI makes things a little easier to manage. Each instantation of `Academe\OpenErpApi\OpenErp()` will
contain its own DIC, so an application can connect to multiple OpenERP systems or as multiple users
simultaneously, if required.

Here is a simple example to read a partner record:

    // Instantiate the factory.
    $openerp = new Academe\OpenErpApi\OpenErp();
    
    // Point to the OpenERP installation at example.com
    // Here you can also set a different port and characterset from the default.
    $openerp->setClientUri('http://example.com/');
    
    // Set the application database/username/password to access the application.
    $openerp->setCredentials($database, $username, $password);
    
    // Instantiate an object interface.
    $object = $openerp->getInterface('object');
    
    // Log in to OpenERP.
    // This will validate the username/password, and return the user ID.
    // The user ID will be stored on the connection object in the DIC and used
    // for most subsequent API functions.
    $uid = $openerp->getInterface('common')->login();
    
    // Get the record for partner number 1 (database ID 1).
    $partner = $object->read('res.partner', 1);
    
    // Get the record for partner numbers 2 and 3.
    $partners = $object->read('res.partner', array(2, 3));

    // Get the partner for external ID 'base.my_foo'.
    $partner = $object->readExternal('res.partner', 'base.my_foo');
    
    // The returned records at the moment are arrays structured as they are returned
    // from the API. Some normalisation is needed, and that is a future task.
    
    // If not logged in yet, the read() method will automatically log in using the
    // supplied credentials. The remaining methods and interfaces will all do this
    // eventually.

TODO
====

* [ ] An interface for the client (the connection class) so a JSON-RPC alternative can be slipped in.
* [ ] Namespace the exceptions, as they are used heavily.
* [ ] Take a look at the data formats returned - how much of the deep nesting is needed?
* [ ] Add some handy wrappers for common objects and operations. These are likely to need extending for
  the specific requirements of different projects.
* [ ] Fix the documentation in src. Move the documentation out of src first.

