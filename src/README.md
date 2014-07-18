# OpenERP API #

## How to use: ##

For begin you must call login method.


```
#!php

<?php
use Simbigo\OpenERP\OpenERP;
$erp = new OpenERP('http://127.0.0.1:8069', 'utf-8');
$erp->login('db_test', 'myLogin', 'myPassword'); // return user id, if success
```

## Create new record ##


```
#!php

<?php
$erp->login('db_test', 'myLogin', 'myPassword');
$partner = ['name' => 'John', 'email' => 'john@example.com'];
$erp->create('res.partner', $partner); // return record ID, if it created
```

## Search records ##


```
#!php

<?php
$erp->login('db_test', 'myLogin', 'myPassword');
$offset = 0; // default
$limit = 1000; // default
$criteria= [['name', '=', 'John'], ['email', '=', 'john@example.com']];
$erp->search('res.partner', $criteria, $offset, $limit); // return ID array

```

## Read records ##


```
#!php

<?php
$erp->login('db_test', 'myLogin', 'myPassword');
$readColumns = ['name', 'email']; // default [] equal 'SELECT * ...'
$ids = [30, 31];
$erp->read('res.partner', $ids, $readColumns); // return array of records
```

## Update records ##


```
#!php

<?php
$erp->login('db_test', 'myLogin', 'myPassword');
$columns = ['name' => 'Peter', 'email' => 'peter@example.com'];
$ids = [30, 31];
$erp->write('res.partner', $ids, $columns); // return true
```


## Delete records ##


```
#!php

<?php
$erp->login('db_test', 'myLogin', 'myPassword');
$ids = [30, 31];
$erp->unlink('res.partner', $ids); // return true
```