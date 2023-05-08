# PdoFish
An Active Record style wrapper for PHP and PDO

## Purpose
The last stable release of [PHP Active Record](http://www.phpactiverecord.org) was in 2010, with the latest nightly release being in 2013. Because PHPAR appears to be abandoned, but is still in use in many projects, PdoFish was designed as an extremely lightweight alternative of a subset of the ActiveRecord syntax. The goal was to create a drop-in replacement of _some, but not all_ of the same conventions as PHPAR. 

The goal of this project was to recreate the _static_ methods that work with table models.  This project is not for everyone. Sloppy programming can break these functions. However, if you're already using PHPAR or wish to use an Active Record style DB interface in PHP, this may suit. To be clear, many of the conventions in PHPAR are not implemented in PdoFish, however, the aim of this project was simple, readable code that is as thin a layer on top of PDO as possible. 

## Currently Supported Methods
```Model::raw()``` - execute raw SQL  
```Model::find_by_pk()``` - find a single row by primary key  
```Model::find()``` - find by a column called "id"  
```Model::all()``` - return all rows matching a query   
```Model::first()``` - returns the first row matching a query  
```Model::last()``` - returns the first row matching a query  
```Model::find_by_sql()``` - returns a single row matching a query  
```Model::find_all_by_sql()``` - returns all rows matching a query  
```Model::lastInsertId()``` - returns last insert id  
```Model::count()``` - return matching row count  
```Model::save()``` - insert/create/save data  \
```Model::insert()``` - insert record  
```Model::update()``` - update field(s)  
```Model::delete()```  - delete a row  
```Model::delete_by_id()``` - delete by a column called "id"   
```Model::delete_all()``` - delete via criteria provided   
```Model::deleteMany()``` - delete multiple rows matching criteria   
```Model::set_fetch_mode()``` - set the PDO fetch mode, e.g. PDO::FETCH_OBJ or PDO::FETCH_ASSOC  

#### Dynamic function names
```Model::find_by_[field]``` - find a single row by a specific column value  
```Model::find_all_by_[field]``` - find multiple rows where one column matches the given value  

#### The following methods must be called via the PdoFish class
```PdoFish::load_models($path)``` - load PdoFish models. This can be done via instanciation or via this explicit function   
```PdoFish::truncate($table)``` - truncate a table, must be called via PdoFish class  

#### The following methods are also supported: 
```PdoFish::connection()->query($sql)``` will execute a raw SQL statement via PDO's ```query()``` interface  
```PdoFish::table()->last_sql``` will return the last SQL query run through PdoFish - it is not model specific. Note that it will not capture SQL run through PdoFish::connection().  

What's Not Supported
------------
Quite a bit, but hopefully, not conventions you need. Here is a list of known PHPAR features not yet implemented in PdoFish: 
- multiple active connections (coming in a future version)
- foreign key relationships  
- transactions, including rollbacks  
- eager loading  
- validations 
- associations
- delegators
- attribute setters 
- aliases
- serialization 
- automatic timestamping
- feeding an array to ```find()```  
- feeding an array of values to ```first()```, e.g. ```ModelName::first(array(2,3));```  
- read-only models   
- callbacks - before callbacks and after callbacks 
- associations, such as ```$has_many``` or ```$belongs_to``` (if set in models, these properties will be safely ignored)  
- ```Model::table()->xxx``` properties other than ```last_sql```   
- auto timestamping of ```updated_at``` and ```created_at``` fields
- feeding an array to the ```joins``` element of your finder array argument. ```joins``` should be a string. See [arguments supported](#arguments-supported) for more. 

Installation
------------
You can install PdoFish using Composer or manually.   

Using composer:   
```bash
composer require sethadam1/pdofish
```

Manually: 
- Upload the files to your web server.  
- Add any models in your ```models/``` directory (or any other directory), being sure to use the example to extend the ```PdoFish``` class and set the ```$table``` variable and ```id```, if your primary key is not already called ```id```. 
- Include Pdofish/PdoFish.php in your code and you should be ready to go. 

```php  
require_once '/path/to/PdoFish/PdoFish.php';  
```

## Basic CRUD
To insert data into a table, you can use Active Record style syntax. Both of these are valid: 

#### Create 
```php  
$data = [
	'id' => 1,  
	'col1' => '2020-08-27 09:58:01',  
	'col2'=> 'a string',  
	'col3' => 12345  
];  
$y = new ModelName($data);  
$y->save();  
  
$x = new ModelName();  
$x->id = 1;  
$x->col1 = '2020-08-27 09:58:01';  
$x->col2 = 'a string';  
$x->col3 = "12345";  
$x->save();  
```

Like PHP Active Record, the ```->save()``` method can be used to update existing objects OR insert new ones. However, if the primary key is not called "id," you'll need to make sure you set a primary key on the Model. If you don't specify the primary key in that case, the update will fail and it will attempt to insert a new row. 
  
A non-PHPAR way to do inserting is as follows:   
```php  
$data = [
	'id' => 1,  
	'col1' => '2020-08-27 09:58:01',  
	'col2'=> 'a string',  
	'col3' => 12345  
];   
$insertid = ModelName::insert($data);    
  
echo $insertid;   
// example response "3"  
```


#### Read

Several of the ways to read data from PHPAR are supported by PDOFish. 
```php  
//both of these print an object
$x = ModelName::first(['conditions'=>['some_field=?', 'some_value']]);
print_r($x); 

$x = ModelName::last(['conditions'=>['some_field=?', 'some_value']]);
print_r($x); 
```  

```php  
//prints an associative array 
$x = ModelName::first(['conditions'=>['some_field=?', 'some_value']], PDO::FETCH_ASSOC);
print_r($x); 
```  

```php  
//also prints an associative array   
ModelName::set_fetch_mode(PDO::FETCH_ASSOC); 
$x = ModelName::first(['conditions'=>['some_field=?', 'some_value']]);
print_r($x); 
```  

```php  
// print a row where primary key, in this case 'example_id' = 5
$x = ModelName::find_by_pk(5);
print_r($x);
```  

```php  
// print a single row matching SQL query  
$x = ModelName::find_by_sql('select * from random_table where random_field=12');
print_r($x);
```  

```php  
// print a row where id = 5   
$x = ModelName::find(5);
print_r($x); 
```

```php  
// print 5 rows of data from this query   
$x = ModelName::all([
	'select'=>'field1, field2, field3',
	'from'=>'table t',
	'joins'=>'LEFT JOIN table2 t2 ON t.field1=t2.other_field',
	'conditions' => ['some_field=?', 'some_value'],
	'order'=>'field3 ASC',
	'limit'=>5
], PDO::FETCH_OBJ);
print_r($x);
```

These PHPAR styles are supported, but not reccommended: 
```php  
// print a row where name is "John"   
$x = ModelName::find('first' array('conditions'=>array('name=?','John')));
print_r($x); 
```

```php  
// print all rows where user_id is greater than 1   
$x = ModelName::find('all' array('conditions'=>array('id>?','1')));
print_r($x); 
```

#### Update  
```php    
// updates column "firstname" to "Boris" where id = 5
ModeName::update(['firstname'=>'Boris'], ['id'=>5]); 

// updates columns "firstname" to "June", "lastname" to "Basoon" where id = 5
ModeName::update(['firstname'=>'June', 'lastname'=>'Basoon'], ['id'=>5]); 
```   
  
You can use the save() method on an existing model object, just like you can in Active Record, provided it has a property called "id" that matches a unique column in the table OR has a ```$primary_key``` attribute defined in the model.  

Consider a table with three columns, "id", "columnA", and "columnB." 
```php  
// this will work if ModelName has a pk of "id" or has a $primary_key defined. 
$y = ModelName::find(3); //find a model with primary key=3  
$y->columnA = "Updated field!";  
$y->save(); // this will work   
```

Now consider a table with three columns, "row_id", "columnA", and "columnB."   
```php   
// this will also work  
$y = ModelName::find(3); //find a model with primary key=3  
$y->columnA = "Updated field!";  
$y->save(); // this will work only if the next lines are also configured...   
```  

But ModelName must have the right properties defined, like so:  
```php   
class ModelName extends PdoFish {
	static $table_name = 'tablename';
	static $primary_key = 'row_id';
}
``` 

#### Delete  
```php    
// delete rows where column "firstname" is equal to "Boris"  
ModeName::delete(['firstname'=>'Boris']);   
  
// delete row where column "id" is equal to "5"  
ModeName::delete_by_id(5);   
  
// delete rows where column "user_id" is equal to 1, 2, or 3  
ModeName::deleteMany(['user_id', '1,2,3']);   

// delete via criteria, e.g. rows where column "user_id" is equal to 1, 2, or 3  
ModeName::delete_all([ 'conditions'=>['user_id=? OR user_id=? OR user_id=?',1,2,3] ]);   
   
// this will truncate an entire table. You MUST call this via the PdoFish class, and not a child class  
PdoFish::truncate('tableName');  

// you cannot use table()->method() functions   
$y = ModelName::find(3); //find a model with primary key=3  
$y->delete(); // this will not work   
```    
  
## Arguments supported
The following arguments are supported in the PdoFish queries:  
```select``` - columns to select  
```from``` - table, or table and an alias _e.g. "prices p"_  
```joins``` - a **string** of joins in SQL syntax, _e.g. LEFT JOIN table2 on prices.field=table2.field_.  Note that you cannot use an array here.   
```conditions``` - an array of SQL, using ? placeholders, and arguments to be bound _e.g. ['year=? AND mood=?',2021,'happy']_   
```group``` - group by, using a field name  
```having``` - having, _e.g. 'count(x)>3'_  
```order``` - order by, _e.g. 'id DESC'  
```limit``` - a positive integer greater than 0  

## Credits
Some of this code has roots in the [David Carr](https://twitter.com/dcblogdev)'s [PDOWrapper](https://dcblog.dev/docs/pdo-wrapper) project. 
